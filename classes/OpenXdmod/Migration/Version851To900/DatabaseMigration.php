<?php
namespace OpenXdmod\Migration\Version851To900;

use Exception;
use OpenXdmod\Setup\Console;
use FilterListBuilder;
use CCR\DB;
use ETL\Utilities;
use Xdmod\SlurmResourceParser;

/**
* Migrate databases from version 8.5.1 to 9.0.0.
*/
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    /**
     * Update batch export request realm names, rebuild storage filters lists.
     * Prompt user and re-ingest slurm GPU data if desired.
     */
    public function execute()
    {
        parent::execute();

        $dbh = DB::factory('datawarehouse');
        $dbh->execute("UPDATE moddb.batch_export_requests SET realm = 'Jobs' WHERE realm = 'jobs'");
        $dbh->execute("UPDATE moddb.batch_export_requests SET realm = 'SUPREMM' WHERE realm = 'supremm'");

        $this->logger->notice('Rebuilding filter lists');
        try {
            $builder = new FilterListBuilder();
            $builder->setLogger($this->logger);
            $builder->buildRealmLists('Storage');
            $this->logger->notice('Done building filter lists');
        } catch (Exception $e) {
            $this->logger->warning('Failed to build filter list: '  . $e->getMessage());
            $this->logger->warning('You may need to run xdmod-build-filter-lists manually');
        }

        $console = Console::factory();
        $console->displayMessage(<<<"EOT"
This version of Open XDMoD has support for GPU metrics in the jobs realm.  If
you have shredded Slurm job records in the past it is possible to extract the
GPU count from the ReqGRES data that was collected.  This will require
re-ingest and re-aggregating your job data.
EOT
        );
        $console->displayBlankLine();
        $reingestSlurmData = $console->prompt(
            'Re-ingest and re-aggregate Slurm job data?',
            'yes',
            ['yes', 'no']
        );

        if ($reingestSlurmData === 'yes') {
            Utilities::runEtlPipeline(['jobs-gpu-migration-8_5_1-9_0_0'], $this->logger);
            $this->updateSlurmGpuCount();
            // Use current time from the database in case clocks are not
            // synchronized.
            $lastModifiedStartDate = DB::factory('hpcdb')->query('SELECT NOW() AS now FROM dual')[0]['now'];
            Utilities::runEtlPipeline(
                ['jobs-gpu-re-ingest-8_5_1-9_0_0', 'jobs-xdw-aggregate'],
                $this->logger,
                [
                    'last-modified-start-date' => $lastModifiedStartDate,
                    'option-overrides' => [
                        'analyze_table' => false
                    ]
                ]
            );
            $builder = new FilterListBuilder();
            $builder->setLogger($this->logger);
            $builder->buildRealmLists('Jobs');
        }
        if (\CCR\DB\MySQLHelper::DatabaseExists($dbh->_db_host, $dbh->_db_port, $dbh->_db_username, $dbh->_db_password, 'modw_cloud')) {
            $sql = "SELECT
                      r.name,
                    	sr.start_time,
                      sr.end_time,
                      i.provider_identifier,
                      COALESCE(sa.username, 'Unknown') as username
                    FROM
                    	modw_cloud.session_records as sr
                    LEFT JOIN
                    	modw_cloud.instance as i on sr.instance_id = i.instance_id
                    LEFT JOIN
                    	modw.systemaccount as sa on sr.person_id = sa.person_id
                    LEFT JOIN
                    	modw.resourcefact as r on sr.resource_id = r.id
                    WHERE
                      sr.start_event_type_id IN (4,6,17,19,45,55)";

            $erroneousSessions = $dbh->query($sql);
            $numSessions = count($erroneousSessions);

            if (!empty($erroneousSessions)) {
                $sessionTable = $this->makeAsciiSessionTable($erroneousSessions);
                $console->displayMessage(<<<"EOT"
This version of Open XDMoD fixes a bug in the cloud realm that created erroneous session. We have found $numSessions erroneous sessions.

$sessionTable
EOT
                    );
                $console->displayBlankLine();
                $deleteErroneousSessions = $console->prompt(
                    'Would you like to remove these sessions and re-aggregate your cloud data',
                    'yes',
                    ['yes', 'no']
                );

                // We only need to delete the rows here. Re-aggregations is done in configuration/etl/etl.d/xdmod-migration-8_5_1-9_0_0.json
                if ($deleteErroneousSessions) {
                    $dbh->execute("DELETE FROM modw_cloud.session_records WHERE start_event_type_id IN (4,6,17,19,45,55)");
                    $dbh->execute("DELETE FROM modw_cloud.event_reconstructed where start_event_id IN (4,6,17,19,45,55)");
                }
            }
        }
    }

    /**
     * Update the GPU count for all slurm records in
     * `mod_shredder`.`shredded_job_slurm` that have ReqGRES data.
     */
    private function updateSlurmGpuCount()
    {
        $dbh = DB::factory('shredder');
        $dbh->beginTransaction();
        try {
            $this->logger->notice('Querying slurm job records');
            $rows = $dbh->query("SELECT shredded_job_slurm_id AS id, req_gres FROM shredded_job_slurm WHERE req_gres != ''");
            $this->logger->notice('Updating slurm job records');
            $sth = $dbh->prepare('UPDATE shredded_job_slurm SET ngpus = :gpuCount WHERE shredded_job_slurm_id = :id');
            $gresParser = new SlurmResourceParser();
            foreach ($rows as $row) {
                $gres = $gresParser->parseReqGres($row['req_gres']);
                $gpuCount = $gresParser->getGpuCountFromGres($gres);
                $sth->execute(['gpuCount' => $gpuCount, 'id' => $row['id']]);
            }
            $dbh->commit();
            $this->logger->notice('Done updating slurm job records');
        } catch (Exception $e) {
            $dbh->rollBack();
            $this->logger->err('Failed to update slurm job records: '  . $e->getMessage());
        }
    }

    /**
     * Create a dashed line the same width as the ascii table.
     *
     * @param $tableWidth int Width of ascii table
     * @return String
     */
    private function asciiLineSeperators($tableWidth)
    {
        return "+ ".str_pad('', $tableWidth, '-')." +\r\n";
    }

    /**
     * Makes an ascii table that show details of sessions that were erroneously created.
     * Shows the Resource, Start Time, End Time, Instance UUID and Username associated with session.
     *
     * @param $tableData Multidimensional array that should contain data for the ascii table
     * @return String
     */
    private function makeAsciiSessionTable($tableData)
    {
        $tableData = array_merge(
            [['name' => 'Resource','start_time' => 'Start Time','end_time' => 'End Time','provider_identifier' => 'Instance', 'username' => 'Username']],
            $tableData
        );

        $widths = $this->getSessionTableColumnWidths($tableData);
        $tableWidth = array_sum($widths) + 17;
        $asciiTable = $this->asciiLineSeperators($tableWidth);

        foreach ($tableData as $key => $result) {
            foreach ($result as $index => $value) {
                //Format that values of the table so that all values in a column are padded to the same length
                $strFormat = ($index == 'name') ? "| %-".$widths[$index]."s  |" : " %-".$widths[$index]."s  |";
                $asciiTable .= sprintf($strFormat, $value);
            }
            $asciiTable .= "\r\n";
            if ($key === 0) {
                $asciiTable .= $this->asciiLineSeperators($tableWidth);
            }
        }

        $asciiTable .= $this->asciiLineSeperators($tableWidth);

        return $asciiTable;
    }

    /**
     * Returns an array with the max width of a string for each column in the multidimensional array that is passed in
     *
     * @param $tableData Multidimensial array that should contain data for the ascii table
     * @return array
     */
    private function getSessionTableColumnWidths($tableData)
    {
        $widths = array_fill_keys(array_keys($tableData[0]), 0);

        foreach ($tableData as $row) {
            foreach ($row as $key => $value) {
                if (strlen($value) > $widths[$key]) {
                    $widths[$key] = strlen($value);
                }
            }
        }

        return $widths;
    }
}
