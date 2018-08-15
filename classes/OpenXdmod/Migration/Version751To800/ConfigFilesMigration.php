<?php

namespace OpenXdmod\Migration\Version751To800;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;

/**
 * Update config files from version 7.5.1 to 8.0.0.
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

        $organization = $this->config['organization'];

        // Set new options in portal_settings.ini.
        $this->writePortalSettingsFile(array(
            'sso_default_organization_name' => $organization['name'],
            'sso_force_default_organization' => 'on',
            'sso_email_admin_unknown_org' => 'on'
        ));
    }
}
