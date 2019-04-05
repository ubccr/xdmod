<?php
/**
 * @author Greg Dean <gmdean@buffalo.edu>
 */

namespace OpenXdmod\Migration\Version800To810;

use CCR\Json;
use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;
use Exception;

/**
 * Update config files from version 8.0.0 To 8.0.1.
 */
class ConfigFilesMigration extends AbstractConfigFilesMigration
{

    private $cloudRolesFilePath;

    private $templateCloudRolesFile;

    public function __construct($currentVersion, $newVersion)
    {
        $this->templateCloudRolesFile = DATA_DIR . '/templates/roles.d/cloud.json';
        $this->cloudRolesFilePath = CONFIG_DIR . '/roles.d/cloud.json';
        parent::__construct($currentVersion, $newVersion);
    }

    /**
     * Execute the migration.
     */
    public function execute()
    {
        if (file_exists($this->cloudRolesFilePath)) {
            $this->addtoCloudRoles();
        }
        $this->assertPortalSettingsIsWritable();

        // Set new options in portal_settings.ini.
        $this->writePortalSettingsFile(array(
            'mailer_subject_prefix' => ''
        ));
    }

    /**
     * Adds new group bys to roles.d/cloud.json
     */
    public function addtoCloudRoles()
    {
        // Json::loadFile throws an exception if the file is completely empty or if there some other
        // problem loading the file. If those exceptions are thrown catch them so the rest of the
        // migration script can continue to run
        try{
            $cloudRolesFile = Json::loadFile($this->cloudRolesFilePath);
            $templateCloudRoles = Json::loadFile($this->templateCloudRolesFile);
        }
        catch(Exception $e){
            return false;
        }

        if (array_key_exists('+roles', $cloudRolesFile)) {
            foreach($cloudRolesFile['+roles'] as $key => $unused) {
                $cloudRolesFile['+roles'][$key]['+query_descripters'][] = array('realm' => 'Cloud', 'group_by' => 'person');
                $cloudRolesFile['+roles'][$key]['+query_descripters'][] = array('realm' => 'Cloud', 'group_by' => 'username');
                if ($key === '+default') {
                    $cloudRolesFile['+roles'][$key]['+summary_charts'] = $templateCloudRoles['+roles'][$key]['+summary_charts'];
                }
            }

            // An exception can be thrown if there is a problem writing the file. Catch and log the issue
            // while letting the rest of the migration script run
            try{
                JSON::saveFile($this->cloudRolesFilePath, $cloudRolesFile);
            }
            catch(Exception $e){
                $this->logger->notice("Unable to write to roles.d/cloud.json config file. Continuing upgrade");
                return false;
            }
        }

        return true;
    }
}
