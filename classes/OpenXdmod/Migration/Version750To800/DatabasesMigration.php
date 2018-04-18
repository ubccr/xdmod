<?php
namespace OpenXdmod\Migration\Version750To800;

use CCR\DB;
use FilterListBuilder;
use TimePeriodGenerator;
use  OpenXdmod\Ingestor\Staging\ResourceTypes;

/**
 * Migrate databases from version 7.5.0 to 8.0.0.
 */
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    /**
     * @see \OpenXdmod\Migration\Migration::execute
     **/
    public function execute()
    {
        parent::execute();

        $this->updateResourceTypes();
        $this->updateTimePeriods();
        $this->runEtlPipeline('jobs-xdw.bootstrap');
        $this->logger->notice(
            "Data is going to be migrated away from modw.jobfact into\n" .
            "modw.job_records and modw.jobtasks, this might take a while.\n" .
            "approx 30k records / minute \n"
        );
        $this->runEtlPipeline('hpcdb-xdw.ingest');
        $this->validateJobTasks();
        $this->logger->notice('Rebuilding filter lists');
        try {
            $builder = new FilterListBuilder();
            $builder->setLogger($this->logger);
            $builder->buildAllLists();
        } catch (Exception $e) {
            $this->logger->notice('Failed BuildAllLists: '  . $e->getMessage());
            $this->logger->crit(array(
                'message'    => 'Filter list building failed: ' . $e->getMessage(),
                'stacktrace' => $e->getTraceAsString(),
            ));
            throw new \Exception('Filter list building failed: ' . $e->getMessage());
        }
        $this->logger->notice('Done building filter lists');
    }

    private function validateJobTasks(){
        $this->logger->notice('Validating Job Tasks');
        $dbh = DB::factory('datawarehouse');
        $results = $dbh->query(
            "SELECT
                count(job_tasks.`job_id`) AS tasks,
                count(jobfact.`job_id`) AS facts
            FROM
                job_tasks,
                jobfact;"
        );
        if($results[0]['facts'] === $results[0]['tasks']){
            $this->logger->notice(
                "Migration Complete.\n\tThe modw.jobfact table is no longer needed " .
                "it can now be deleted.\n" .
                "\t It is suggested you run a full reaggregation of your data.\n"
            );
        }
        else {
            $this->logger->err(
                "Migration FAILED!!\n" .
                print_r($results, true)
            );
            throw new \Exception(
                "Migration FAILED!!\n" .
                print_r($results, true)
            );
        }
    }

    private function runEtlPipeline($name = null){
        if(empty($name)){
            throw new \Exception('ETL Pipeline not given');
        }
        $start = new \DateTime('2000-01-01');
        $end = new \DateTime('2038-01-18');

        $command = 'php ' . DATA_DIR . '/tools/etl/etl_overseer.php '
            . ' -p ' . $name . ' -s ' . $start->format('Y-m-d')
            . ' -e ' . $end->format('Y-m-d') . ' -k year';
        $pipes = array();
        $this->logger->notice("Executing $command");
        $process = proc_open(
            $command,
            array(
                0 => array('file', '/dev/null', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w'),
            ),
            $pipes
        );
        if (!is_resource($process)) {
            $this->logger->err('Unable execute command: ' . $command . "\n" . print_r(error_get_last(), true));
            throw new \Exception('Unable execute command: ' . $command . "\n" . print_r(error_get_last(), true));
        }
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        foreach($pipes as $pipe){
            fclose($pipe);
        }
        $return_value = proc_close($process);
        if ($return_value != 0) {
            $this->logger->err("$command returned $return_value, stdout:  $out stderr: $err");
            throw new \Exception("$command returned $return_value, stdout:  $out stderr: $err");
        }
        $this->logger->notice("Execution Complete: $command");
    }

    private function updateTimePeriods(){
        $aggregationUnits = array(
            'day',
            'month',
            'quarter',
            'year'
        );

        foreach ($aggregationUnits as $aggUnit) {
            $tpg = TimePeriodGenerator::getGeneratorForUnit($aggUnit);
            $tpg->generateMainTable(DB::factory('datawarehouse'));
        }
    }

    /**
     * Update resource types in the mod_hpcdb database.
     */
    private function updateResourceTypes()
    {
        $shredderDb = DB::factory('shredder');
        $hpcdbDb    = DB::factory('hpcdb');
        $ingestor = new ResourceTypes($hpcdbDb, $shredderDb);
        $ingestor->setLogger($this->logger);
        $ingestor->ingest();
    }
}
