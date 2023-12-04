<?php
/**
 * Update config files from version 10.5.0 to 11.0.0
 */

namespace OpenXdmod\Migration\Version1050To1100;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;
use OpenXdmod\Setup\Console;
use CCR\DB;
use CCR\Json;
use CCR\Log;
use PDO;
use DateTime;

class ConfigFilesMigration extends AbstractConfigFilesMigration
{

    private static $RESOURCE_TYPE_REALM_MAP = [
        'hpc' => 'jobs',
        'htc' => 'jobs',
        'dic' => 'jobs',
        'grid' => 'jobs',
        'cloud' => 'cloud',
        'vis' => 'jobs',
        'vm' => 'cloud',
        'tape' => 'storage',
        'disk' => 'storage',
        'stgrid' => 'storage',
    ];

    /**
     * Update portal_settings.ini with the new version number.
     */
    public function execute()
    {
        $this->assertJsonConfigIsWritable('resources');
        $this->assertJsonConfigIsWritable('resource_specs');
        $this->assertPortalSettingsIsWritable();

        $console = Console::factory();
        echo <<<"EOT"
Open XDMoD 11.0 includes changes to how your resource's specifications
are tracked. Implementing these changes may take some time. In some
cases it may be as long as 20-30 minutes. Before continuing, please check
your resource_specs.json file and make sure it is accurate. This will make
the implementation process much quicker. If you have multiple entries for a
resource, please make sure the start_date and end_date for each entry are
accurate. Also note that if a resource has multiple entries, you may omit the
end_date from the last entry. The first entry for each resource needs a start
date; if you have not provided one, one will be automatically set based on
the earliest database fact for the resource (e.g., earliest submitted job,
earliest cloud VM start time, earliest storage entry start date, etc.).

EOT;
        $proceed = $console->promptBool('Are you ready to continue?', false);

        if (!$proceed) {
            $console->displayMessage("You have chosen not to proceed with the Open XDMoD upgrade. You have exited the XDMoD Upgrade process.");
            exit();
        }

        $dbh = DB::factory('datawarehouse');
        $seen = array();

        $resource_specs_config = Json::loadFile(CONFIG_DIR . '/resource_specs.json');
        $resource_config = Json::loadFile(CONFIG_DIR . '/resources.json');
        $resource_to_resource_type_map = array();
        $resource_realms = array();
        $resource_specs_start_dates = array();

        foreach ($resource_config as $key => $value) {
            // Add the allocation type property to the resources.json file. The default is cpu.
            $resource_config[$key]['resource_allocation_type'] = 'CPU';
            $resource_to_resource_type_map[$value['resource']] = $value['resource_type'];
        }

        $this->writeJsonConfigFile('resources', $resource_config);

        foreach ($resource_specs_config as $key => $value) {
            if (!array_key_exists('start_date', $value)) {
                // Some entries may not have a start_date property. This is needed when array_multisort is called below.
                $resource_specs_config[$key]['start_date'] = null;
                $resource_type = strtolower($resource_to_resource_type_map[$value['resource']]);
                if (array_key_exists($resource_type, self::$RESOURCE_TYPE_REALM_MAP)) {
                    $realm = self::$RESOURCE_TYPE_REALM_MAP[$resource_type];
                    $resource_realms[$realm] = true;
                }
            }
        }

        // Sort the resource_specs.json file by resource and start date. This order helps when trying to get start and end dates for records that may be missing them.
        array_multisort(array_column($resource_specs_config, 'resource'), SORT_ASC, array_column($resource_specs_config, 'start_date'), SORT_ASC, $resource_specs_config);

        $start_date_sql = self::getStartDateSql($resource_realms);

        if (!empty($start_date_sql)) {
            $resource_specs_start_dates_stmt = $dbh->query($start_date_sql, array(), true);
            $resource_specs_start_dates = $resource_specs_start_dates_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        // Set the start and end dates for each resource_specs record as best we can. Also, change the nodes, processors, and ppn field names and add fields for
        // new gpu information.
        foreach ($resource_specs_config as $key => $value) {
            // Check if this is the first entry for a resource
            if (!in_array($value['resource'], $seen)) {
                $seen[] = $value['resource'];

                // If the start date for the first entry for a resource is empty, get the date of the first entry in it's associated
                // fact table.
                if (empty($resource_specs_config[$key]['start_date']) && (array_key_exists($value['resource'], $resource_specs_start_dates))) {
                    $resource_specs_config[$key]['start_date'] = $resource_specs_start_dates[$value['resource']];
                }
            }

            $resource_specs_config[$key]['cpu_node_count'] = $value['nodes'];
            $resource_specs_config[$key]['cpu_processor_count'] = $value['processors'];
            $resource_specs_config[$key]['cpu_ppn'] = $value['ppn'];
            $resource_specs_config[$key]['gpu_node_count'] = 0;
            $resource_specs_config[$key]['gpu_processor_count'] = 0;
            $resource_specs_config[$key]['gpu_ppn'] = 0;

            unset($resource_specs_config[$key]['nodes']);
            unset($resource_specs_config[$key]['processors']);
            unset($resource_specs_config[$key]['ppn']);
        }

        $this->writeJsonConfigFile('resource_specs', $resource_specs_config);
        $this->writePortalSettingsFile();
    }

    /**
     * Create sql statement to get first date that data exists for a resource. Makes a UNION
     * statement to get dates from the appropriate fact table for each realm.
     *
     * @param @resource_realms Realms to get resource start dates for
     */
    private static function getStartDateSql($resource_realms)
    {
        $realm_sql_statements = array();

        $realm_sql_statements['jobs'] = "SELECT
              r.code,
              DATE(FROM_UNIXTIME(MIN(jr.submit_time_ts))) as `start_time`
          FROM
              modw.resourcefact AS r
          JOIN
              modw.job_records AS jr ON r.id = jr.resource_id
          GROUP BY
              r.id";

        $realm_sql_statements['cloud'] = "SELECT
              r.code,
              MIN(DATE(sr.start_time)) as `start_time`
          FROM
              modw.resourcefact AS r
          JOIN
              modw_cloud.session_records AS sr ON r.id = sr.resource_id
          GROUP BY
              r.id";

        $realm_sql_statements['storage'] = "SELECT
              r.code,
              DATE(MIN(sf.dt)) as `start_time`
          FROM
              modw.resourcefact AS r
          JOIN
              modw.storagefact AS sf ON r.id = sf.resource_id
          GROUP BY
              r.id";

        $sql_array = array_intersect_key($realm_sql_statements, $resource_realms);

        return (!empty($sql_array)) ? implode(' UNION ', $sql_array) : '';
    }
}
