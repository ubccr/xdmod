<?php

namespace OpenXdmod;

use Exception;
use CCR\DB\iDatabase;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseer;
use ETL\EtlOverseerOptions;
use ETL\Utilities;
use FilterListBuilder;

class DataWarehouseInitializer
{

    /**
     * @var \Log
     */
    protected $logger;

    /**
     * HPcDB database.
     *
     * @var iDatabase
     */
    protected $hpcdbDb;

    /**
     * MoD warehouse database.
     *
     * @var iDatabase
     */
    protected $warehouseDb;

    /**
     * Aggregation units.
     *
     * @var array
     */
    protected $aggregationUnits = array(
        'day',
        'month',
        'quarter',
        'year'
    );

    /**
     * Name of the aggregate database.
     *
     * @var string
     */
    protected $aggDbName = 'modw_aggregates';

    /**
     * Default aggregation start date.
     *
     * @var string
     */
    protected $aggregationStartDate;

    /**
     * Default aggregation end date.
     *
     * @var string
     */
    protected $aggregationEndDate;

    /**
     * Default append value.
     *
     * True if aggregation data should be appended.
     *
     * @var bool
     */
    protected $append;

    /**
     * @param iDatabase $hpcdbDb The HPcDB database.
     * @param iDatabase $warehouseDb The MoD warehouse database.
     */
    public function __construct(
        iDatabase $hpcdbDb,
        iDatabase $warehouseDb
    ) {
        $this->hpcdbDb     = $hpcdbDb;
        $this->warehouseDb = $warehouseDb;
        $this->logger      = \Log::singleton('null');
    }

    /**
     * Set the logger.
     *
     * @param \Log $logger A logger instance.
     */
    public function setLogger(\Log $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set the name of the aggregate database.
     *
     * @param string $dbName
     */
    public function setAggregateDatabaseName($dbName)
    {
        $this->aggDbName = $dbName;
    }

    /**
     * Ingest all data needed for the data warehouse.
     *
     * @param string $startDate
     * @param string $endDate
     */
    public function ingestAll($startDate = null, $endDate = null)
    {
        $this->logger->debug('Ingesting all data');
        if ($startDate !== null) {
            $this->logger->debug('Start date: ' . $startDate);
        }
        if ($endDate !== null) {
            $this->logger->debug('End date: ' . $endDate);
        }

        $this->ingestAllShredded($startDate, $endDate);
        $this->ingestAllStaging($startDate, $endDate);
        $this->ingestAllHpcdb($startDate, $endDate);
    }

    /**
     * Ingest shredded job data.
     *
     * @param string $startDate
     * @param string $endDate
     */
    public function ingestAllShredded($startDate = null, $endDate = null)
    {
        $this->logger->debug('Ingesting shredded data to staging tables');
        $this->runEtlPipeline('staging-ingest-common');
        $this->runEtlPipeline('staging-ingest-jobs');
    }

    /**
     * Ingest staging data to the HPcDB.
     *
     * @param string $startDate
     * @param string $endDate
     */
    public function ingestAllStaging($startDate = null, $endDate = null)
    {
        $this->logger->debug('Ingesting staging data to HPCDB');
        $this->runEtlPipeline('hpcdb-ingest-common');
        $this->runEtlPipeline('hpcdb-ingest-jobs');
    }

    /**
     * Ingest HPcDB data to the MoD warehouse.
     *
     * @param string $startDate
     * @param string $endDate
     */
    public function ingestAllHpcdb($startDate = null, $endDate = null)
    {
        $this->logger->debug('Ingesting HPCDB data to modw');

        if ($startDate !== null || $endDate !== null) {
            $params = array();
            if ($startDate !== null) {
                $params['start-date'] = $startDate . ' 00:00:00';
            }
            if ($endDate !== null) {
                $params['end-date'] = $endDate . ' 23:59:59';
            }
            $this->runEtlPipeline(
                'hpcdb-prep-xdw-job-ingest-by-date-range',
                $params
            );
        } else {
            $this->runEtlPipeline('hpcdb-prep-xdw-job-ingest-by-new-jobs');
        }

        // Use current time from the database in case clocks are not
        // synchronized.
        $lastModifiedStartDate
            = $this->hpcdbDb->query('SELECT NOW() AS now FROM dual')[0]['now'];

        $this->runEtlPipeline(
            'hpcdb-xdw-ingest',
            array('last-modified-start-date' => $lastModifiedStartDate)
        );
    }

    /**
     * Initialize aggregate database.
     *
     * This function should be called before all other aggregation
     * functions.
     *
     * @param string $startDate
     * @param string $endDate
     */
    public function initializeAggregation($startDate = null, $endDate = null)
    {
        /**
         * This is staying around until xsede can be updated to not require this to be changed.
         * As this is called from supremm aggregation still.
         */
        return;
    }

    /**
     * Create aggregate job data.
     *
     * @param string $lastModifiedStartDate Last modified start date used to
     *     determine which jobs will be aggregated.
     */
    public function aggregateAllJobs($lastModifiedStartDate)
    {
        $this->runEtlPipeline(
            'jobs-xdw-aggregate',
            array('last-modified-start-date' => $lastModifiedStartDate)
        );
        $filterListBuilder = new FilterListBuilder();
        $filterListBuilder->setLogger($this->logger);
        $filterListBuilder->buildRealmLists('Jobs');
    }

    /**
     * Aggregate a fact table.
     *
     * This is staying around until supremm can be updated to etlv2
     *
     * @param string $aggregator Aggregator class name.
     * @param string $startDate Aggregation start date.
     * @param string $endDate Aggregation end date.
     * @param bool $append True if aggregation data should be appended.
     */
    public function aggregate(
        $aggregator,
        $startDate,
        $endDate,
        $append = true
    ) {
        $this->logger->info(array(
            'message'    => 'start',
            'class'      => get_class($this),
            'function'   => __FUNCTION__,
            'aggregator' => $aggregator,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'append'     => $append,
        ));

        foreach ($this->aggregationUnits as $aggUnit) {
            $this->logger->info("Aggregating by $aggUnit");
            $agg = new $aggregator($aggUnit);
            $agg->setLogger($this->logger);
            $agg->execute(
                $this->warehouseDb,
                $this->aggDbName,
                $startDate,
                $endDate,
                $append
            );
        }

        $this->logger->info("Building filter lists");
        $agg->updateFilters();

        $this->logger->info(array(
            'message'  => 'end',
            'class'    => get_class($this),
            'function' => __FUNCTION__,
        ));
    }

    /**
     * Run an ETL pipeline.
     *
     * @param string $name Pipeline or "section" to run.
     * @param array $params Parameters to be passed to used to construct
     *   EtlOverseerOptions.
     */
    private function runEtlPipeline($name, $params = array())
    {
        $this->logger->debug(
            sprintf(
                'Running ETL pipeline "%s" with parameters %s',
                $name,
                json_encode($params)
            )
        );

        $etlConfig = new EtlConfiguration(
            CONFIG_DIR . '/etl/etl.json',
            null,
            $this->logger,
            array('default_module_name' => 'xdmod')
        );
        $etlConfig->initialize();
        Utilities::setEtlConfig($etlConfig);

        $scriptOptions = array_merge(
            array(
                'default-module-name' => 'xdmod',
                'process-sections' => array($name),
            ),
            $params
        );
        $this->logger->debug(
            sprintf(
                'Running ETL pipeline with script options %s',
                json_encode($scriptOptions)
            )
        );

        $overseerOptions = new EtlOverseerOptions(
            $scriptOptions,
            $this->logger
        );
        $overseer = new EtlOverseer($overseerOptions, $this->logger);
        $overseer->execute($etlConfig);
    }
}
