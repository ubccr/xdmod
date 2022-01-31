<?php
/**
 * Update database from version 9.5.0 To 10.0.0.
 */

namespace OpenXdmod\Migration\Version950To1000;

use OpenXdmod\Migration\DatabasesMigration as AbstractDatabasesMigration;
use OpenXdmod\Setup\Console;
use CCR\DB;
use ETL\Utilities;

/**
 * Migrate databases from version 9.5.0 to 10.0.0.
 */
class DatabasesMigration extends AbstractDatabasesMigration
{
    /**
     * Check whether a table exists in the datawarehouse. Note this creates (and destroys)
     * a new connection for the check. This mitigates problems with a persistent connection
     * timing out (which can happen if there is a long time between queries).
     * @param string $tableName the name of the table to check.
     * @return bool whether the table exists in the datawarehouse.
     */
    private function tableExists($tableName)
    {
        $dbh = DB::factory('datawarehouse');
        $mysql_helper = \CCR\DB\MySQLHelper::factory($dbh);
        $exists = $mysql_helper->tableExists($tableName);
        $mysql_helper = null;
        $dbh = null;
        return $exists;
    }

    public function execute()
    {
        parent::execute();

        $console = Console::factory();
        $pipelinesToRun = [];

        if ($this->tableExists('mod_shredder.staging_storage_usage')) {
            Utilities::runEtlPipeline(
                ['storage-table-definition-update-9-5-0_10-0-0'],
                $this->logger,
                ['last-modified-start-date' => '2017-01-01 00:00:00']
            );
        }

        if ($this->tableExists('modw_cloud.event')) {
            Utilities::runEtlPipeline(
                ['cloud-migration-9-5-0_10-0-0'],
                $this->logger,
                [
                    'last-modified-start-date' => '2017-01-01 00:00:00'
                ]
            );
        }

        if ($this->tableExists('modw_cloud.cloud_resource_specs')) {
            $pipelinesToRun[] = 'cloud-resource-specs-migration-9-5-0_10-0-0';
        }

        if ($this->tableExists('modw_cloud.event')) {
            $pipelinesToRun[] = 'cloud-migration-innodb-9-5-0_10-0-0';
        }

        $console->displayMessage(<<<"EOT"
This version of Open XDMoD converts any table with the MyISAM engine to InnoDB. This converstion may take some time to complete.
EOT
        );

        Utilities::runEtlPipeline(
            $pipelinesToRun,
            $this->logger,
            ['last-modified-start-date' => '2017-01-01 00:00:00']
        );
    }
}
