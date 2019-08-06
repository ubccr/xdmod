<?php
/**
 * Update config files from version 8.1.2 To 8.5.0.
 */

namespace OpenXdmod\Migration\Version812To850;

use CCR\Json;
use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;
use OpenXdmod\Setup\Console;

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

        $this->writePortalSettingsFile(array(
            'data_warehouse_export_export_directory' => '/var/spool/xdmod/export',
            'data_warehouse_export_retention_duration_days' => 30,
            'data_warehouse_export_hash_salt' => bin2hex(random_bytes(32)),
            'features_novice_user' => $novice_user
        ));
    }
}
