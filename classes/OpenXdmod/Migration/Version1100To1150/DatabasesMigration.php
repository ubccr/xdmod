<?php
/**
 * Update database from version 11.0.0 to 11.5.0
 */

namespace OpenXdmod\Migration\Version1100To1150;

use OpenXdmod\Migration\DatabasesMigration as AbstractDatabasesMigration;
use CCR\DB;
use CCR\DB\MySQLHelper;
use ETL\Utilities;

/**
 * Migrate databases from version 11.0.0 to 11.5.0
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
                ['cloud-migration-11-0-0_11-5-0','cloud-state-pipeline'],
                $this->logger,
                ['last-modified-start-date' => '2016-01-01 00:00:00']
            );
        }
    }
}
