<?php
/**
 * Migrate config files from version 3.5.0 to 4.5.0.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Migration\Version350To450;

use Exception;
use Xdmod\Template;
use xd_utilities;

/**
 * Update config files from version 3.5.0 to 4.5.0.
 */
class ConfigFilesMigration extends \OpenXdmod\Migration\ConfigFilesMigration
{

    /**
     * Execute the migration.
     */
    public function execute()
    {

        // The resource specs (nodes, processors and ppn) are now stored
        // in a separate file to allow changes over time.

        $resources = $this->config['resources'];

        $newResources     = array();
        $newResourceSpecs = array();

        foreach ($resources as $key => $resource) {
            if ($key === 'meta') {
                continue;
            }

            $newResources[] = array(
                'resource'         => $resource['resource'],
                'resource_type_id' => $resource['resource_type_id'],
                'name'             => $resource['name'],
            );

            $newResourceSpecs[] = array(
                'resource'   => $resource['resource'],
                'nodes'      => $resource['nodes'],
                'processors' => $resource['processors'],
                'ppn'        => $resource['ppn'],
            );
        }

        // Make sure both config files are writable before attempting
        // to overwrite the existing config.

        $resourcesFile = $this->config->getFilePath('resources');

        if (!is_writable($resourcesFile)) {
            throw new Exception("Cannot write to file '$resourcesFile'");
        }

        $resourceSpecsFile
            = $this->config->getConfigDirPath() . '/' . 'resource_specs.json';

        // This file may not exist so create it.  Otherwise, the
        // writable check will always fail.
        if (!file_exists($resourceSpecsFile)) {
            file_put_contents($resourceSpecsFile, '');
        }

        if (!is_writable($resourceSpecsFile)) {
            throw new Exception("Cannot write to file '$resourceSpecsFile'");
        }

        $this->writeJsonConfigFile('resource_specs', $newResourceSpecs);
        $this->writeJsonConfigFile('resources', $newResources);

        $settingsTemplate = new Template('portal_settings');

        $settingsTemplate->apply(array(
            'general_version' => '4.5.0',
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

        // These settings were not included in the template for 3.5.0,
        // so they may not be present in the portal_settings.ini file.
        $settings = array(
            'slurm_sacct' => '',
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
