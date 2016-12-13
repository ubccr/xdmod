<?php
/**
 * Migrate config files from version 4.5.0 to 4.5.1.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Migration\Version450To451;

use Exception;
use Xdmod\Template;
use xd_utilities;

/**
 * Update config files from version 4.5.0 to 4.5.1.
 */
class ConfigFilesMigration extends \OpenXdmod\Migration\ConfigFilesMigration
{

    /**
     * Execute the migration.
     */
    public function execute()
    {
        $settingsTemplate = new Template('portal_settings');

        $settingsTemplate->apply(array(
            'general_version' => '4.5.1',
        ));

        $sections = array(
            'general',
            'mailer',
            'reporting',
            'logger',
            'database',
            'datawarehouse',
            'shredder',
            'hpcdb',
            'slurm',
        );

        foreach ($sections as $section) {
            try {
                $options = xd_utilities\getConfigurationSection($section);
            } catch (Exception $e) {
                continue;
            }

            foreach ($options as $option => $value) {
                $settings[$section . '_' . $option] = $value;
            }
        }

        $settingsTemplate->apply($settings);

        $this->assertPortalSettingsIsWritable();

        $settingsTemplate->saveTo(CONFIG_PORTAL_SETTINGS);
    }
}
