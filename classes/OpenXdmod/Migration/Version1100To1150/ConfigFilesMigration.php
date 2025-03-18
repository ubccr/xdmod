<?php
/**
 * Update config files from version 11.0.0 to 11.5.0
 */

namespace OpenXdmod\Migration\Version1100To1150;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;
use CCR\Json;

class ConfigFilesMigration extends AbstractConfigFilesMigration
{
    /**
     * Update portal_settings.ini with the new version number.
     */
    public function execute()
    {
        $this->assertPortalSettingsIsWritable();
        $this->assertModulePortalSettingsAreWritable();
        $this->assertJsonConfigIsWritable('organization');
        $organization_config = Json::loadFile(CONFIG_DIR . '/organization.json');
        $organization_config = [$organization_config];
        $this->writeJsonConfigFile('organization', $organization_config);
        $this->writePortalSettingsFile();
        $this->writeModulePortalSettingsFiles();
    }
}
