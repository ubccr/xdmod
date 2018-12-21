<?php
/**
 * Abstract base class for migrating databases.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Migration;

use CCR\DB;
use CCR\DB\MySQLHelper;
use OpenXdmod\Setup\Console;
use xd_utilities;

abstract class DatabasesMigration extends Migration
{

    /**
     * Array of MySQLHelper objects.
     *
     * @param MySQLHelper[]
     */
    protected $databaseHelpers = array();

    /**
     * Admin credentials for the MySQL database.
     *
     * This saves the user from having to input the same credentials multiple
     * times if they are performing multiple migrations.
     *
     * @var array|null
     */
    protected static $mysqlAdminCredentials = null;

    /**
     * @inheritdoc
     */
    public function setLogger(\Log $logger)
    {
        parent::setLogger($logger);

        foreach ($this->databaseHelpers as $helper) {
            $helper->setLogger($logger);
        }
    }

    /**
     * Get a MySQL helper for the specified database.
     *
     * @param string $name Database config section name.
     *
     * @return MySQLHelper
     */
    protected function getDatabaseHelper($name)
    {
        if (!isset($this->databaseHelpers[$name])) {
            $db = DB::factory($name);
            $helper = MySQLHelper::factory($db);
            $helper->setLogger($this->logger);
            $this->databaseHelpers[$name] = $helper;
        }

        return $this->databaseHelpers[$name];
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {

        // Mapping of database names to their portal_settings.ini config
        // section.
        $databases = array(
            'mod_logger'      => 'logger',
            'moddb'           => 'database',
            'modw'            => 'datawarehouse',
            'modw_aggregates' => 'datawarehouse',
            'modw_filters'    => 'datawarehouse',
            'mod_shredder'    => 'shredder',
            'mod_hpcdb'       => 'hpcdb',
            'modw_etl'        => 'logger',
            'modw_supremm'    => 'datawarehouse',
            'modw_cloud'      => 'datawarehouse'
        );

        $dir = BASE_DIR . '/db/migrations/'
            . $this->currentVersion . '-' . $this->newVersion;

        foreach ($databases as $db => $section) {
            $file = "$dir/$db.sql";

            if (file_exists($file)) {
                $this->updateDatabase($section, $file);
            }
        }
    }

    /**
     * Update a database.
     *
     * @param string $section Database config section name.
     * @param string $file SQL file path to apply to the database.
     *
     * @throws Exception If an error occurs.
     */
    protected function updateDatabase($section, $file)
    {
        $helper = $this->getDatabaseHelper($section);

        $db = $helper->getDatabaseName();

        $this->logger->info("Updating database $db");

        $helper->executeFile($file);
    }

    /**
     * Request admin credentials for the MySQL database.
     *
     * This function remembers admin credentials until the end of execution.
     * If the database's credentials are requested after already being supplied,
     * they will be returned without prompting the user.
     *
     * This function may cause execution to stop, as the user may elect
     * to not input admin credentials.
     *
     * @return array The admin credentials for the database.
     */
    protected function requestMysqlAdminCredentials()
    {
        if (self::$mysqlAdminCredentials === null) {
            $console = Console::factory();
            $proceed = $console->prompt(
                "One or more migrations in this upgrade require admin credentials for the MySQL database. Would you like to proceed?",
                'yes',
                array('yes', 'no')
            ) === 'yes';
            if (!$proceed) {
                exit;
            }

            $user = $console->prompt("MySQL Admin Username:", 'root');
            $pass = $console->silentPrompt("MySQL Admin Password:");

            self::$mysqlAdminCredentials = array(
                'user' => $user,
                'pass' => $pass,
            );
        }

        return self::$mysqlAdminCredentials;
    }
}
