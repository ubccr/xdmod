<?php
/**
 * Update database from version 11.0.0 to 11.0.1
 */

namespace OpenXdmod\Migration\Version1100To1101;

use OpenXdmod\Migration\DatabasesMigration as AbstractDatabasesMigration;
use CCR\DB;
use CCR\DB\MySQLHelper;
use ETL\Utilities;

/**
 * Migrate databases from version 11.0.0 to 11.0.1
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
                ['cloud-migration-11_0_0-11_0_1','cloud-state-pipeline'],
                $this->logger,
                ['last-modified-start-date' => '2016-01-01 00:00:00']
            );
        }
    }
}
