<?php
/**
 * Update config files from version 8.5.1 To 9.0.0.
 */
namespace OpenXdmod\Migration\Version851To900;

use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;

/**
 * Update portal_settings.ini with the new version number and update roadmap
 * URL.
 */
class ConfigFilesMigration extends AbstractConfigFilesMigration
{
    public function execute()
    {
        $this->assertPortalSettingsIsWritable();
        $data = parse_ini_file($this->portalSettingsPath, true);
        $roadmapUrl = 'https://trello.com/b/mdFESh6j.html';
        if('https://trello.com/embed/board?id=mdFESh6j' !== $data['roadmap']['url']){
            $roadmapUrl = $data['roadmap']['url'];
        }
        $this->writePortalSettingsFile(
            array(
                'roadmap_url' => $roadmapUrl,
                'cors_domains' => ''
            )
        );

        $this->writeModulePortalSettingsFiles();
    }
}
