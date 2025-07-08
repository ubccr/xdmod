<?php
/**
 * Update config files from version 11.0.1 to 11.0.2
 */

namespace OpenXdmod\Migration\Version1101To1102;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;

class ConfigFilesMigration extends AbstractConfigFilesMigration
{

    /**
     * Update portal_settings.ini with the new version number.
     */
    public function execute()
    {
        $this->assertPortalSettingsIsWritable();
        $this->assertModulePortalSettingsAreWritable();
        $this->writePortalSettingsFile();
        $this->writeModulePortalSettingsFiles();
    }
}
