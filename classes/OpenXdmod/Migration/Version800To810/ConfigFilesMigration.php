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

    /**
     * Data from cloud.json file in CONFIG_DIR/roles.d folder
     */
    private $cloudRolesFile;

    /**
     * Execute the migration.
     */
    public function execute()
    {
        $this->setCloudRolesFile();
        $this->addCloudRolesGroupBy('person', '+pub');
        $this->addCloudRolesGroupBy('username', '+pub');
        $this->addCloudRolesGroupBy('person', '+default');
        $this->addCloudRolesGroupBy('username', '+default');
        $this->writeJsonPartialConfigFile('roles', 'cloud', $this->cloudRolesFile);
    }

    /**
     * Find cloud.json file in config directory and assign it to $this->clouRolesFile
     */
    private function setCloudRolesFile()
    {
        $rolesConfigFolder = $this->config->getPartialFilePaths('roles');

        if($cloudFile = array_search(CONFIG_DIR."/roles.d/cloud.json", $rolesConfigFolder) === false){
            throw new Exception("cloud.json file not found in roles.d folder");
        }

        $this->cloudRolesFile = Json::loadFile($rolesConfigFolder[$cloudFile]);
    }

    /**
     * Add a group by to a role in the cloud.json file
     *
     * @param $groupBy Name of group by that is being added
     * @param $role Role that group by is being added to
     */
    private function addCloudRolesGroupBy($groupBy, $role)
    {
        if(!array_key_exists($role, $this->cloudRolesFile['+roles'])){
            throw new Exception("Role not found in cloud.json file");
        }

        $group_bys_found = array_filter($this->cloudRolesFile['+roles'][$role]['+query_descripters'], function($descripters) use ($groupBy){
            if($descripters['group_by'] === $groupBy){
                return $descripters;
            }
        });

        if(empty($group_bys_found)){
            $this->cloudRolesFile['+roles'][$role]['+query_descripters'][] = array('realm' => 'Cloud', 'group_by' => $groupBy);
        }
    }
}
