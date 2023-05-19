<?php
/**
 * Update config files from version 10.0.2 to 10.5.0.
 */

namespace OpenXdmod\Migration\Version1002To1050;

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
