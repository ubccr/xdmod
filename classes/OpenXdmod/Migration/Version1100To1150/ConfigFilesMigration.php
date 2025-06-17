<?php
/**
 * Update config files from version 11.0.0 to 11.5.0
 */

namespace OpenXdmod\Migration\Version1100To1150;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;
use CCR\Json;

class ConfigFilesMigration extends AbstractConfigFilesMigration
{
    /**
     * Update portal_settings.ini with the new version number.
     */
    public function execute()
    {
        $this->assertPortalSettingsIsWritable();
        $this->assertModulePortalSettingsAreWritable();
        $this->assertJsonConfigIsWritable('organization');
        $this->assertJsonConfigIsWritable('resources');

        $organization_config = Json::loadFile(CONFIG_DIR . '/organization.json');
        $resources_config = Json::loadFile(CONFIG_DIR . '/resources.json');

        $organization_abbrev = $organization_config['abbrev'];
        $organization_config = [$organization_config];
        $this->writeJsonConfigFile('organization', $organization_config);

        $resource_config_with_organization = [];

        // Add the organization field to the resource.json file and use
        // the organization listed in organization.json.
        foreach($resources_config as $resource)
        {
            $resource['organization'] = $organization_abbrev;
            $resource_config_with_organization[] = $resource;
        }

        $this->writeJsonConfigFile('resources', $resource_config_with_organization);

        $this->writePortalSettingsFile();
        $this->writeModulePortalSettingsFiles();
    }
}
