<?php

namespace OpenXdmod\Migration\Version550To560;

use xd_utilities;
use CCR\Json;
use OpenXdmod\Setup\Console;

/**
 * Update config files from version 5.5.0 to 5.6.0.
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
        $this->assertModulePortalSettingsAreWritable();
        $this->assertJsonConfigIsWritable('roles');

        // Load the main roles config file.
        $mainRolesConfigFile = $this->config->getFilePath('roles');
        $mainRolesConfig = Json::loadFile($mainRolesConfigFile);

        // Add dimension associations to each known role.
        $dimensionAssociations = array(
            "usr" => array(
                "person"
            ),
            "cd" => array(
                "provider"
            ),
            "pi" => array(
                "pi"
            ),
            "cs" => array(
                "provider"
            ),
            "mgr" => array(
                "person"
            ),
        );
        foreach ($dimensionAssociations as $role => $roleDimensionAssociations) {
            if (
                !isset($mainRolesConfig['roles'][$role])
                || isset($mainRolesConfig['roles'][$role]['dimensions'])
            ) {
                continue;
            }

            $mainRolesConfig['roles'][$role]['dimensions'] = $roleDimensionAssociations;
        }

        $roadmapHeader = 'Located below is the XDMoD Development roadmap,'
            . ' organized by XDMoD release and powered by Trello.com. To view'
            . ' the full roadmap as well as vote and comment on features click'
            . ' any one of the elements on the roadmap.  This will take you to'
            . ' the full roadmap on the Trello.com site in a new browser window'
            . ' (or tab).  All users will be able to view the roadmap, however'
            . ' if you wish to vote or comment on a feature you will need to'
            . ' create a (free) Trello account if you do not already have one.';

        // Use the current site address to build the user manual URL.
        $siteAddress = xd_utilities\getConfiguration('general', 'site_address');
        $siteAddress = xd_utilities\ensure_string_ends_with($siteAddress, '/');
        $userManualUrl = $siteAddress . 'user_manual/';

        $settings = array(
            'reporting_javac_path' => exec('which javac 2>/dev/null'),
            'roadmap_url' => 'https://trello.com/b/mdFESh6j.html',
            'roadmap_header' => $roadmapHeader,
            'general_user_manual' => $userManualUrl,
        );

        $console = Console::factory();

        $console->displaySectionHeader('Configuration File Migration');
        $console->displayBlankLine();

        $console->displayMessage(<<<"EOT"
The previous version of Open XDMoD did not allow you to specify the path
of the javac executable that was used by the report generator.  Please
specify your preferred javac here.
EOT
        );
        $console->displayBlankLine();

        $settings['reporting_javac_path'] = $console->prompt(
            'Javac Path:',
            $settings['reporting_javac_path']
        );
        $console->displayBlankLine();

        // Set new options in portal_settings.ini.
        $this->writePortalSettingsFile($settings);

        $this->writeModulePortalSettingsFiles(array(
            'supremm' => array(
                'jobsummarydb_db_engine' => 'MongoDB',
                'jobsummarydb_uri' => 'mongodb://localhost:27017/supremm',
                'jobsummarydb_db' => 'supremm',
            ),
        ));

        // Set new options in the main roles config file.
        $this->writeJsonConfigFile('roles', $mainRolesConfig);
    }
}
