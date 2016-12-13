<?php

namespace OpenXdmod\Migration\Version550To560;

use OpenXdmod\Setup\Console;
use OpenXdmod\Shared\DatabaseHelper;

/**
 * Migrate databases from version 5.5.0 to 5.6.0.
 */
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        $console = Console::factory();
        $console->displaySectionHeader('Database Migration');
        $console->displayBlankLine();

        // Request all admin credentials required by this migration.
        $dataWarehouseAdminCredentials = $this->requestMysqlAdminCredentials();

        // Create the modw_filters schema.
        DatabaseHelper::createDatabases(
            $dataWarehouseAdminCredentials['user'],
            $dataWarehouseAdminCredentials['pass'],
            array(
                'db_host' => \xd_utilities\getConfiguration('datawarehouse', 'host'),
                'db_port' => \xd_utilities\getConfiguration('datawarehouse', 'port'),
                'db_user' => \xd_utilities\getConfiguration('datawarehouse', 'user'),
                'db_pass' => \xd_utilities\getConfiguration('datawarehouse', 'pass'),
            ),
            array('modw_filters')
        );

        // Perform standard database migration tasks.
        parent::execute();

        // Display a warning about filter lists being initially empty unless
        // action is taken.
        $console->displayWarning(array(
            'WARNING: Filter lists will not work until data aggregation next runs.',
            '',
            'To restore filter lists without performing aggregation, you can run:',
            '',
            '    xdmod-ingestor --build-filter-lists',
            '',
            '(This command does not need to be run regularly.',
            'Filter lists are automatically built each time',
            'data ingestion and aggregation occurs.)',
        ));
    }
}
