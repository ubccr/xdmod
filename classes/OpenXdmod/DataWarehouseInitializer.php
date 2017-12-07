<?php

namespace OpenXdmod;

use Exception;
use CCR\DB\iDatabase;
use CCR\DB\MySQLDB;
use CCR\DB\MySQLHelper;

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
     * Run a set of ingestors using the specified databases.
     *
     * @param Ingestor[] ingestors A set of ingestors.
     * @param iDatabase $srcDb The source database.
     * @param iDatabase $destDb The destination database.
     * @param string $startDate
     * @param string $endDate
     */
    protected function ingest(
        array $ingestors,
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
     * Prefix a set of strings.
     *
     * @param string[] $names A set of names that need prefixing.
     * @param string $predix The prefix to add to the names.
     *
     * @return string[]
     */
    private function addPrefix(array $names, $prefix)
    {
        return array_map(
            function ($name) use ($prefix) {
                return $prefix . $name;
            },
            $names
        );
    }
}
