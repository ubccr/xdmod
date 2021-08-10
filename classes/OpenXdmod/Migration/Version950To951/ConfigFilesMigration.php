<?php
/**
 * Update config files from version 9.5.0 To 9.5.1.
 */

namespace OpenXdmod\Migration\Version950To951;

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
