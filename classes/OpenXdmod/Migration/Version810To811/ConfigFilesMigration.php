<?php
namespace OpenXdmod\Migration\Version810To811;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;

/**
 * Update config files from version 8.1.0 To 8.1.1.
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
