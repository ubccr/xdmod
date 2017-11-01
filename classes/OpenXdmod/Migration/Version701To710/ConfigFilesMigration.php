<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Migration\Version701To710;

use CCR\Json;
use OpenXdmod\Migration\ConfigFilesMigration as AbstractConfigFilesMigration;

/**
 * Update config files from version 7.0.1 To 7.1.0.
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

        // Set new options in portal_settings.ini.
        $this->writePortalSettingsFile();

        // Update the roles files
        $this->updateRoles();

        // Add the 'provider' group_by if it does not already exist.
        $this->modifyDatawarehouse();
    }

    public function updateRoles()
    {
        $files = $this->getSourceFiles();
        foreach ($files as $file) {
            $json = $this->modifyFile($file);
            $this->writeFile($file, $json);
        }
    }

    public function modifyFile($file)
    {

        $this->assertFileIsWritable($file);
        $json = Json::loadFile($file);

        $roleKeys = $this->getFiltersFor('roles');
        $foundRoleKeys = array_intersect_assoc(array_keys($json), $roleKeys);
        if (count($foundRoleKeys) < 1) {
            // return the unmodified json so that it can be written back out.
            return $json;
        }

        $roleKey = array_shift($foundRoleKeys);
        $roles = $json[$roleKey];

        $modifications = $this->getModifications();

        foreach ($roles as $role => $roleDef) {
            if (array_key_exists($role, $modifications)) {
                $roleModifications = $modifications[$role];
                $missingProperties = array_diff(
                    array_keys($roleModifications),
                    array_keys($roleDef)
                );
                if (count($missingProperties) < 1) {
                    continue;
                }
                foreach ($missingProperties as $missing) {
                    $json[$roleKey][$role][$missing] = $roleModifications[$missing];
                }
            }
        }

        return $json;
    }

    /**
     * Helper function that retrieves a list of the file paths this migration
     * operates on ('roles').
     *
     * @return array
     * @throws Exception
     */
    public function getSourceFiles()
    {
        return array_merge(
            array(
                $this->config->getFilePath('roles')
            ),
            $this->config->getPartialFilePaths('roles')
        );
    }

    /**
     * Helper function that just helps with defining filters for a given term.
     *
     * @param  string $term
     * @return array
     */
    public function getFiltersFor($term)
    {
        return array(
            $term,
            "+$term"
        );
    }

    public function getModifications()
    {
        return $this->modifications;
    }

    private $modifications = array(
        'pub' => array(
            'hierarchies' => array(
                array(
                    'level' => 0,
                    'filter_override' => false
                )
            )
        ),
        'usr' => array(
            'hierarchies' => array(
                array(
                    'level' => 100,
                    'filter_override' => false
                )
            )
        ),
        'cd' => array(
            'requires' => array('provider'),
            'hierarchies' => array(
                array(
                    'level' => 400,
                    'filter_override' => false
                )
            )
        ),
        'pi' => array(
            'hierarchies' => array(
                array(
                    'level' => 200,
                    'filter_override'=> false
                )
            )
        ),
        'cs' => array(
            'requires' => array('provider'),
            'hierarchies' => array(
                array(
                    'level' => 300,
                    'filter_override' => false
                )
            )
        ),
        'cc' => array(
            'requires' => array('provider'),
            'hierarchies' => array(
                array(
                    'level' => 201,
                    'filter_override'=> false
                )
            )
        ),
        'po' => array(
            'hierarchies' => array(
                array(
                    'level' => 500,
                    'filter_override' => false
                )
            )
        ),
        'mgr' => array(
            'hierarchies' => array(
                array(
                    'level' => 301,
                    'filter_override' => false
                )
            )
        )
    );

    protected function writeFile($filePath, array $data)
    {
        $json = Json::prettyPrint(json_encode($data));
        if (file_put_contents($filePath, $json) === false) {
            throw new Exception("Failed to write to file '$filePath'");
        }

        // Add the 'provider' group_by if it does not already exist.
        $this->modifyDatawarehouse();
    }

    public function modifyDatawarehouse()
    {
        $datawarehouseFile = $this->config->getFilePath('datawarehouse');
        $datawarehouse = Json::loadFile($datawarehouseFile);
        $realms = isset($datawarehouse['realms']) ? $datawarehouse['realms'] : array();
        $jobs = isset($realms['Jobs']) ? $realms['Jobs'] : array();
        $groupBys = isset($jobs['group_bys']) ? $jobs['group_bys'] : array();

        $found = array_filter(
            $groupBys,
            function ($groupBy) {
                return isset($groupBy['name']) && $groupBy['name'] === 'provider';
            }
        );
        if (empty($found)) {
            $groupBys[] = array(
                'name' => 'provider',
                'class' => 'GroupByProvider'
            );
            $datawarehouse['realms']['Jobs']['group_bys'] = $groupBys;
            $this->writeJsonConfigFile('datawarehouse', $datawarehouse);
        }
    }
}
