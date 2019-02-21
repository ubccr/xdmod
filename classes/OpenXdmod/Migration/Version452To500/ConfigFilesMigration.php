<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Migration\Version452To500;

use Configuration\XdmodConfiguration;
use Exception;
use Xdmod\Template;
use xd_utilities;

/**
 * Update config files from version 4.5.2 to 5.0.0.
 */
class ConfigFilesMigration extends \OpenXdmod\Migration\ConfigFilesMigration
{

    /**
     * Execute the migration.
     */
    public function execute()
    {

        // Make sure all the config files that will be changed are
        // writable.
        $this->assertPortalSettingsIsWritable();
        $this->assertJsonConfigIsWritable('datawarehouse');
        $this->assertJsonConfigIsWritable('roles');

        $configFile = new XdmodConfiguration(
            'datawarehouse.json',
            CONFIG_DIR,
            $this->logger,
            array(
                'local_config_dir' => implode(
                    DIRECTORY_SEPARATOR,
                    array(
                        CONFIG_DIR,
                        'datawarehouse.d'
                    )
                )
            )
        );
        $configFile->initialize();

        $datawarehouse = $configFile->toAssocArray();

        if (!isset($datawarehouse['realms'])) {
            $newDatawarehouse = array('realms' => array());

            foreach ($datawarehouse as $realm) {
                $realmName = $realm['realm'];
                unset($realm['realm']);
                $newDatawarehouse['realms'][$realmName] = $realm;
            }

            $this->writeJsonConfigFile('datawarehouse', $newDatawarehouse);
        }

        // Update roles.json to the new format as necessary.  The roles
        // may already have been updated by the RPM manager if it has
        // never been changed.
        $roleConfigFile = new XdmodConfiguration(
            'roles.json',
            CONFIG_DIR,
            $this->logger,
            array(
                'local_config_dir' => implode(
                    DIRECTORY_SEPARATOR,
                    array(
                        CONFIG_DIR,
                        'roles.d'
                    )
                )
            )
        );
        $roles = $roleConfigFile->toAssocArray();

        // The first change replaces the top level array with an object
        // containing the key "roles" and an object containing all of
        // the roles.  Each role then identified by it's key which is
        // then removed from the object for that role.
        //
        // Before:
        // [
        //     {
        //         "abbrev": "roleName",
        //         ...
        //     },
        //     ...
        // ]
        //
        // After:
        // {
        //     "roles": {
        //         "roleName": {
        //             ...
        //         },
        //         ...
        //     }
        // }
        if (!isset($roles['roles'])) {
            $newRoles = array('roles' => array());

            foreach ($roles as $role) {
                $roleName = $role['abbrev'];
                unset($role['abbrev']);
                $newRoles['roles'][$roleName] = $role;
            }
        } else {
            $newRoles = $roles;
        }

        // The second change adds the relative position of each module
        // contained in the role.  This position determines the
        // placement of the module's tab in the top level tab bar.
        $positions = array(
            'tg_summary'       => 100,
            'tg_usage'         => 200,
            'metric_explorer'  => 300,
            'report_generator' => 1000,
            'about_xdmod'      => 10000,
        );

        foreach ($newRoles['roles'] as $role => $roleDef) {
            if (!isset($roleDef['permitted_modules'])) {
                continue;
            }

            foreach ($roleDef['permitted_modules'] as $i => $module) {
                if (isset($module['position'])) {
                    continue;
                }

                $moduleName = $module['name'];

                $position
                    = isset($positions[$moduleName])
                    ? $positions[$moduleName]
                    : 999999;

                $newRoles['roles'][$role]['permitted_modules'][$i]['position']
                    = $position;
            }
        }

        $this->writeJsonConfigFile('roles', $newRoles);

        // Set new options in portal_settings.ini.
        $this->writePortalSettingsFile(array(
            'general_sql_debug_mode' => '',
            'rest_base'              => '/rest/',
            'rest_version'           => 'v1',
            'auto_login_tabs'        => 'app_kernels',
        ));
    }
}
