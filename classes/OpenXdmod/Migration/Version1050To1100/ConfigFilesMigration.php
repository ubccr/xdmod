<?php
/**
 * Update config files from version 10.5.0 to 11.0.0
 */

namespace OpenXdmod\Migration\Version1050To1100;

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
