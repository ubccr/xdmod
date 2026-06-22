<?php
/**
 * Update database from version 11.0.3 to 11.0.4
 */

namespace OpenXdmod\Migration\Version1103To1104;

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

        if ($mysql_helper->tableExists('modw_cloud.event')) {
            Utilities::runEtlPipeline(
                ['move-disk-gb-property', 'cloud-state-pipeline'],
                $this->logger,
                ['last-modified-start-date' => '2017-01-01 00:00:00']
            );
        }
    }
}
