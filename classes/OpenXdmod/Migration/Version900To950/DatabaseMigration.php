<?php
/**
 * Update database from version 9.0.0 To 9.5.0.
 */

namespace OpenXdmod\Migration\Version900To950;

use CCR\DB;
use ETL\Utilities;
use OpenXdmod\Setup\Console;

/**
* Migrate databases from version 9.0.0 to 9.5.0.
*/
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    public function execute()
    {
        parent::execute();

        $console = Console::factory();

        $dbh = DB::factory('datawarehouse');
        $mysql_helper = \CCR\DB\MySQLHelper::factory($dbh);

        if ($mysql_helper->tableExists('modw_cloud.event')) {

            Utilities::runEtlPipeline(
                ['cloud-migration-9-0-0_9-5-0'],
                $this->logger,
                [
                    'last-modified-start-date' => '2017-01-01 00:00:00'
                ]
            );
        }

        if ($mysql_helper->tableExists('modw_cloud.cloud_resource_specs')) {

            $staging_resource_sql = "SELECT
                        COUNT(*)
                    FROM
                        modw_cloud.staging_resource_specifications
                    GROUP BY
                        resource_id, hostname, fact_date
                    HAVING
                        COUNT(*) > 1";

            $staging_result = $dbh->query($staging_resource_sql);

            if(count($staging_result) > 0) {
                $console->displayMessage(<<<"EOT"
This version of Open XDMoD changes the schema on two tables related to cloud utilization metrics. It appears that
data in the table modw_cloud.staging_resource_specifications will violate these schema changes. The violation is that
there cannot be two rows with the same resource ID, hostname, and date. Before you next ingest your cloud resource
specification files you should either remove any extra rows or truncate this table and then shred and ingest all of
your cloud resource specification files.
EOT
                );
            }

            Utilities::runEtlPipeline(
                ['cloud-resource-specs-migration-9-0-0_9-5-0'],
                $this->logger,
                [
                    'last-modified-start-date' => '2017-01-01 00:00:00'
                ]
            );
        }
    }
}
