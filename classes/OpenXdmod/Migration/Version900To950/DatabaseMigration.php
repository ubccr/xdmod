<?php
/**
 * Update database from version 9.0.0 To 9.5.0.
 */

namespace OpenXdmod\Migration\Version900To950;

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
        $mysql_helper = \CCR\DB\MySQLHelper::factory($dbh);
        if ($mysql_helper->tableExists('modw_cloud.cloud_resource_specs')) {
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
