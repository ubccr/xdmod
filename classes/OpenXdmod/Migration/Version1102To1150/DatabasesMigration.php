<?php
/**
 * Update database from version 11.0.2 to 11.5.0
 */

namespace OpenXdmod\Migration\Version1102To1150;

use OpenXdmod\Migration\DatabasesMigration as AbstractDatabasesMigration;
use ETL\Utilities;
use CCR\DB;
use CCR\DB\MySQLHelper;

class DatabasesMigration extends AbstractDatabasesMigration
{
    public function execute()
    {
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
