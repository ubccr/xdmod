<?php

namespace OpenXdmod\Migration\Version800To810;

use CCR\DB;
use OpenXdmod\DataWarehouseInitializer;
use FilterListBuilder;
use TimePeriodGenerator;
use OpenXdmod\Setup\Console;
use CCR\DB\MySQLHelper;

/**
 * Migrate databases from version 8.0.0 to 8.1.0
 */
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    /**
     * @see \OpenXdmod\Migration\Migration::execute
     **/
    public function execute()
    {
        parent::execute();

        $console = Console::factory();
        $console->displayMessage(<<<"EOT"
This release includes an update that enables the Job Viewer tab and the 'show raw
data' drilldown in the Metric Explorer for Jobs realm data. It is recommended
to reaggregate all jobs. Depending on the amount of data this could take multiple
hours. If the job data is not reaggregated then existing jobs will not be viewable
in the Job Viewer.
EOT
        );
        $runaggregation = $console->promptBool(
            'Do you want to run aggregation now?',
            false
        );
        if (true === $runaggregation) {
            $this->runEtlv2(
                array(
                    'process-sections' => array('jobs-xdw-aggregate'),
                    'last-modified-start-date' => date('Y-m-d', strtotime('2000-01-01')),
                )
            );
            $this->logger->notice('Rebuilding filter lists');
            try {
                $builder = new FilterListBuilder();
                $builder->setLogger($this->logger);
                $builder->buildRealmLists('Jobs');
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
Aggregation not run.  Aggregation may be run manually with the following command:
xdmod-ingestor --aggregate --last-modified-start-date '2000-01-01'
EOT
            );
        }
    }
}
