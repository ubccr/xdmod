<?php
/**
 * Update config files from version 11.0.2 to 11.0.3
 */

namespace OpenXdmod\Migration\Version1102To1103;

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
