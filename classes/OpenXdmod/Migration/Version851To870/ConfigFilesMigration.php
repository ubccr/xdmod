<?php
/**
 * Update config files from version 8.5.1 To 8.7.0.
 */

namespace OpenXdmod\Migration\Version851To870;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;

class ConfigFilesMigration extends AbstractConfigFilesMigration
{

    /**
     * Update portal_settings.ini with the new version number.
     */
    public function execute()
    {
        $this->assertPortalSettingsIsWritable();
        $this->writePortalSettingsFile(
            array(
                'cors_domains' => ''
            )
        );
    }
}
