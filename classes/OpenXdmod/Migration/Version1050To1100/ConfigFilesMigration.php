<?php
/**
 * Update config files from version 10.5.0 to 11.0.0
 */

namespace OpenXdmod\Migration\Version1050To1100;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;
use OpenXdmod\Setup\Console;
use CCR\DB;
use CCR\Json;
use ETL\Utilities;
use PDO;

class ConfigFilesMigration extends AbstractConfigFilesMigration
{

    private $resource_types_map = [
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
        $console = Console::factory();
        $console->promptBool(<<<"EOT"
Open XDMoD 11.0 includes changes to how your resource's specifications are tracked. Implementing these changes may take some time.
In some cases it may be as long as 20-30 minutes. Before continuing, please check your resource_specs.json file and make sure it is accurate.
This will make the implementation process much quicker.If you have multiple entries for a resource, please make sure the start and end times
for each entry is accurate. Also note that if a resource has multiple entries, the end_date may be omitted from the last entry.
Would you like to continue?
EOT
        );

        $dbh = DB::factory('datawarehouse');
        $seen = array();

        $resource_specs_data = Json::loadFile(CONFIG_DIR . '/resource_specs.json');
        $resource_config = Json::loadFile(CONFIG_DIR . '/resources.json');
        $resource_to_resource_type_map = array();
        $resources_get_start_dates = array();
        $resource_specs_start_times = array();

        foreach ($resource_config as $key => $value) {
            // Add the allocation type property to the resources.json file. The default is cpu.
            $resource_config[$key]['allocation_type'] = 'cpu';
            $resource_to_resource_type_map[$value['resource']] = $value['resource_type'];
        }

        $this->assertJsonConfigIsWritable('resources');
        $this->writeJsonConfigFile('resources', $resource_config);

        foreach ($resource_specs_data as $key => $value) {
            if (!array_key_exists('start_date', $value)) {
              // Some entries may not have a start_date property. This is needed when array_multisort is called below.
              $resource_specs_data[$key]['start_date'] = NULL;
              $resources_get_start_dates[] = $this->resource_types_map[strtolower($resource_to_resource_type_map[$value['resource']])];
            }
        }

        // Sort the resource_specs.json file by resource and start date. This order helps when trying to get start and end dates for records that may be missing them.
        array_multisort(array_column($resource_specs_data, 'resource'), SORT_ASC, array_column($resource_specs_data, 'start_date'), SORT_ASC, $resource_specs_data);

        $start_date_sql = $this->getStartDateSql($resources_get_start_dates);

        if (!empty($start_date_sql)) {
          $resource_specs_start_times_stmt = $dbh->query($start_date_sql, array(), true);
          $resource_specs_start_times = $resource_specs_start_times_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        // Set the start and end dates for each resource_specs record as best we can. Also, change the nodes, processors, and ppn field names and add fields for
        // new gpu information.
        foreach ($resource_specs_data as $key => $value) {
            $next = (array_key_exists($key + 1, $resource_specs_data)) ? $resource_specs_data[$key + 1] : null;
            $prev = (array_key_exists($key - 1, $resource_specs_data)) ? $resource_specs_data[$key - 1] : null;

            // If this is the first entry for a resource, there are different methods and checks to get the start and end dates.
            if (!in_array($value['resource'], $seen)) {
              $seen[] = $value['resource'];

              if (empty($resource_specs_data[$key]['start_date']) && (array_key_exists($value['resource'], $resource_specs_start_times))) {
                  $resource_specs_data[$key]['start_date'] = $resource_specs_start_times[$value['resource']];
              }

              if ((!array_key_exists('end_date', $value) || empty($resource_specs_data[$key]['end_date'])) && $next['resource'] === $resource_specs_data[$key]['resource']) {
                  $resource_specs_data[$key]['end_date'] = $next['start_date'];
              }
            }

            if (empty($resource_specs_data[$key]['start_date']) && $prev['resource'] === $resource_specs_data[$key]['resource']) {
                $resource_specs_data[$key]['start_date'] = $prev['end_date'];
            }

            if (empty($resource_specs_data[$key]['end_date']) && $next['resource'] === $resource_specs_data[$key]['resource']) {
                $resource_specs_data[$key]['end_date'] = $next['start_date'];
            }

            $resource_specs_data[$key]['cpu_node_count'] = $value['nodes'];
            $resource_specs_data[$key]['cpu_processor_count'] = $value['processors'];
            $resource_specs_data[$key]['cpu_ppn'] = $value['ppn'];
            $resource_specs_data[$key]['gpu_node_count'] = 0;
            $resource_specs_data[$key]['gpu_processor_count'] = 0;
            $resource_specs_data[$key]['gpu_ppn'] = 0;

            unset($resource_specs_data[$key]['nodes']);
            unset($resource_specs_data[$key]['processors']);
            unset($resource_specs_data[$key]['ppn']);
        }

        $this->assertJsonConfigIsWritable('resource_specs');
        $this->writeJsonConfigFile('resource_specs', $resource_specs_data);

        $this->assertPortalSettingsIsWritable();
        $this->writePortalSettingsFile();
    }

    /**
     * Create sql statement to get first date that data exists for a resource. Makes a UNION
     * statement to get dates from the appropriate fact table for each realm.
     *
     * @param @resource_realms Realms to get resource start dates for
     */
    private function getStartDateSql($resource_realms)
    {
        $sql_array = array();
        $resource_types_unique = array_unique($resource_realms);

        $resource_specs_start_times_jobs_sql = "SELECT
              r.code,
              DATE(FROM_UNIXTIME(MIN(jr.submit_time_ts))) as `start_time`
          FROM
              modw.resourcefact AS r
          JOIN
              modw.job_records AS jr ON r.id = jr.resource_id
          GROUP BY
              r.id";

        $resource_specs_start_times_cloud_sql = "SELECT
              r.code,
              MIN(DATE(sr.start_time)) as `start_time`
          FROM
              modw.resourcefact AS r
          JOIN
              modw_cloud.session_records AS sr ON r.id = sr.resource_id
          GROUP BY
              r.id";

        $resource_specs_start_times_storage_sql = "SELECT
              r.code,
              DATE(MIN(sf.dt)) as `start_time`
          FROM
              modw.resourcefact AS r
          JOIN
              modw.storagefact AS sf ON r.id = sf.resource_id
          GROUP BY
              r.id";

        foreach ($resource_types_unique as $key => $value) {
            $variable_name = 'resource_specs_start_times_'.$value.'_sql';
            $sql_array[] = $$variable_name;
        }

        return (!empty($sql_array)) ? implode(' UNION ', $sql_array) : '';
    }
}
