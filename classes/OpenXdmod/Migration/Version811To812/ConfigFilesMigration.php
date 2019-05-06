<?php
namespace OpenXdmod\Migration\Version811To812;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;

/**
 * Update config files from version 8.1.1 To 8.1.2.
 *
 * Update portal_settings.ini with the new version number.
 */
class ConfigFilesMigration extends AbstractConfigFilesMigration
{
    public function execute()
    {
        $this->assertPortalSettingsIsWritable();
        $this->writePortalSettingsFile();
    }
}
