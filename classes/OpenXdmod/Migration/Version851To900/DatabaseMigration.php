<?php
namespace OpenXdmod\Migration\Version851To900;

use Exception;
use OpenXdmod\Setup\Console;
use FilterListBuilder;
use CCR\DB;
use ETL\Utilities;
use Xdmod\SlurmGresParser;

/**
* Migrate databases from version 8.5.1 to 9.0.0.
*/
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    /**
     * Drop possibly corrupt storage realm filters and rebuild them.  Prompt
     * user and re-ingest slurm GPU data if desired.
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
                ['last-modified-start-date' => $lastModifiedStartDate]
            );
            $builder = new FilterListBuilder();
            $builder->setLogger($this->logger);
            $builder->buildRealmLists('Jobs');
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
            $gresParser = new SlurmGresParser();
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
}
