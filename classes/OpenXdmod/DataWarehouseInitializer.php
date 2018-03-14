<?php

namespace OpenXdmod;

use Exception;
use CCR\DB\iDatabase;
use CCR\DB\MySQLDB;
use CCR\DB\MySQLHelper;
use ProcessorBucketGenerator;
use JobTimeGenerator;
use TimePeriodGenerator;
use JobTimeseriesAggregator;

class DataWarehouseInitializer
{

    /**
     * @var \Log
     */
    protected $logger;

    /**
     * Shredder database.
     *
     * @var iDatabase
     */
    protected $shredderDb;

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
     * Aggregate database.
     *
     * @var iDatabase
     */
    protected $aggregateDb;

    /**
     * Ingestors that use the "shredded_job" table as their data source.
     *
     * @var array
     */
    protected $shreddedJobIngestors = array(
        'Resources',
        'PI',
        'PIResource',
        'UserPIResource',
        'UnionUserPI',
        'UnionUserPIResource',
        'Jobs',
    );

    /**
     * Ingestors that use staging data as their data source.
     *
     * @var array
     */
    protected $stagingIngestors = array(

        // Constant data.
        'ResourceTypes',

        // Entity data.
        'Organizations',
        'Resources',
        'ResourceSpecs',
        'ResourceAllocated',
        'Accounts',
        'Allocations',
        'People',
        'Requests',

        // Allocation data.
        'AllocationBreakdown',
        'AllocationsOnResources',

        // Person data.
        'EmailAddresses',
        'PeopleOnAccountsHistory',
        'PrincipalInvestigators',
        'SystemAccounts',

        // Job data.
        'Jobs',
    );

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
     * @param iDatabase $shredderDb The shredder database.
     * @param iDatabase $hpcdbDb The HPcDB database.
     * @param iDatabase $warehouseDb The MoD warehouse database.
     */
    public function __construct(
        iDatabase $shredderDb,
        iDatabase $hpcdbDb,
        iDatabase $warehouseDb
    ) {
        $this->shredderDb  = $shredderDb;
        $this->hpcdbDb     = $hpcdbDb;
        $this->warehouseDb = $warehouseDb;
        $this->logger      = \Log::singleton('null');

        $this->aggregateDb = new MySQLDB(
            $warehouseDb->_db_host,
            $warehouseDb->_db_port,
            'modw_aggregates',
            $warehouseDb->_db_username,
            $warehouseDb->_db_password
        );

        $helper = MySQLHelper::factory($this->aggregateDb);

        // Append if aggregate tables already exist.

        $this->append = $helper->tableExists('jobfact_by_year');
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
        $this->logger->debug('Ingesting shredded job data');
        if ($startDate !== null) {
            $this->logger->debug('Start date: ' . $startDate);
        }
        if ($endDate !== null) {
            $this->logger->debug('End date: ' . $endDate);
        }

        $ingestors = $this->addPrefix(
            $this->shreddedJobIngestors,
            "OpenXdmod\\Ingestor\\Shredded\\"
        );

        $this->ingest(
            $ingestors,
            $this->shredderDb,
            $this->shredderDb,
            $startDate,
            $endDate
        );
    }

    /**
     * Ingest staging data to the HPcDB.
     *
     * @param string $startDate
     * @param string $endDate
     */
    public function ingestAllStaging($startDate = null, $endDate = null)
    {
        $this->logger->debug('Ingesting staging data');
        if ($startDate !== null) {
            $this->logger->debug('Start date: ' . $startDate);
        }
        if ($endDate !== null) {
            $this->logger->debug('End date: ' . $endDate);
        }

        $ingestors = $this->addPrefix(
            $this->stagingIngestors,
            "OpenXdmod\\Ingestor\\Staging\\"
        );

        $this->ingest(
            $ingestors,
            $this->shredderDb,
            $this->hpcdbDb,
            $startDate,
            $endDate
        );
    }

    /**
     * Ingest HPcDB data to the MoD warehouse.
     *
     * @param string $startDate
     * @param string $endDate
     */
    public function ingestAllHpcdb($startDate = null, $endDate = null)
    {
        $this->logger->debug('Ingesting HPcDB data');
        if ($startDate !== null) {
            $this->logger->debug('Start date: ' . $startDate);
        }
        if ($endDate !== null) {
            $this->logger->debug('End date: ' . $endDate);
        }

        // Store aggregation start and end dates before ingesting from
        // mod_hpcdb since those dates depend on what data has not been
        // inserted into modw.
        list(
            $this->aggregationStartDate,
            $this->aggregationEndDate,
        ) = $this->getDefaultAggregationDateParams();

        $ingestors = $this->addPrefix(
            $this->hpcdbIngestors,
            "OpenXdmod\\Ingestor\\Hpcdb\\"
        );

        $this->ingest(
            $ingestors,
            $this->hpcdbDb,
            $this->warehouseDb,
            $startDate,
            $endDate
        );
    }

    /**
     * Run a set of ingestors using the specified databases.
     *
     * @param Ingestor[] ingestors A set of ingestors.
     * @param iDatabase $srcDb The source database.
     * @param iDatabase $destDb The destination database.
     * @param string $startDate
     * @param string $endDate
     */
    protected function ingest(
        array $ingestors = array(),
        iDatabase $srcDb,
        iDatabase $destDb,
        $startDate = null,
        $endDate = null
    ) {
        if (strcmp($startDate, $endDate) > 0) {
            $msg = "Invalid date range: '$startDate' to '$endDate'";
            throw new Exception($msg);
        }

        $this->logger->debug("Using date range: '$startDate' to '$endDate'");

        foreach ($ingestors as $ingestorName) {
            $this->logger->debug("Creating ingestor '$ingestorName'");

            $ingestor = new $ingestorName(
                $destDb,
                $srcDb,
                $startDate,
                $endDate
            );
            $ingestor->setLogger($this->logger);
            $ingestor->ingest();
        }
    }

    /**
     * Initialize aggregate database.
     *
     * This function should be called before all other aggregation
     * funcions.
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
     * Aggregate a fact table.
     *
     * @param string $aggregator Aggregator class name.
     * @param string $startDate Aggregation start date.
     * @param string $endDate Aggregation end date.
     * @param bool $append True if aggregation data should be appended.
     */
     /**
      * This is staying around until supremm can be updated to etlv2
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
     * Prefix a set of strings.
     *
     * @param string[] $names A set of names that need prefixing.
     * @param string $predix The prefix to add to the names.
     *
     * @return string[]
     */
    private function addPrefix(array $names = array(), $prefix)
    {
        return array_map(
            function ($name) use ($prefix) {
                return $prefix . $name;
            },
            $names
        );
    }
}
