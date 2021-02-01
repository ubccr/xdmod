<?php
namespace OpenXdmod\Migration\Version900To950;

use Exception;
use CCR\DB;
use ETL\Utilities;

/**
* Migrate databases from version 9.0.0 to 9.5.0.
*/
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    public function execute()
    {
        parent::execute();

        $dbh = DB::factory('datawarehouse');
        if (\CCR\DB\MySQLHelper::DatabaseExists($dbh->_db_host, $dbh->_db_port, $dbh->_db_username, $dbh->_db_password, 'modw_cloud')) {
            Utilities::runEtlPipeline(
                ['cloud-migration-9_0_0-9_5_0'],
                $this->logger,
                [
                    'last-modified-start-date' => '2017-01-01 00:00:00'
                ]
            );
        }
    }
}
