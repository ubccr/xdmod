<?php
/**
 * Update database from version 10.5.1 to 11.0.0
 */

namespace OpenXdmod\Migration\Version1051To1100;

use OpenXdmod\Migration\DatabasesMigration as AbstractDatabasesMigration;
use CCR\DB;
use CCR\DB\MySQLHelper;
use ETL\Utilities;

/**
 * Migrate databases from version 10.5.1 to 11.0.0
 */
class DatabasesMigration extends AbstractDatabasesMigration
{
    public function execute()
    {
        parent::execute();

        $dbh = DB::factory('datawarehouse');
        $mysql_helper = MySQLHelper::factory($dbh);

        if ($mysql_helper->tableExists('modw_cloud.event')) {
            Utilities::runEtlPipeline(
                ['cloud-migration-10_5_1-11_0_0'],
                $this->logger,
                ['last-modified-start-date' => '2017-01-01 00:00:00']
            );
        }
    }
}
