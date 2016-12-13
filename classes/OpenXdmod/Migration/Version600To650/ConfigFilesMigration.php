<?php

namespace OpenXdmod\Migration\Version600To650;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;

/**
 * Update config files from version 6.0.0 to 6.5.0.
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
        $this->writePortalSettingsFile(array(
            'rest_basic_auth' => 'on',
        ));
    }
}
