<?php
/**
 * Update config files from version 8.1.2 To 8.5.0.
 */

namespace OpenXdmod\Migration\Version812To850;

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

        $console = Console::factory();

        $console->displayMessage(<<<"EOT"
This release of XDMoD features an optional replacement for the summary
tab that is intended to provide easier access to XDMoD's many features
for new or inexperienced (novice) users. Detailed information is available
as https://open.xdmod.org/novice_user.html
EOT
        );
        $console->displayBlankLine();
        $novice_user = $console->prompt(
            'Enable Novice User Tab?',
            'off',
            array('on', 'off')
        );

        $this->writePortalSettingsFile(array(
            'features_novice_user' => $novice_user
        ));
    }
}
