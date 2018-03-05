<?php
namespace OpenXdmod\Migration\Version700To701;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;

/**
 * Update config files from version 7.5.0 to 8.0.0.
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
    }
}
