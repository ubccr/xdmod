<?php

namespace OpenXdmod\Migration\Version751To800;

use CCR\DB;
use OpenXdmod\DataWarehouseInitializer;
use FilterListBuilder;
use TimePeriodGenerator;
use OpenXdmod\Setup\Console;

/**
 * Migrate databases from version 7.5.1 to 8.0.0.
 */
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    /**
     * @see \OpenXdmod\Migration\Migration::execute
     **/
    public function execute()
    {
        parent::execute();

        $this->runEtl(
            array(
                'process-sections' => array(
                    'acls-import'
                )
            )
        );

        $this->updateTimePeriods();

        $this->logger->notice(
            "Data is going to be migrated away from modw.jobfact into\n" .
            "modw.job_records and modw.job_tasks, this might take a while.\n" .
            "approx 30k records / minute \n"
        );

        $this->runEtl(
            array(
                'process-sections' => array(
                    'jobs-xdw-bootstrap',
                )
            )
        );
        $dwi = new DataWarehouseInitializer(
            DB::factory('hpcdb'),
            DB::factory('datawarehouse')
        );
        $dwi->ingestAllHpcdb();
        $this->validateJobTasks();

        $console = Console::factory();
        $console->displayMessage(<<<"EOT"
There have been updates to aggregation statistics to make the data more accurate.
It is recommended that you reaggregate all jobs.  Depending on the amount of
data this could take multiple hours.
EOT
        );
        $runaggregation = $console->promptBool(
            'Do you want to run aggregation now?',
            false
        );
        if (true === $runaggregation) {
            $this->runEtl(
                array(
                    'process-sections' => array('jobs-xdw-aggregate'),
                    'last-modified-start-date' => date('Y-m-d', strtotime('2000-01-01')),
                )
            );
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
        else {
            $console->displayMessage(<<<"EOT"
Aggregation not run.  To do this yourself you will need to run the following command:
xdmod-ingestor --aggregate --last-modified-start-date '2000-01-01'
EOT
            );
        }
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
                "it can now be deleted.\n"
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

    private function runEtl($scriptOptions = array()){
        if(count($scriptOptions) < 0 || (!array_key_exists('process-sections', $scriptOptions) && !array_key_exists('actions', $scriptOptions))){
            throw new \Exception('ETL Pipeline / actions not given.');
        }
        $scriptOptions['chunk-size-days'] = 365;
        $scriptOptions['default-module-name'] = 'xdmod';
        if(empty($scriptOptions['start-date'])){
            $scriptOptions['start-date'] = date('Y-m-d', strtotime('2000-01-01'));
        }
        if(empty($scriptOptions['end-date'])){
            $scriptOptions['end-date'] = date('Y-m-d', strtotime('2038-01-18'));
        }
        if(empty($scriptOptions['last-modified-start-date'])){
            $scriptOptions['last-modified-start-date'] = date('Y-m-d');
        }

        $etlConfig = new \ETL\Configuration\EtlConfiguration(CONFIG_DIR . '/etl/etl.json', null, $this->logger, array('default_module_name' => $scriptOptions['default-module-name']));
        $etlConfig->initialize();
        \ETL\Utilities::setEtlConfig($etlConfig);
        $overseerOptions = new \ETL\EtlOverseerOptions($scriptOptions, $this->logger);
        $overseer = new \ETL\EtlOverseer($overseerOptions, $this->logger);
        if($scriptOptions['process-sections'][0] == 'hpcdb-xdw.ingest'){

        }
        $overseer->execute($etlConfig);
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
}
