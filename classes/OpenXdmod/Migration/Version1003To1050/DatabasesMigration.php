<?php
/**
 * Update database from version 10.0.3 to 10.5.0.
 */

namespace OpenXdmod\Migration\Version1003To1050;

use OpenXdmod\Migration\DatabasesMigration as AbstractDatabasesMigration;
use OpenXdmod\Setup\Console;
use CCR\DB;
use ETL\Utilities;

/**
 * Migrate databases from version 10.0.3 to 10.5.0.
 */
class DatabasesMigration extends AbstractDatabasesMigration
{
    public function execute()
    {
        parent::execute();

        $dbh = DB::factory('datawarehouse');
        $mysql_helper = \CCR\DB\MySQLHelper::factory($dbh);
        $pipelinesToRun = [];

        if ($mysql_helper->tableExists('modw_cloud.event')) {
            $pipelinesToRun[] = 'cloud-migration-10-0-3_10-5-0';
            $pipelinesToRun[] = 'cloud-state-pipeline';
        }

        foreach ($pipelinesToRun as $pipeline) {
            Utilities::runEtlPipeline(
                [$pipeline],
                $this->logger,
                ['last-modified-start-date' => '2017-01-01 00:00:00']
            );
        }
    }
}
