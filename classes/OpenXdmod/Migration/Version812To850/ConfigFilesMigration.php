<?php
/**
 * Update config files from version 8.1.2 To 8.5.0.
 */

namespace OpenXdmod\Migration\Version812To850;

use CCR\DB;
use CCR\Json;
use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;
use OpenXdmod\Setup\Console;
use OpenXdmod\Setup\WarehouseExportSetup;
use xd_utilities;

class ConfigFilesMigration extends AbstractConfigFilesMigration
{

    /**
     * Update portal_settings.ini with the new version number.
     */
    public function execute()
    {
        $cloudRolesFilePath = CONFIG_DIR . '/roles.d/cloud.json';
        if (file_exists($cloudRolesFilePath)) {
            $cloudRolesFile = Json::loadFile($cloudRolesFilePath);
            if (isset($cloudRolesFile['+roles']['+default']['+summary_charts'])) {
                foreach($cloudRolesFile['+roles']['+default']['+summary_charts'] as $key => $data) {
                    if(isset($data['data_series']['data'])){
                        $dataId = 1;
                        foreach($data['data_series']['data'] as $dsKey => $dsData) {
                            if(!isset($data['data_series']['data'][$dsKey]['id'])) {
                                $cloudRolesFile['+roles']['+default']['+summary_charts'][$key]['data_series']['data'][$dsKey]['id'] = $dataId;
                                $dataId++;
                            }

                        }
                    }
                }
            }
            Json::saveFile($cloudRolesFilePath, $cloudRolesFile);
        }

        // Updates the resources.json file by translating the `resource_type_id` to `resource_type`
        $this->updateResources();

        $modifiedResourceTypePath = CONFIG_DIR . '/resource_types.json';
        $xdmodResourceTypesPath = $modifiedResourceTypePath. '.rpmnew';

        // If the rpmnew path exists then we know that the user has modified resource_types and we need to take that
        // into account.
        if (file_exists($xdmodResourceTypesPath)) {
            $xdmodResourceTypes = Json::loadFile($xdmodResourceTypesPath)['resource_types'];

            $results = array();
            $modifiedResourceTypes = Json::loadFile($modifiedResourceTypePath);

            // Collecting information and formatting it so that it is inline with the new resource_types format.
            foreach($modifiedResourceTypes as $resourceType) {
                $abbrev = $resourceType['abbrev'];
                $realms = array();

                if (array_key_exists($abbrev, $xdmodResourceTypes)) {
                    $realms = $xdmodResourceTypes[$abbrev]['realms'];
                }

                $results[$abbrev] = array(
                    'description' => $resourceType['description'],
                    'realms' => $realms
                );
            }

            // Move their modified resource_types so that we don't overwrite them.
            rename($modifiedResourceTypePath, $modifiedResourceTypePath . '.rpmsave');

            // Save the re-formatted contents to the resource_types file.
            @file_put_contents($modifiedResourceTypePath, json_encode(array('resource_types' => $results), JSON_PRETTY_PRINT));
        }

        $this->assertPortalSettingsIsWritable();

        $console = Console::factory();

        $console->displayMessage(<<<"EOT"
This release of XDMoD features an optional replacement for the summary
tab that is intended to provide easier access to XDMoD's many features
for new or inexperienced (novice) users. Detailed information is available
as https://open.xdmod.org/novice_user.html
EOT
        );
        $console->displayBlankLine();
        $novice_user = $console->prompt(
            'Enable Novice User Tab?',
            'off',
            array('on', 'off')
        );

        $console->displayMessage(<<<"EOT"
This release of XDMoD includes support for batch exporting of data from the
data warehouse.
EOT
        );
        $console->displayBlankLine();
		$exportSetup = new OpenXdmod\Setup\WarehouseExportSetup($console);
        $exportSettings = $exportSetup->promptForSettings([
            'data_warehouse_export_export_directory' => xd_utilities\getConfiguration('data_warehouse_export', 'export_directory'),
            'data_warehouse_export_retention_duration_days' => xd_utilities\getConfiguration('data_warehouse_export', 'retention_duration_days')
        ]);

        $this->writePortalSettingsFile(array_merge(
            ['features_novice_user' => $novice_user],
            $exportSettings
        ));
    }

    /**
      *
     * @throws \Exception
     */
    private function updateResources()
    {
        $resources = Json::loadFile(CONFIG_DIR . DIRECTORY_SEPARATOR . 'resources.json', true);
        $resourceTypeIds = $this->retrieveResourceTypeIds();

        foreach ($resources as &$resource) {
            if (array_key_exists('resource_type_id', $resource)) {
                $resourceTypeId = $resource['resource_type_id'];
                $resource['resource_type'] = $resourceTypeIds[$resourceTypeId];
                unset($resource['resource_type_id']);
            }
        }

        $this->writeJsonConfigFile('resources', $resources);
    }

    /**
     *
     * @return array
     * @throws \Exception
     */
    private function retrieveResourceTypeIds()
    {
        $db = DB::factory('database');

        $rows = $db->query('SELECT rt.id, rt.abbrev FROM modw.resourcetype AS rt;');

        $results = array();
        foreach($rows as $row) {
            $results[$row['id']] = $row['abbrev'];
        }
        return $results;
    }
}
