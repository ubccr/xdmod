<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Migration\Version500To550;

use Exception;
use Xdmod\Template;
use xd_utilities;

/**
 * Update config files from version 5.0.0 to 5.5.0.
 */
class ConfigFilesMigration extends \OpenXdmod\Migration\ConfigFilesMigration
{

    /**
     * Execute the migration.
     */
    public function execute()
    {

        // Make sure all the config files that will be changed are
        // writable.
        $this->assertPortalSettingsIsWritable();

        // Set new options in portal_settings.ini.
        $this->writePortalSettingsFile();
    }
}
