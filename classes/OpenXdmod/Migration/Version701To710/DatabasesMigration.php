<?php

/**
 * @author Ryan Rathsam <ryanrath@buffalo.edu>
 **/
namespace OpenXdmod\Migration\Version701To710;

use CCR\DB;
use FilterListBuilder;
use TimePeriodGenerator;

/**
 * Ensure that the tables / data exists that will support the Acl subsystem
 * going into version 7.1.
 **/
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    /**
     * @see \OpenXdmod\Migration\Migration::__construct
     **/
    public function __construct($currentVersion, $newVersion)
    {
        parent::__construct($currentVersion, $newVersion);
    }

    /**
     * @see \OpenXdmod\Migration\Migration::execute
     **/
    public function execute()
    {
        parent::execute();

        $this->migrateTables();
        $this->populateTables();

        $this->updateTimePeriods();
        $this->runEtlPipeline('hpcdb-modw.bootstrap');
        $this->logger->notice(
            "Data is going to be migrated away from modw.jobfact into\n" .
            "modw.job_records and modw.jobtasks, this might take a while.\n"
        );
        $this->runEtlPipeline('hpcdb-modw.ingest');
        $this->validateJobTasks();
        $this->logger->notice('Rebuilding filter lists');
        try {
            $builder = new FilterListBuilder();
            $this->logger->notice('got the builder');
            $this->logger->notice('Running buildAllLists');
            $builder->setLogger($this->logger);
            $builder->buildAllLists();
            $this->logger->notice('Returned from buildAllLists');
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
        $this->logger->debug(print_r($results, true));
        if($results[0]['facts'] === $results[0]['tasks']){
            $this->logger->notice(
                "Migration Complete.\n\tThe modw.jobfact table is no longer needed " .
                "it can now be deleted.\n"
            );
        }
        else {
            $this->logger->error(
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
            . '-c ' . CONFIG_DIR . '/etl/etl.json' . ' -p ' . $name .
            ' -m ' . $start->format('Y-m-d') . ' -y ' . $end->format('Y-m-d');
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
            $this->logger->error('Unable execute command: ' . $command . "\n" . print_r(error_get_last(), true));
            throw new \Exception('Unable execute command: ' . $command . "\n" . print_r(error_get_last(), true));
        }
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        foreach($pipes as $pipe){
            fclose($pipe);
        }
        $return_value = proc_close($process);
        if ($return_value != 0) {
            $this->logger->error("$command returned $return_value, stdout:  $out stderr: $err");
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
            $tpg->generateMainTable(DB::factory('datawarehouse'), new \DateTime('2000-01-01'), new \DateTime('2038-01-18'));
        }
    }

    /**
     * Attempt to migrate the acl tables into the current system database. This process will
     * utilize the script acl-xdmod-management which in turn utilizes the ETL overseer.
     *
     * @return void
     **/
    public function migrateTables()
    {
        $scripts = array(
            'acl-xdmod-management' => array()
        );
        $this->runScripts($scripts);
    }

    /**
     * Attempt to execute the scripts: acl-import and acl-config which will
     * populate the acl related tables with the correct information for this
     * installation based on the following configuration (directories of) files:
     *     - CONF_DIR/datawarehouse.[d|json]
     *     - CONF_DIR/roles.[d|json]
     *     - CONF_DIR/hierarchies.[d|json]
     *
     * @return void
     **/
    public function populateTables()
    {
        $scripts = array(
            'acl-config' => array(),
            'acl-import' => array()
        );

        $this->runScripts($scripts);
    }

    public function runScripts(array $scripts)
    {
        foreach ($scripts as $scriptName => $params) {
            $cmd = "$scriptName ". implode(' ', $params);
            $this->logger->info("Executing $cmd");

            $output = shell_exec($cmd);

            $hadError = strpos($output, 'error') !== false;

            if ($hadError == true) {
                $this->logger->err(<<<MSG
There was an error when attempting to execute the the script with the provided
parameters. Please see the output below, make any corrections necessary and
re-run this setup.

$output
MSG
                );
                exit(1);
            } else {
                $this->logger->notice("The script executed without error.");
                $this->logger->notice($output);
            }
        }
    }
}
