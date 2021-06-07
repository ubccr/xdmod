<?php
/**
 * Update config files from version 9.5.0 To 10.0.0.
 */

namespace OpenXdmod\Migration\Version950To1000;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;
use OpenXdmod\Setup\Console;

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
