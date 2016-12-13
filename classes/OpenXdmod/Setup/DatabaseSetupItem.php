<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

use OpenXdmod\Shared\DatabaseHelper;

/**
 * Database setup item.
 */
abstract class DatabaseSetupItem extends SetupItem
{
    /**
     * Create the databases.
     *
     * @param string $username Admin username.
     * @param string $password Admin password.
     * @param array $settings
     *   - db_host => Database hostname
     *   - db_port => Database port number
     *   - db_user => Database username
     *   - db_pass => Database password
     * @param array $databases Database names.
     */
    protected function createDatabases(
        $username,
        $password,
        array $settings,
        array $databases
    ) {
        DatabaseHelper::createDatabases(
            $username,
            $password,
            $settings,
            $databases,
            $this->console
        );
    }
}
