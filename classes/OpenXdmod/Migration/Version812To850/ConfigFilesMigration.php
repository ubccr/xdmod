<?php
/**
 * Update config files from version 8.1.2 To 8.5.0.
 */

namespace OpenXdmod\Migration\Version812To850;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;

class ConfigFilesMigration extends AbstractConfigFilesMigration
{
    /**
     * Update portal_settings.ini with the new version number.
     */
    public function execute()
    {
        $this->assertPortalSettingsIsWritable();
        $this->writePortalSettingsFile();
    }
}
