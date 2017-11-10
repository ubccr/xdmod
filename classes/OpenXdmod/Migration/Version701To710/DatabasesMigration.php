<?php

/**
 * @author Ryan Rathsam <ryanrath@buffalo.edu>
 **/
namespace OpenXdmod\Migration\Version701To710;

use CCR\DB;
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
        $this->runEtlPipeline('hpcdb-modw.ingest');
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

        fclose($pipes[1]);
        fclose($pipes[2]);

        $return_value = proc_close($process);
        if ($return_value != 0) {
            $this->logger->error("$command returned $return_value, stdout:  stderr: $err");
            throw new \Exception("$command returned $return_value, stdout:  stderr: $err");
        }
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
