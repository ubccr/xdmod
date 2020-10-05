<?php
/**
 * Update config files from version 9.0.0 To 9.5.0.
 */

namespace OpenXdmod\Migration\Version900To950;

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

        $console = Console::factory();

        $console->displayMessage(<<<"EOT"
This version of Open XDMoD switches from using PhantomJS to using Chromium for exporting and report generation
EOT
        );
        $console->displayBlankLine();
        $chromiumPath = $console->prompt(
            'Chromium Path:',
            is_executable('/usr/lib64/chromium-browser/headless_shell') ? '/usr/lib64/chromium-browser/headless_shell' : ''
        );

        $this->writePortalSettingsFile(
            array(
                'reporting_chromium_path' => $chromiumPath
            )
        );
        $this->writeModulePortalSettingsFiles();
    }
}
