<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Migration\Version701To710;

use CCR\Json;
use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;

/**
 * Update config files from version 7.0.1 To 7.1.0.
 */
class ConfigFilesMigration extends AbstractConfigFilesMigration
{
    /**
     * Execute the migration.
     */
    public function execute()
    {
        // Make sure all the config files that will be changed are writable.
        $this->assertPortalSettingsIsWritable();

        // Set new options in portal_settings.ini.
        $this->writePortalSettingsFile();

        // Add the 'provider' group_by if it does not already exist.
        $this->modifyDatawarehouse();
    }

    public function modifyDatawarehouse()
    {
        $datawarehouseFile = $this->config->getFilePath('datawarehouse');
        $datawarehouse = Json::loadFile($datawarehouseFile);
        $realms = isset($datawarehouse['realms']) ? $datawarehouse['realms'] : array();
        $jobs = isset($realms['Jobs']) ? $realms['Jobs'] : array();
        $groupBys = isset($jobs['group_bys']) ? $jobs['group_bys'] : array();

        $found = array_filter(
            $groupBys,
            function ($groupBy) {
                return isset($groupBy['name']) && $groupBy['name'] === 'provider';
            }
        );
        if (empty($found)) {
            $groupBys[] = array(
                'name' => 'provider',
                'class' => 'GroupByProvider'
            );
            $datawarehouse['realms']['Jobs']['group_bys'] = $groupBys;
            $this->writeJsonConfigFile('datawarehouse', $datawarehouse);
        }
    }
}
