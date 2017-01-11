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
        'Resource',
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
     * Ingestors that use the HPcDB as their data source.
     *
     * @var array
     */
    protected $hpcdbIngestors = array(
        'Accounts',
        'AllocationBreakdowns',
        'Allocations',
        'AllocationsOnResources',
        'FieldOfScience',
        'FieldOfScienceHierarchy',
        'Organizations',
        'NodeCount',
        'PIPeople',
        'People',
        'PeopleUnderPI',
        'PrincipalInvestigators',
        'Queues',
        'Requests',
        'ResourceTypes',
        'Resources',
        'ResourceSpecs',
        'ResourceAllocated',
        'ServiceProvider',
        'SystemAccounts',
        'Jobs',
        'JobHosts',
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
        $this->logger->info('Initializing aggregation');

        $this->logger->debug('Generating processor buckets');
        $generator = new ProcessorBucketGenerator();
        $generator->execute($this->warehouseDb, $this->aggDbName);

        $this->logger->debug('Generating job times');
        $generator = new JobTimeGenerator();
        $generator->execute($this->warehouseDb, $this->aggDbName);

        if ($startDate === null) {
            $startDate = $this->aggregationStartDate;
        }

        if ($endDate === null) {
            $endDate = $this->aggregationEndDate;
        }

        $this->logger->info("Date range: '$startDate' to '$endDate'");

        if ($startDate === null || $endDate === null) {
            $msg = 'No start and/or end date, ending initialization';
            $this->logger->debug($msg);
            return;
        }

        if (strcmp($startDate, $endDate) > 0) {
            $msg = "Invalid date range: '$startDate' to '$endDate'";
            throw new Exception($msg);
        }

        $sql = "
            SELECT DISTINCT resource_id
            FROM jobfact
            WHERE
                start_time BETWEEN '$startDate' AND '$endDate'
                AND resource_id NOT IN (
                    SELECT DISTINCT resource_id
                    FROM resourcespecs
                    WHERE processors IS NOT NULL
                )
        ";
        $this->logger->debug('Querying for missing data: ' . $sql);
        $resourcesWithoutInfo = $this->warehouseDb->query($sql);

        if (count($resourcesWithoutInfo) > 0) {
            $resources = array();
            foreach ($resourcesWithoutInfo as $resource) {
                $resources[] = $resource['resource_id'];
            }

            $msg = 'New Resource(s) in resourcespecs table do not have '
                . 'processor and node information: '
                . implode(',', $resources);
            throw new Exception($msg);
        }

        $this->logger->debug('Dropping minmaxdate table');
        $this->warehouseDb->execute("DROP TABLE IF EXISTS minmaxdate");

        $this->logger->debug('Creating minmaxdate table');
        $sql = "
            CREATE TABLE minmaxdate AS SELECT
                LEAST(
                    MIN(start_time),
                    MIN(end_time),
                    MIN(submit_time)
                ) AS min_job_date,
                GREATEST(
                    MAX(start_time),
                    MAX(end_time),
                    MAX(submit_time)
                ) AS max_job_date
            FROM jobfact
        ";
        $this->logger->debug('Create statement: ' . $sql);
        $this->warehouseDb->execute($sql);

        // Re-generate the aggregation unit tables (day, month, quarter,
        // year) in modw
        foreach ($this->aggregationUnits as $aggUnit) {
            $tpg = TimePeriodGenerator::getGeneratorForUnit($aggUnit);
            $tpg->generateMainTable($this->warehouseDb);
        }
    }

    /**
     * Aggregate a fact table.
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
     * Create aggregate job data.
     *
     * @param string $startDate
     * @param string $endDate
     * @param bool $append
     */
    public function aggregateAllJobs(
        $startDate = null,
        $endDate = null,
        $append = null
    ) {
        $this->logger->info('Aggregating jobs');

        if ($startDate === null || $endDate === null || $append === null) {
            if ($startDate === null) {
                $startDate = $this->aggregationStartDate;
            }

            if ($endDate === null) {
                $endDate = $this->aggregationEndDate;
            }

            if ($append === null) {
                $append = $this->append;
            }
        }

        if ($startDate === null || $endDate === null) {
            $msg = 'No new job data found, skipping aggregation';
            $this->logger->notice($msg);
            return;
        }

        if (strcmp($startDate, $endDate) > 0) {
            $msg = "Invalid date range: '$startDate' to '$endDate'";
            throw new Exception($msg);
        }

        $this->logger->debug("Date range: '$startDate' to '$endDate'");

        $this->aggregate(
            'JobTimeseriesAggregator',
            $startDate,
            $endDate,
            $append
        );
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

    /**
     * Returns default date range for aggregation.
     *
     * Returns a date range starting with the minimum start date for
     * jobs that have not been ingested into the warehouse and ending
     * with today's date.  Since the dates depend on pre-ingested HPcDB
     * data this method must be called after the staging data is
     * ingested, but before HPcDB data is ingested, to return a useful
     * date range.
     *
     * @return array
     *   - Aggregation start date
     *   - Aggregation end date
     */
    private function getDefaultAggregationDateParams()
    {
        $this->logger->debug('Determining aggregation date range');

        $sql = '
            SELECT
                COUNT(*)    AS count,
                MAX(job_id) AS max_job_id
            FROM jobfact
        ';
        $this->logger->debug('Querying jobfact for max job_id: ' . $sql);
        list($row) = $this->warehouseDb->query($sql);

        if ($row['count'] == 0) {
            $this->logger->debug('No data found in jobfact');

            $sql = '
                SELECT
                    COUNT(*)                             AS count,
                    DATE(FROM_UNIXTIME(MIN(start_time))) AS min_start_date
                FROM hpcdb_jobs
            ';
            $msg = 'Querying hpcdb_jobs for start date: ' . $sql;
            $this->logger->debug($msg);
            list($row) = $this->hpcdbDb->query($sql);

            if ($row['count'] == 0) {
                return array(null, null, null);
            }

            $startDate = $row['min_start_date'];
        } else {
            $maxJobId = $row['max_job_id'];
            $this->logger->debug("Found jobfact max job_id: $maxJobId");
            $sql = '
                SELECT
                    COUNT(*)                             AS count,
                    DATE(FROM_UNIXTIME(MIN(start_time))) AS min_start_date
                FROM hpcdb_jobs
                WHERE job_id > :job_id
            ';
            $params = array('job_id' => $maxJobId);
            $msg = 'Querying hpcdb_jobs for start date: ' . $sql;
            $this->logger->debug(array_merge(
                array('message' => $msg),
                $params
            ));
            list($row) = $this->hpcdbDb->query($sql, $params);

            if ($row['count'] == 0) {
                $this->logger->debug('No new data found in hpcdb_jobs');
                return array(null, null, null);
            }

            $startDate = $row['min_start_date'];
            $msg = "Found max start date in hpcdb_jobs: $startDate";
            $this->logger->debug($msg);
        }

        $endDate = date('Y-m-d');
        $this->logger->debug("Using current date for end date: $endDate");

        return array($startDate, $endDate);
    }
}
