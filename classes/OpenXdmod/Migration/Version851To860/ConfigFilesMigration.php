<?php
/**
 * Update config files from version 8.5.1 To 8.6.0.
 */
namespace OpenXdmod\Migration\Version851To860;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;

/**
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
