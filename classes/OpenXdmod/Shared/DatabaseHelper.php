<?php

namespace OpenXdmod\Shared;

use CCR\DB;
use CCR\DB\MySQLHelper;
use OpenXdmod\Setup\Console;

/**
 * Performs interactive tasks to help setup databases.
 */
class DatabaseHelper
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
     * @param Console $console (Optional) The console to use to prompt the user.
     *                         If not provided, one will be obtained.
     */

    public static function createDatabases(
        $username,
        $password,
        array $settings,
        array $databases,
        Console $console = null
    ) {
        if ($console === null) {
            $console = Console::factory();
        }

        $rows = MySQLHelper::userExists(
            $settings['db_host'],
            $settings['db_port'],
            $username,
            $password,
            $settings['db_user'],
            $settings['xdmod_host']
        );
        $console->displayMessage(
                'rows retuned' . $rows
        );
        if ($rows == false) {
            $console->displayMessage(
                'Creating User ' . $settings['db_user']
            );
            $console->displayMessage(
                'Creating User with ' . $settings['db_user'] . ' on host ' . $settings['xdmod_host'] . ' with password ' . $settings['db_pass']
            );
            MySQLHelper::staticExecuteStatement(
                $settings['db_host'],
                $settings['db_port'],
                $username,
                $password,
                null,
                sprintf(
                    "CREATE USER '%s'@'%s' IDENTIFIED BY '%s';",
                    $settings['db_user'],
                    $settings['xdmod_host'],
                    $settings['db_pass'],
                )
            );
            $console->displayMessage(
                'Created User'
            );
        }
        MySQLHelper::grantAllPrivileges(
            $settings['db_host'],
            $settings['db_port'],
            $username,
            $password,
            $settings['xdmod_host'],
            $settings['db_user'],
            $settings['db_pass']
        );
        foreach ($databases as $database) {
            $console->displayBlankLine();

            if (
                MySQLHelper::databaseExists(
                    $settings['db_host'],
                    $settings['db_port'],
                    $username,
                    $password,
                    $database
                )
            ) {
                $console->displayMessage(
                    "Database `$database` already exists."
                );
                $drop = $console->prompt(
                    'Drop and recreate database?',
                    'no',
                    array('yes', 'no')
                );
                if ($drop == 'yes') {
                    $console->displayMessage(
                        "Dropping database `$database`."
                    );
                    MySQLHelper::dropDatabase(
                        $settings['db_host'],
                        $settings['db_port'],
                        $username,
                        $password,
                        $database
                    );
                } else {
                    continue;
                }
            }

            $console->displayMessage("Creating database `$database`.");
            MySQLHelper::createDatabase(
                $settings['db_host'],
                $settings['db_port'],
                $username,
                $password,
                $database
            );

            $console->displayMessage(
                "Granting privileges on database `$database`."
            );
            MySQLHelper::grantAllPrivilegesOnDatabase(
                $settings['db_host'],
                $settings['db_port'],
                $username,
                $password,
                $database,
                $settings['xdmod_host'],
                $settings['db_user'],
                $settings['db_pass']
            );

            $console->displayMessage(
                "Initializing database `$database`."
            );
            self::mysqlImportData($settings, $database);
        }
    }

    /**
     * Import database files.
     *
     * @param array $settings
     * @param string $db The database name.
     */
    private static function mysqlImportData(array $settings, $db)
    {
        $schemaPath = BASE_DIR . "/db/schema/$db.sql";

        if (file_exists($schemaPath)) {
            MySQLHelper::staticExecuteFile(
                $settings['db_host'],
                $settings['db_port'],
                $settings['db_user'],
                $settings['db_pass'],
                $db,
                $schemaPath
            );
        }

        // Import any data if necessary.
        $dataPath = BASE_DIR . "/db/data/$db.sql";

        if (file_exists($dataPath)) {
            MySQLHelper::staticExecuteFile(
                $settings['db_host'],
                $settings['db_port'],
                $settings['db_user'],
                $settings['db_pass'],
                $db,
                $dataPath
            );
        }
    }
}
