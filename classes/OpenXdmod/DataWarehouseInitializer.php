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
        $this->ingestCloudDataGeneric();
        $this->ingestCloudDataOpenStack();
        $this->ingestStorageData();
    }

    /**
     * Ingest shredded job data.
     *
     * @param string $startDate
     * @param string $endDate
     */
    public function ingestAllShredded($startDate = null, $endDate = null)
    {
        if( $this->isRealmEnabled('Jobs')){
            $this->logger->debug('Ingesting shredded data to staging tables');
            Utilities::runEtlPipeline(array('staging-ingest-common', 'staging-ingest-jobs'), $this->logger);
        }
    }

    /**
     * Ingest staging data to the HPcDB.
     *
     * @param string $startDate
     * @param string $endDate
     */
    public function ingestAllStaging($startDate = null, $endDate = null)
    {
        if( $this->isRealmEnabled('Jobs')){
            $this->logger->debug('Ingesting staging data to HPCDB');
            Utilities::runEtlPipeline(array('hpcdb-ingest-common', 'hpcdb-ingest-jobs'), $this->logger);
        }
    }

    /**
     * Ingest HPcDB data to the MoD warehouse.
     *
     * @param string $startDate
     * @param string $endDate
     */
    public function ingestAllHpcdb($startDate = null, $endDate = null)
    {
        if( $this->isRealmEnabled('Jobs')){
            $this->logger->debug('Ingesting HPCDB data to modw');
            $params = array();
            $pipeline = array('hpcdb-prep-xdw-job-ingest-by-new-jobs');

            if ($startDate !== null || $endDate !== null) {
                if ($startDate !== null) {
                    $params['start-date'] = $startDate . ' 00:00:00';
                }
                if ($endDate !== null) {
                    $params['end-date'] = $endDate . ' 23:59:59';
                }
                $pipeline = array('hpcdb-prep-xdw-job-ingest-by-date-range');
            }

            Utilities::runEtlPipeline($pipeline, $this->logger, $params);

            // Use current time from the database in case clocks are not
            // synchronized.
            $lastModifiedStartDate
                = $this->hpcdbDb->query('SELECT NOW() AS now FROM dual')[0]['now'];

            Utilities::runEtlPipeline(
                array('hpcdb-xdw-ingest-common', 'hpcdb-xdw-ingest-jobs'),
                $this->logger,
                array('last-modified-start-date' => $lastModifiedStartDate)
            );
        }
    }

    /**
     * Extracting openstack data from the openstack_raw_events table. If the raw
     * tables do not exist then catch the resulting exception and display a message
     * saying that there is no OpenStack data to ingest.
     */
    public function ingestCloudDataOpenStack()
    {
        if( $this->isRealmEnabled('Cloud') ){
            try {
                $this->logger->notice('Ingesting OpenStack event log data');
                Utilities::runEtlPipeline(
                    array('jobs-cloud-import-users-openstack', 'jobs-cloud-extract-openstack'),
                    $this->logger
                );
            }
            catch( Exception $e ){
                if( $e->getCode() == 1146 ){
                    $this->logger->notice('No OpenStack events to ingest');
                }
                else{
                    throw $e;
                }
            }
        }
    }

    /**
     * Extracting cloud log data from the generic_raw_events table. If the raw
     * tables do not exist then catch the resulting exception and display a message
     * saying that there is no generic cloud data to ingest.
     */
    public function ingestCloudDataGeneric()
    {
        if( $this->isRealmEnabled('Cloud') ){
            try {
                $this->logger->notice('Ingesting generic cloud log files');
                Utilities::runEtlPipeline(
                    array('jobs-cloud-import-users-generic', 'jobs-cloud-extract-generic'),
                    $this->logger
                );
            }
            catch( Exception $e ){
                if( $e->getCode() == 1146 ){
                    $this->logger->notice('No cloud event data to ingest');
                }
                else{
                    throw $e;
                }
            }
        }
    }

    /**
     * Ingest storage data.
     *
     * If the storage realm is not enabled then do nothing.
     */
    public function ingestStorageData()
    {
        if (!$this->isRealmEnabled('Storage')) {
            $this->logger->notice('Storage realm not enabled, not ingesting');
            return;
        }

        $this->logger->notice('Ingesting storage data');
        Utilities::runEtlPipeline(
            [
                'staging-ingest-common',
                'hpcdb-ingest-common',
                'hpcdb-ingest-storage',
                'hpcdb-xdw-ingest-common',
                'xdw-ingest-storage',
            ],
            $this->logger
        );
    }

    /**
     * Aggregating all cloud data. If the appropriate tables do not exist then
     * catch the resulting exception and display a message saying that there
     * is no cloud data to aggregate and cloud aggregation is being skipped.
     */
    public function aggregateCloudData($lastModifiedStartDate)
    {
        if( $this->isRealmEnabled('Cloud') ){
            $this->logger->notice('Aggregating Cloud data');
            Utilities::runEtlPipeline(
                array('cloud-state-pipeline'),
                $this->logger,
                array('last-modified-start-date' => $lastModifiedStartDate)
            );

            $filterListBuilder = new FilterListBuilder();
            $filterListBuilder->setLogger($this->logger);
            $filterListBuilder->buildRealmLists('Cloud');
        }
    }

    /**
     * Aggregate storage data.
     *
     * If the storage realm is not enabled then do nothing.
     */
    public function aggregateStorageData()
    {
        if (!$this->isRealmEnabled('Storage')) {
            $this->logger->notice('Storage realm not enabled, not aggregating');
            return;
        }

        $this->logger->notice('Aggregating storage data');
        Utilities::runEtlPipeline(['xdw-aggregate-storage'], $this->logger);
        $filterListBuilder = new FilterListBuilder();
        $filterListBuilder->setLogger($this->logger);
        $filterListBuilder->buildRealmLists('Storage');
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

        if( $this->isRealmEnabled('Jobs') ){
            Utilities::runEtlPipeline(
                array('jobs-xdw-aggregate'),
                $this->logger,
                array('last-modified-start-date' => $lastModifiedStartDate)
            );
            $filterListBuilder = new FilterListBuilder();
            $filterListBuilder->setLogger($this->logger);
            $filterListBuilder->buildRealmLists('Jobs');
        }
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
     * Check to see if a realm exists in the realms table
     *
     * @param string $realm The realm you are checking to see if exists
     */
    public function isRealmEnabled($realm)
    {
        $realms = $this->warehouseDb->query("SELECT * FROM moddb.realms WHERE display = :realm", [':realm' => $realm]);
        return (count($realms) > 0);
    }
}
