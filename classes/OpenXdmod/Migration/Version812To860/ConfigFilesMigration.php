<?php
/**
 * Update config files from version 8.1.2 To 8.5.0.
 */

namespace OpenXdmod\Migration\Version812To860;

use CCR\DB;
use CCR\Json;
use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;
use OpenXdmod\Setup\Console;
use OpenXdmod\Setup\WarehouseExportSetup;

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

            // We need to make sure that we grant access to both default and pub for the new groupby
            if (isset($cloudRolesFile['+roles']['+default']['+query_descripters'])) {
                $this->addQueryDescripter(
                    $cloudRolesFile['+roles']['+default']['+query_descripters'],
                    'Cloud',
                    'domain'
                );
            }
            if (isset($cloudRolesFile['+roles']['+pub']['+query_descripters'])) {
                $this->addQueryDescripter(
                    $cloudRolesFile['+roles']['+pub']['+query_descripters'],
                    'Cloud',
                    'domain'
                );
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
tab that is intended to provide easier access to XDMoD's many features.
Detailed information is available at https://open.xdmod.org/dashboard.html
EOT
        );
        $console->displayBlankLine();
        $dashboard = $console->prompt(
            'Enable Dashboard Tab?',
            'off',
            array('on', 'off')
        );

        $console->displayMessage(<<<"EOT"
This release of XDMoD includes support for batch exporting of data from the
data warehouse.
EOT
        );
        $console->displayBlankLine();
        $exportSetup = new WarehouseExportSetup($console);
        $exportSettings = $exportSetup->promptForSettings([
            'data_warehouse_export_export_directory' => '/var/spool/xdmod/export',
            'data_warehouse_export_retention_duration_days' => 30
        ]);
        $data = parse_ini_file($this->portalSettingsPath, true);
        $roadmapUrl = 'https://trello.com/embed/board?id=mdFESh6j';
        if('https://trello.com/b/mdFESh6j.html' !== $data['roadmap']['url']){
            $roadmapUrl = $data['roadmap']['url'];
        }
        $this->writePortalSettingsFile(array_merge(
            ['features_user_dashboard' => $dashboard],
            ['roadmap_url' => $roadmapUrl],
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

    /**
     * Add a new query descripter entry ( $realm, $groupBy ) to the provided $queryDescripters if it
     * does not already exist.
     *
     * @param array $queryDescripters the array of query descriptors that will be appended to.
     * @param string $realm           the realm portion of the query descriptor to be added.
     * @param string $groupBy         the groupBy portion of the query descriptor to be added.
     */
    public function addQueryDescripter(&$queryDescripters, $realm, $groupBy)
    {
        $found = false;
        foreach($queryDescripters as $queryDescripter) {
            if ($queryDescripter['realm'] === $realm && $queryDescripter['group_by'] === $groupBy) {
                $found = true;
            }
        }
        if ($found === false) {
            $queryDescripters [] = array(
                'realm' => $realm,
                'group_by' => $groupBy
            );
        }
    }
}
