<?php

namespace OpenXdmod\Migration\Version810To850;

use OpenXdmod\Setup\Console;

/**
 * Update config files from version 8.1.0 To 8.5.0
 */
class ConfigFilesMigration extends \OpenXdmod\Migration\ConfigFilesMigration
{
    /**
     * Execute the migration.
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
