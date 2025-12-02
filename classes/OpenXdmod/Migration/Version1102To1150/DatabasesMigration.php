<?php
/**
 * Update database from version 11.0.2 to 11.5.0
 */

namespace OpenXdmod\Migration\Version1102To1150;

use OpenXdmod\Migration\DatabasesMigration as AbstractDatabasesMigration;
use OpenXdmod\Shared\DatabaseHelper;
use ETL\Utilities;
use CCR\DB;
use CCR\DB\MySQLHelper;

class DatabasesMigration extends AbstractDatabasesMigration
{
    public function execute()
    {
        $dataWarehouseAdminCredentials = $this->requestMysqlAdminCredentials();

        // Create the modw_etl schema.
        DatabaseHelper::createDatabases(
            $dataWarehouseAdminCredentials['user'],
            $dataWarehouseAdminCredentials['pass'],
            array(
                'db_host' => \xd_utilities\getConfiguration('datawarehouse', 'host'),
                'db_port' => \xd_utilities\getConfiguration('datawarehouse', 'port'),
                'db_user' => \xd_utilities\getConfiguration('datawarehouse', 'user'),
                'db_pass' => \xd_utilities\getConfiguration('datawarehouse', 'pass'),
            ),
            array('modw_etl')
        );

        parent::execute();

        $dbh = DB::factory('datawarehouse');
        $mysql_helper = MySQLHelper::factory($dbh);

        if ($mysql_helper->tableExists('modw.storagefact')) {
            Utilities::runEtlPipeline(
                ['storage-migration-11_0_2-11_5_0', 'xdw-aggregate-storage'],
                $this->logger,
                ['last-modified-start-date' => '2017-01-01 00:00:00']
            );
        }
    }
}
