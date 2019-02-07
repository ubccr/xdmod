<?php
/**
 * @author Greg Dean <gmdean@buffalo.edu>
 */

namespace OpenXdmod\Migration\Version800To810;

use CCR\Json;
use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;

/**
 * Update config files from version 8.0.0 To 8.0.1.
 */
class ConfigFilesMigration extends AbstractConfigFilesMigration
{
    /**
     * Execute the migration.
     */
    public function execute()
    {
        // Add the 'provider' group_by if it does not already exist.
        $this->modifyCloudRoles();
    }

    public function modifyCloudRoles()
    {

        $rolesConfigFolder = $this->config->getPartialFilePaths('roles');

        $cloudFile = array_filter($rolesConfigFolder, function($file){
            if(basename($file) === 'cloud.json'){
              return $file;
            }
        });

        if(empty($cloudFile)){
          return;
        }

        $cloudRolesFile = Json::loadFile($cloudFile[0]);

            $person = array(
                'realm' => 'Cloud',
                'group_by' => 'person'
            );

            $username = array(
                'realm' => 'Cloud',
                'group_by' => 'username'

            );

            $cloudRolesFile['+roles']['+default']['+query_descripters'][] = $person;
            $cloudRolesFile['+roles']['+default']['+query_descripters'][] = $username;
            $cloudRolesFile['+roles']['+pub']['+query_descripters'][] = $person;
            $cloudRolesFile['+roles']['+pub']['+query_descripters'][] = $username;
            $this->writeJsonPartialConfigFile('roles', 'cloud', $cloudRolesFile);
    }
}
