<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Migration\Version701To710;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;

/**
 * Update config files from version 7.0.1 To 7.1.0.
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
