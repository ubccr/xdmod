<?php
/* ==========================================================================================
 * Ingestor action using the PDO driver. The pdoIngestor is responsible for handling the bulk
 * of the operations necessary for ingesting data using PDO source and destination
 * endpoints. The aRdbmsDestinationAction class implements functionality specific to
 * supporting a database destination.
 *
 * Optionally, the following methods may be overriden to perform additional actions or override
 * default actions:
 *
 * getDestinationFields() (if the fields could not be parsed from the source query)
 * getSourceQueryString()
 *
 * The overall ingestion process is as follows:
 *
 * 1. Perform pre-execution tasks (aIngestor)
 * 2. Perform a single-database (optimized) or multi-database ingest.
 * 2a. A single-database ingest combines the source and insert queries into one operation and
 *     executes entirely within the database. This can be used when the source and
 *     destination are the same database and enjoys a small performance improvement.
 * 2b. A multi-database ingest is used when the source and destination endpoints are not the
 *     same, when a single source query is used to populate multiple destination tables, or
 *     when data transformation is required. This uses a LOAD DATA INFILE statement for each
 *     destination table. The general process is:
 *     - Set up one INFILE for each target database table
 *     - Extract the data from the source
 *     - Optionally transform each source record (possibly into multiple records)
 *     - Load the appropriate data into each INFILE
 *     - Process the INFILES
 * 3. Perform post-execution tasks (aIngestor)
 *
 * Notes:
 *
 * - By default, an unbuffered query is used to fetch data for ingestion. Buffered queries require
 *   significant memory for large result sets.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-09-16
 * ==========================================================================================
 */

namespace ETL\Ingestor;

use ETL\aAction;
use ETL\aOptions;
use ETL\aRdbmsDestinationAction;
use ETL\DataEndpoint\iRdbmsEndpoint;
use ETL\DataEndpoint\Mysql;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\Utilities;
use ETL\DbModel\Query;

use CCR\DB\MySQLHelper;
use PDOException;
use Exception;
use PDO;
use Psr\Log\LoggerInterface;

class pdoIngestor extends aIngestor
{

    /** -----------------------------------------------------------------------------------------
     * Maximum number of times to attempt to execute the source query
     *
     * @var int
     * ------------------------------------------------------------------------------------------
     */

    const MAX_QUERY_ATTEMPTS = 3;

    /** -----------------------------------------------------------------------------------------
     * Write a log message after processing this many source records
     *
     * @var int
     * ------------------------------------------------------------------------------------------
     */

    const NUM_RECORDS_PER_LOG_MSG = 100000;

    /** -----------------------------------------------------------------------------------------
     * Maximum number of records to import in one LOAD DATA IN FILE
     *
     * @var int
     * ------------------------------------------------------------------------------------------
     */

    private $dbInsertChunkSize = 250000;

    /** -----------------------------------------------------------------------------------------
     * The number of seconds to allot for the timeout per file per record chunk
     *
     * @var int
     * ------------------------------------------------------------------------------------------
     */

    private $netWriteTimeoutSecondsPerFileChunk = 60;

    /** -----------------------------------------------------------------------------------------
     * Query used for extracting data from the source endpoint.
     *
     * @var string | null
     * ------------------------------------------------------------------------------------------
     */

    private $sourceQueryString = null;

    /** -----------------------------------------------------------------------------------------
     * A Query object representing the source query for this action
     *
     * @var Query | null
     * ------------------------------------------------------------------------------------------
     */

    protected $etlSourceQuery = null;

    /** -----------------------------------------------------------------------------------------
     * An array containing the field names available from the source record (query,
     * structured file, etc.)
     *
     * @var array | null
     * ------------------------------------------------------------------------------------------
     */

    protected $sourceRecordFields = null;

    /** -----------------------------------------------------------------------------------------
     * Line separator for MySQL LOAD DATA INFILE LINES TERMINATED BY.
     *
     * @var string
     * ------------------------------------------------------------------------------------------
     */

    protected $lineSeparator = '\n';

    /** -----------------------------------------------------------------------------------------
     * Field separator for MySQL LOAD DATA INFILE FIELDS TERMINATED BY.
     *
     * @var string
     * ------------------------------------------------------------------------------------------
     */

    protected $fieldSeparator = '\t';

    /** -----------------------------------------------------------------------------------------
     * String enclosure for MySQL LOAD DATA INFILE ENCLOSED BY.
     *
     * @var string
     * ------------------------------------------------------------------------------------------
     */

    protected $stringEnclosure = '';


    /** -----------------------------------------------------------------------------------------
     * Escape character for the MySQL LOAD DATA INFILE ESCAPED BY
     *
     * @var string
     * ------------------------------------------------------------------------------------------
     */

    protected $escapeChar = '\\';

    /** -----------------------------------------------------------------------------------------
     * Database helper object such as MySQLHelper
     *
     * @var object
     * ------------------------------------------------------------------------------------------
     */

    private $_dest_helper = null;

    /** -----------------------------------------------------------------------------------------
     * General setup.
     *
     * @see iAction::__construct()
     *
     * @param aOptions $options Options specific to this Ingestor
     * @param EtlConfiguration $etlConfig Parsed configuration options for this ETL
     * @param LoggerInterface $logger Monolog object for system logging
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, LoggerInterface $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);

        // This action supports chunking of the ETL date period

        $this->supportDateRangeChunking = true;

        // Use ASCII group, record, and unit separators

        $this->lineSeparator = chr(0x1d);   // Group separator
        $this->fieldSeparator = chr(0x1e);  // Record separator
        $this->stringEnclosure = chr(0x1f); // Unit separator

    }  // __construct()

    /** -----------------------------------------------------------------------------------------
     * @see iAction::initialize()
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(EtlOverseerOptions $etlOverseerOptions = null)
    {
        if ( $this->isInitialized() ) {
            return;
        }

        $this->initialized = false;

        parent::initialize($etlOverseerOptions);

        // Get the handles for the various database endpoints

        if ( ! $this->utilityEndpoint instanceof iRdbmsEndpoint ) {
            $this->logAndThrowException(
                sprintf(
                    "Utility endpoint %s does not implement ETL\\DataEndpoint\\iRdbmsEndpoint",
                    get_class($this->utilityEndpoint)
                )
            );
        }

        if ( ! $this->sourceEndpoint instanceof iRdbmsEndpoint ) {
            $this->logAndThrowException(
                sprintf(
                    "Source endpoint %s does not implement ETL\\DataEndpoint\\iRdbmsEndpoint",
                    get_class($this->sourceEndpoint)
                )
            );
        }

        if ( "mysql" == $this->destinationHandle->_db_engine ) {
            $this->_dest_helper = MySQLHelper::factory($this->destinationHandle);
        }

        // An individual action may override restrictions provided by the overseer.
        $this->setOverseerRestrictionOverrides();

        // If the source query is specified in the definition file use it, otherwise assume that a child
        // class has overriden getSourceQueryString().  Child classes overriding getSourceQueryString()
        // should throw a warning if source_query is defined.

        if ( null === $this->etlSourceQuery && isset($this->parsedDefinitionFile->source_query) ) {
            $this->logger->debug("Create ETL source query object");
            $this->etlSourceQuery = new Query(
                $this->parsedDefinitionFile->source_query,
                $this->sourceEndpoint->getSystemQuoteChar(),
                $this->logger
            );

            // If supported by the source query, set the date ranges here. These will be overriden
            // in the _execute() function with the current start/end dates but are needed here to
            // parse the query.

            $this->getEtlOverseerOptions()->applyOverseerRestrictions(
                $this->etlSourceQuery,
                $this->sourceEndpoint,
                $this
            );

        }  // ( null === $this->etlSourceQuery )

        // By default, queries may include references to the schema names so they can be changed in the
        // config file.

        $this->sourceQueryString = $this->getSourceQueryString();

        if ( null !== $this->sourceQueryString &&
             ! (is_string($this->sourceQueryString) || empty($this->sourceQueryString)) )
        {
            $this->logAndThrowException("Source query must be null or a non-empty string");
        }

        // Get the list of available source query fields. If we have described the source query in
        // the JSON config, use the record keys otherwise we need to parse the SQL string. It is
        // expected that the source record fields are in the same order as they are in the SQL
        // query.
        //
        // NOTE: The SQL parser can fail on complex queries or those containing a UNION. In this
        // case, allow the child class should also set the source field records in
        // getSourceQueryString().

        if ( null === $this->sourceRecordFields ) {
            $this->sourceRecordFields = (
                null !== $this->etlSourceQuery
                ? array_keys($this->etlSourceQuery->records)
                : $this->getSqlColumnNames($this->sourceQueryString)
            );
        }

        $this->parseDestinationFieldMap($this->sourceRecordFields);

        if ( isset($this->options->db_insert_chunk_size) ) {
            $this->dbInsertChunkSize = $this->options->db_insert_chunk_size;
        }

        if ( isset($this->options->net_write_timeout_per_db_chunk) ) {
            $this->netWriteTimeoutSecondsPerFileChunk = $this->options->net_write_timeout_per_db_chunk;
        }

        $this->initialized = true;

        return true;

    } // initialize()

    /** -----------------------------------------------------------------------------------------
     * Get the query to be run against the source data endpoint that will be used to
     * extract the data.
     *
     * @return string The query on the source endpoint.
     * ------------------------------------------------------------------------------------------
     */

    protected function getSourceQueryString()
    {
        if ( null === $this->etlSourceQuery ) {
            $this->logAndThrowException(
                "ETL source query object not instantiated.  Perhaps it is not specified in "
                . "the definition file and not implemented in the Ingestor."
            );
        }

        $sql = $this->variableStore->substitute(
            $this->etlSourceQuery->getSql(),
            "Undefined macros found in source query"
        );

        return $sql;

    }  // getSourceQueryString()

    /** -----------------------------------------------------------------------------------------
     * Perform the query on the data source.
     *
     * @return A PDOStatement with the results of the source query.
     * @throws Exception if any query data was not int the correct format.
     * ------------------------------------------------------------------------------------------
     */

    protected function getSourceData()
    {
        // If the source query is un-buffered we need to perform the count beforehand because all
        // results may not be available immediately after the query.

        $this->logger->info(sprintf("%s: Querying %s", get_class($this), $this->sourceEndpoint));

        // Execute the source query. If we are unable to connect, continue to attempt up the
        // MAX_QUERY_ATTEMPTS

        $query_success = false;
        $n_attempts = 0;
        $srcStatement = null;

        while (false == $query_success) {
            $n_attempts += 1;

            try {

                $this->logger->debug("Source query " . $this->sourceEndpoint . ":\n" . $this->sourceQueryString);
                $srcStatement = $this->sourceHandle->prepare($this->sourceQueryString);

                $start = microtime(true);
                $srcStatement->execute();
                $query_success = true;

            } catch (PDOException $e) {

                // ER_LOCK_DEADLOCK: Deadlock found when trying to get lock; try restarting transaction
                if ( $srcStatement->errorCode() != "40001" ) {
                    $this->logAndThrowException(
                        "Error querying source",
                        array('exception' => $e, 'sql' => $this->sourceQueryString, 'endpoint' => $this->sourceEndpoint)
                    );
                } elseif ( $n_attempts > self::MAX_QUERY_ATTEMPTS ) {
                    $this->logAndThrowException(
                        sprintf("Could not execute source query after %d attempts. Exiting.", self::MAX_QUERY_ATTEMPTS)
                    );
                }

                $this->logger->info(
                    get_class($this)
                    . ': Query was cancelled by server with error '
                    . $srcStatement->errorCode() . '. Retries left = ' . (self::MAX_QUERY_ATTEMPTS - $n_attempts)
                );
            }
        }  // while (FALSE == $query_success)

        // We can query the number of source records if we are using a buffered query.

        if ( $this->options->buffered_query ) {
            $this->logger->debug("Source row count: " . $srcStatement->rowCount());
        }

        return $srcStatement;

    }  // getSourceData()

    /** -----------------------------------------------------------------------------------------
     * Perform pre-execution tasks including disabling foreign key constraints and
     * managing table structure.
     *
     * @see iAction::performPreExecuteTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performPreExecuteTasks() {

        parent::performPreExecuteTasks();

        // Update the start/end dates for this query and get the source query string. It
        // is important to do it in the pre-execute stage because if we are chunking our
        // ingest it will get updated every time.

        $this->sourceQueryString = $this->getSourceQueryString();

        return true;

    }  // performPreExecuteTasks()

    /** ------------------------------------------------------------------------------------------
     * @see iAction::execute()
     * ------------------------------------------------------------------------------------------
     */

    // @codingStandardsIgnoreLine
    protected function _execute()
    {
        // Since the overseer may split the ingestion period up into chunks. Apply the current
        // start/end date range here.

        if ( null !== $this->etlSourceQuery) {
            $this->getEtlOverseerOptions()->applyOverseerRestrictions($this->etlSourceQuery, $this->sourceEndpoint, $this);
        }

        // Re-generate the source query string taking into account overseer restrictions, or in the
        // case where a child class overrides getSourceQueryString() allow any updated macros to be
        // re-applied.

        $this->sourceQueryString = $this->getSourceQueryString();

        // ------------------------------------------------------------------------------------------
        // Main ingest

        $totalRecordsProcessed = 0;

        // If we can execute the ingestion query directly on the server instead of querying,
        // returning the data locally, chunking it into a file, and loading it on the remote server
        // we may gaina speed enhancement. For ~860,000 records this executes in roughly 65% of the
        // time.

        $optimize = $this->allowSingleDatabaseOptimization();

        if ( $optimize ) {
            $this->logger->debug("Allowing same-server SQL optimizations");
            $totalRecordsProcessed = $this->singleDatabaseIngest();
        } else {
            $this->logger->debug("Using multi-database ingest");
            $totalRecordsProcessed = $this->multiDatabaseIngest();
        }

        return $totalRecordsProcessed;

    }  // _execute()

    /** -----------------------------------------------------------------------------------------
     * If the source and destination endpoints are on the same database server we can
     * optimize the ingestion by executing an INSERT...SELECT statement directly on the
     * server rather than selecting the data, brining it back to this host, chunking it
     * into a file, and running LOAD DATA INFILE to load it back into the database. Tests
     * on 875K records show a 29% improvement using INSERT...SELECT and a 48% improvement
     * using INSERT...SELECT and disabling keys during load.
     *
     * NOTE: This method assumes that data is being mapped from one source table to one
     * destination table and all columns are being used. If any translation is performed
     * then this method cannot be used.
     *
     * @return int The number of records processed
     * ------------------------------------------------------------------------------------------
     */

    private function singleDatabaseIngest()
    {
        reset($this->destinationFieldMappings);
        reset($this->etlDestinationTableList);
        $qualifiedDestTableName = current($this->etlDestinationTableList)->getFullName();

        // Generate the list of destination fields. Note that the field list for the INSERT must be
        // in the same order as the fields returned by the query or we will get field mismatches.
        // Use the source record fields (generated from the source query in initialize()) as the
        // correct order. Since the destination field map may have been user-specified we cannot
        // guarantee the order.

        $firstFieldMap = current($this->destinationFieldMappings);
        $destColumnList = array();
        foreach ( $this->sourceRecordFields as $sourceField ) {
            if ( array_key_exists($sourceField, $firstFieldMap) ) {
                $destColumnList[] = $sourceField;
            }
        }

        // The default method for ingestion is INSERT INTO ON DUPLICATE KEY UPDATE because tests
        // have shown an approx 40% performance improvement when updating existing data over REPLACE
        // INTO.  REPLACE INTO also may cause issues with auto increment keys because duplicate rows
        // will be deleted and re-inserted (see
        // http://dev.mysql.com/doc/refman/5.7/en/replace.html). To use ON DUPLICATE KEY UPDATE with
        // LOAD DATA INFILE, data must be loaded into a temporary table and then an INSERT INTO
        // <destination> SELECT <temptable> ON DUPLICATE KEY UPDATE issued to move the data into the
        // destination table.

        if ( $this->options->force_load_data_infile_replace_into ) {
            $sql = "REPLACE INTO $qualifiedDestTableName (" . implode(',', $destColumnList) . ")\n" . $this->sourceQueryString;
        } else {
            $destColumnList = $this->quoteIdentifierNames($destColumnList);
            $destColumns = implode(',', $destColumnList);
            $updateColumnList = array_map(
                function ($s) {
                    return "$s=VALUES($s)";
                },
                $destColumnList
            );
            $updateColumns = implode(',', $updateColumnList);
            $sql = "INSERT INTO $qualifiedDestTableName\n($destColumns)\n" . $this->sourceQueryString
                . "\nON DUPLICATE KEY UPDATE $updateColumns";
        }

        $this->logger->info("Single-database ingest into " . $this->destinationEndpoint);
        $this->logger->debug($sql);

        if ( $this->getEtlOverseerOptions()->isDryrun() ) {
            return 0;
        }

        try {
            $totalRecordsProcessed = $this->destinationHandle->execute($sql);
        }
        catch (Exception $e) {
            $this->logAndThrowException(
                $e->getMessage(),
                array('exception' => $e)
            );
        }

        $this->logger->info(
            sprintf('%s: Processed %s records', get_class($this), number_format($totalRecordsProcessed))
        );

        // Display any warnings returned by the SQL

        $warnings = $this->destinationHandle->query("SHOW WARNINGS");

        if ( count($warnings) > 0 ) {
            $this->logSqlWarnings($warnings, $qualifiedDestTableName);
        }

        return $totalRecordsProcessed;

    }  // singleDatabaseIngest()

    /** -----------------------------------------------------------------------------------------
     * If the source and destination endpoints are not on the same database server, we are
     * populating multiple tables from a single query, or translation is being performed
     * on the data we will need to select the data, brining it back to this host, chunk it
     * into a file, and run LOAD DATA INFILE to load it into the database.
     *
     * @see allowSingleDatabaseOptimization()
     *
     * @return int The number of records processed
     * ------------------------------------------------------------------------------------------
     */

    private function multiDatabaseIngest()
    {
        // Set up one infile and output file descriptor for each destination

        $ingestStart = microtime(true);
        $infileList = array();
        $outFdList = array();
        $loadStatementList = array();
        $numDestinationTables = count($this->etlDestinationTableList);

        // Iterate over the destination field mappings rather than the destination table list because it
        // is possible that a table definition is provided but no data is mapped to it.

        foreach ( $this->destinationFieldMappings as $etlTableKey => $destFieldToSourceFieldMap ) {

            // The destination map is parsed in aRdbmsDestinationAction::parseDestinationFieldMap()
            // and any table with no mapping is not included. Keys are also verified to match a
            // destionation table name.

            $etlTable = $this->etlDestinationTableList[$etlTableKey];
            $qualifiedDestTableName = $etlTable->getFullName();

            $infileName = tempnam(
                sys_get_temp_dir(),
                sprintf('%s.data.ts_%s.%s', $etlTable->getFullName(false), time(), rand())
            );

            $this->logger->debug("Using temporary file '$infileName' for destination table key '$etlTableKey'");

            // Keys are table columns (destination) and values are query result columns (source)
            $destColumnList = array_keys($destFieldToSourceFieldMap);

            // The mysql documentation claims that file contents are interpreted using the character set
            // in the character_set_database system variable. However, I was not able to get this to work
            // Explicitly setting the CHARACTER SET does appear to work though.

            $characterSetOverride = '';
            if ( $this->options->load_data_infile_character_set ) {
                $characterSetOverride = "CHARACTER SET '" . $this->options->load_data_infile_character_set . "' ";
            }

            // The default method for ingestion is INSERT INTO ON DUPLICATE KEY UPDATE because tests
            // have shown an approx 40% performance improvement when updating existing data over
            // REPLACE INTO.  REPLACE INTO also may cause issues with auto increment keys because
            // duplicate rows will be deleted and re-inserted (see
            // http://dev.mysql.com/doc/refman/5.7/en/replace.html). To use ON DUPLICATE KEY UPDATE
            // with LOAD DATA INFILE, data must be loaded into a temporary table and then an INSERT
            // INTO <destination> SELECT <temptable> ON DUPLICATE KEY UPDATE issued to move the data
            // into the destination table.

            if ( $this->options->force_load_data_infile_replace_into ) {
                $loadStatement = "LOAD DATA LOCAL INFILE '$infileName' replace into table $qualifiedDestTableName "
                    . $characterSetOverride
                    . "FIELDS TERMINATED BY " . sprintf("0x%02x", ord($this->fieldSeparator))
                    . " OPTIONALLY ENCLOSED BY " . sprintf("0x%02x", ord($this->stringEnclosure))
                    . " ESCAPED BY " . sprintf("0x%02x", ord($this->escapeChar))
                    . " LINES TERMINATED BY " . sprintf("0x%02x", ord($this->lineSeparator)) . " "
                    . "(" . implode(',', $destColumnList) . ") "
                    . "SHOW WARNINGS";
            } else {
                $tmpTable = $etlTable->getSchema(true)
                    . "."
                    . $this->destinationEndpoint->quoteSystemIdentifier("tmp_" . $etlTable->name . "_" . time());

                $destColumnList = $this->quoteIdentifierNames($destColumnList);
                $destColumns = implode(',', $destColumnList);
                $updateColumnList = array_map(
                    function ($s) {
                        return "$s=VALUES($s)";
                    },
                    $destColumnList
                );
                $updateColumns = implode(',', $updateColumnList);

                $loadStatement = "CREATE TABLE $tmpTable LIKE $qualifiedDestTableName; "
                    . "ALTER TABLE $tmpTable DISABLE KEYS; "
                    . "LOAD DATA LOCAL INFILE '$infileName' INTO TABLE $tmpTable "
                    . $characterSetOverride
                    . "FIELDS TERMINATED BY " . sprintf("0x%02x", ord($this->fieldSeparator))
                    . " OPTIONALLY ENCLOSED BY " . sprintf("0x%02x", ord($this->stringEnclosure))
                    . " ESCAPED BY " . sprintf("0x%02x", ord($this->escapeChar))
                    . " LINES TERMINATED BY " . sprintf("0x%02x", ord($this->lineSeparator)) . " "
                    . "($destColumns); "
                    . "SHOW WARNINGS; "
                    . "INSERT INTO $qualifiedDestTableName ($destColumns) "
                    . "SELECT $destColumns FROM $tmpTable ON DUPLICATE KEY UPDATE $updateColumns; "
                    . "DROP TABLE $tmpTable;";

            }

            $this->logger->debug("LOAD statement for destination table key '$etlTableKey' " . $this->destinationEndpoint . ":\n$loadStatement");

            $infileList[$etlTableKey] = $infileName;
            $loadStatementList[$etlTableKey] = $loadStatement;

        }  // foreach ( $this->destinationFieldMappings as $etlTableKey => $destFieldToSourceFieldMap )

        $this->logger->info("Multi-database ingest into " . $this->destinationEndpoint);

        if ( $this->getEtlOverseerOptions()->isDryrun() ) {
            $this->logger->debug("Source query " . $this->sourceEndpoint . ":\n" . $this->sourceQueryString);
            // If this is DRYRUN mode clean up the files that tempnam() created
            foreach ( $infileList as $etlTableKey => $infileName ) {
                @unlink($infileName);
            }
            return 0;
        }

        // Open file descriptors. This is not done in the loop above so we can support DRYRUN.

        foreach ( $infileList as $etlTableKey => $infileName ) {
            if ( false === ($outFd = fopen($infileName, 'w')) ) {
                $this->logAndThrowException(
                    sprintf("Failed to open temporary file for database ingest: '%s'", $infileName)
                );
            }
            $outFdList[$etlTableKey] = $outFd;
        }  // foreach ( $infileList as $etlTableKey => $infileName )

        // Turn off buffering if necessary. This is a MySQL specific optimization.

        $originalBufferedQueryAttribute = null;
        if ( ! $this->options->buffered_query && $this->sourceEndpoint instanceof Mysql ) {
            $this->logger->info("Switching to un-buffered query mode");
            $pdo = $this->sourceHandle->handle();
            $originalBufferedQueryAttribute = $pdo->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

            // On a busy server, it is possible that the mysql server will close the
            // connection on us if we are using an unbuffered query and the loading of the
            // data takes longer than the net_write_timeout (See
            // https://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_net_write_timeout)
            //
            // This may happen using an unbuffered query because the server writes result
            // data to the connection (presumably there is some buffer) and the client
            // reads the data, transforms it, and adds it to a load file. When the number
            // of records hits a threshold, the files are loaded and the process does not
            // read from the connection during loading. If loading takes longer than the
            // net_write_timeout, the server will close the connection on us. The client
            // will read whatever is left in the result buffer and assume that all is well
            // until another operation is attempted on the connection at which point we
            // will get the ambiguous "MySQL server has gone away" error (see
            // https://dev.mysql.com/doc/refman/5.7/en/gone-away.html).

            $result = $this->sourceHandle->query(
                "SHOW SESSION VARIABLES WHERE Variable_name = 'net_write_timeout'"
            );

            $currentTimeout = 0;
            if ( 0 != count($result) ) {
                $currentTimeout = $result[0]['Value'];
                $this->logger->debug("Current net_write_timeout = $currentTimeout");
            }

            // Base the new timeout on the number of tables we are loading data into and the
            // number of records that we are loading.

            $newTimeout = $numDestinationTables * $this->netWriteTimeoutSecondsPerFileChunk;

            if ( $newTimeout > $currentTimeout ) {
                $sql = sprintf('SET SESSION net_write_timeout = %d', $newTimeout);
                $this->executeSqlList(array($sql), $this->sourceEndpoint);
            }
        }

        // The number of source records processed during the load phase.
        $numSourceRecordsProcessed = 0;

        // Total number of records processed during the load phase. This may differ from
        // the number of soruce records because the transform step may transform one
        // record into multiple.
        $totalRecordsProcessed = 0;

        // Number of records in the current load file
        $numRecordsInFile = 0;

        $sourceStatement = $this->getSourceData();

        // NOTE: Much of the time spent in the data processing loop is fetching data from the
        // database. An analysis of the time spent in the entire loop using a sample query
        // (OsgJobsIngestor 2015-12-15 through 2015-12-31, 973,052 rows) shows the following. It
        // takes 36 seconds for the database to return the data.
        //
        // mysql> show profile;
        // +--------------------------------+-----------+
        // | Status                         | Duration  |
        // +--------------------------------+-----------+
        // | Sending data                   | 35.931661 |
        //
        // 1. Loop over statement fetching all rows and doing nothing else
        //
        // Total time: 39.1s
        //   Data fetch statements: 37.3s
        //
        // 2. Load data, without optimizing 2nd loop. Note that 13.7 + 6.9 + 15.8 = 36.4
        //
        // Total time: 124s
        //   Data fetch statements: 13.7s
        //   Handle NULL and empty values in the data (1st loop): 6.9s
        //   Write the requested rows to the infiles (non-optimized 2nd loop): 15.8s
        //   Load data: 83s
        //
        // 3. Load data, optimize 2nd loop. Note that 26.8 + 6.9 + 6 = 39.7
        //
        // Total time: 128s
        //   Data fetch statements: 26.8s
        //   Handle NULL and empty values in the data (1st loop): 6.9s
        //   Write the requested rows to the infiles (optimized 2nd loop): 6.0s
        //   Load data: 83s
        //
        // Note that the time saved by the optimization is simply spent waiting for the data. Unless
        // the time taken by the loop exceeds the time needed to retrieve the data from the
        // database, optimizations are not worthwhile. In fact, optimizing the speed of the loop
        // actually *hinders* performance in the tests! This was verified with 4,195,524 and 124,120
        // rows.

        $orderId = 0;

        while ( $srcRecord = $sourceStatement->fetch(PDO::FETCH_ASSOC) ) {

            $numSourceRecordsProcessed++;

            // Some historical ingestors use an order_id and treat it similar to an auto-increment
            // field, setting its value starting at 0 and incrementing for each record. It is not
            // clear if this is used anywhere, but the functionality is maintained here for
            // compatibility. Note that this does not work when adding fields into a table (e.g.,
            // only when the table is truncated) and will not work properly in cases where the order
            // is relative to a key. For example, if a key is resource_id and the order should be
            // maintained for each unique resource_id we cannot use this method. To not overwrite
            // existing data, only set the order_id if the source field exists and is NULL.

            if ( array_key_exists('order_id', $srcRecord) && null === $srcRecord['order_id'] ) {
                $srcRecord['order_id'] = $orderId++;
            }

            // Note that an array of transformed records is returned because a single source
            // record may be transformed into multiple records.

            $transformedRecords = $this->transform($srcRecord, $orderId);

            foreach ( $transformedRecords as $record ) {

                // Write the requested records to the infile for each destination, performing
                // any requested mapping.

                foreach ( $this->destinationFieldMappings as $etlTableKey => $destinationFields ) {

                    // In practice optimization when the entire source is mapped to the destination has
                    // little effect on performance for larger datasets, and even worsens performance by
                    // several seconds on datasets of 4+M. The majority of the execution time of the
                    // outer loop is spent waiting for the result (data) of the query.  Time saved here
                    // is likely spent waiting on the fetch() from the server. Verify by profiling the
                    // query and checking the time for the "Sending data" phase:
                    //
                    // set profiling=1;
                    // select ...
                    // show profile

                    $destRecord = array();

                    foreach ($destinationFields as $tableDestField => $sourceQueryField) {
                        $destRecord[$tableDestField] = $record[$sourceQueryField];
                    }

                    fwrite($outFdList[$etlTableKey], implode($this->fieldSeparator, $destRecord) . $this->lineSeparator);

                }  // foreach ($destinationFields as $destField)

                $totalRecordsProcessed++;
                $numRecordsInFile++;

                // Periodically log the number of records processed

                if (0 == $totalRecordsProcessed % self::NUM_RECORDS_PER_LOG_MSG) {
                    $this->logger->info(
                        sprintf('%s: Processed %s records', get_class($this), number_format($totalRecordsProcessed))
                    );
                }

                // If we've reached the maximum number of records per chunk, load the data.

                if ( $numRecordsInFile == $this->dbInsertChunkSize ) {
                    $numFilesLoaded = 0;
                    $loadFileStart = microtime(true);

                    foreach ( $loadStatementList as $etlTableKey => $loadStatement ) {
                        try {
                            fflush($outFdList[$etlTableKey]);
                            $output = $this->_dest_helper->executeStatement($loadStatement);

                            $this->logger->debug(
                                sprintf("Loaded %s records into '%s'", number_format($numRecordsInFile), $etlTableKey)
                            );

                            if ( count($output) > 0 ) {
                                $this->logSqlWarnings($output, $etlTableKey);
                            }

                            // Clear the infile

                            ftruncate($outFdList[$etlTableKey], 0);
                            rewind($outFdList[$etlTableKey]);
                            $numFilesLoaded++;
                        }
                        catch (Exception $e) {
                            $this->logAndThrowException(
                                $e->getMessage(),
                                array(
                                    'exception' => $e,
                                    'sql' => $loadStatement
                                )
                            );
                        }
                    }  // foreach ( $loadStatementList as $etlTableKey => $loadStatement )

                    $this->logger->debug(
                        sprintf('Loaded %d files in %ds', $numFilesLoaded, microtime(true) - $loadFileStart)
                    );
                    $loadFileStart = microtime(true);
                    $numRecordsInFile = 0;

                }  // if ( $numRecordsInFile == $this->dbInsertChunkSize )

            } // foreach ( $record )

        }  // while ( $srcRecord = $srcStatement->fetch(...) )

        // Process the final chunk.

        if ( $numRecordsInFile > 0 ) {
            $numFilesLoaded = 0;
            $loadFileStart = microtime(true);

            foreach ( $loadStatementList as $etlTableKey => $loadStatement ) {
                try {
                    fclose($outFdList[$etlTableKey]);
                    unset($outFdList[$etlTableKey]);
                    $output = $this->_dest_helper->executeStatement($loadStatement);

                    $this->logger->debug(
                        sprintf("Loaded %s records into '%s'", number_format($numRecordsInFile), $etlTableKey)
                    );

                    if ( count($output) > 0 ) {
                        $this->logSqlWarnings($output, $etlTableKey);
                    }

                }
                catch (Exception $e) {
                    $this->logAndThrowException(
                        $e->getMessage(),
                        array(
                            'exception' => $e,
                            'sql' => $loadStatement
                        )
                    );
                }

                $numFilesLoaded++;

            }  // foreach ( $loadStatementList as $etlTableKey => $loadStatement )

            $this->logger->debug(sprintf('Loaded %d files in %ds', $numFilesLoaded, microtime(true) - $loadFileStart));

        }  // if ( $numRecordsInFile)

        // Cleanup

        foreach ($outFdList as $outFd) {
            fclose($outFd);
        }
        foreach ($infileList as $fileName) {
            unlink($fileName);
        }

        $this->logger->info(
            sprintf(
                '%s: Processed %s records (%s source records) in %ds',
                get_class($this),
                number_format($totalRecordsProcessed),
                number_format($numSourceRecordsProcessed),
                microtime(true) - $ingestStart
            )
        );

        // Return buffering to its original state.  This is a MySQL specific optimization.

        if ( ! $this->options->buffered_query && $this->sourceEndpoint instanceof Mysql ) {
            $this->logger->info(
                sprintf("Returning buffered query mode to: %s", ($originalBufferedQueryAttribute ? "true" : "false"))
            );
            $pdo = $this->sourceHandle->handle();
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $originalBufferedQueryAttribute);
        }

        return $totalRecordsProcessed;

    }  // multiDatabaseIngest()

    /** -----------------------------------------------------------------------------------------
     * Perform a transformation on a single data record (row). Transformation may alter
     * the values of the record and may create multiple records from a single record, but
     * it should not modify the struture of the record itself (e.g., the keys). Because we
     * support the ability to transform a single source record into multiple result
     * records, an array of records is returned, even for a single result record.
     *
     * @param array $record An associative array containing the source record where the
     *   keys are the field names.
     * @param int $orderId A reference to the relative ordering value used to set the value of an
     *   order_id field. @see multiDatabaseIngest()
     *
     * @return array A 2-dimensional array of potentially transformed records where each
     *  element is an individual record.
     * ------------------------------------------------------------------------------------------
     */

    protected function transform(array $srcRecord, &$orderId)
    {
        foreach ( $srcRecord as $key => &$value ) {
            if ( null === $value ) {
                // Transform NULL values for MySQL LOAD FILE
                $value = '\N';
            } elseif ( '' === $value ) {
                $value = $this->stringEnclosure . '' . $this->stringEnclosure;
            } elseif (strpos($value, $this->lineSeparator) !== false
                || strpos($value, $this->fieldSeparator) !== false
                || strpos($value, $this->stringEnclosure) !== false
                || strpos($value, $this->escapeChar) !== false) {
                // if the string contains any special characters it is enclosed in the stringEnclosure
                // occurences of the stringEnclosure and the escape character are escaped

                $search = array($this->escapeChar, $this->stringEnclosure);
                $replace = array($this->escapeChar . $this->escapeChar, $this->escapeChar . $this->stringEnclosure);

                $value = $this->stringEnclosure . str_replace($search, $replace, $value) . $this->stringEnclosure;
            }
        }

        return array($srcRecord);

    }  // transform()

    /** ------------------------------------------------------------------------------------------
     * Determine if we can support optimizations for queries within a single database. Not
     * only must our source and destination databases be the same, but we cannot optimize
     * if we are dealing with multiple destination tables, or other factors.  Optimization
     * may be performed as a "INSERT...SELECT" directly in the database rather than a
     * SELECT returning the data and then a separate INSERT.
     *
     * @return boolean TRUE if database optimization is allowed, FALSE if not.
     * ------------------------------------------------------------------------------------------
     */

    protected function allowSingleDatabaseOptimization()
    {
        if ( ! $this->options->optimize_query ) {
            $this->logger->debug("Query optimization disabled");
            return false;
        }

        // Automatically detect if a child class has extended the transform() method and
        // force multi-database ingest.

        try {
            $reflector = new \ReflectionMethod($this, 'transform');
            $prototype = $reflector->getPrototype();
            $this->logger->debug(
                $prototype->class . '::' . $prototype->name . '()'
                . " overriden by "
                . $reflector->class . '::' . $reflector->name . '()'
            );
            return false;
        } catch ( \ReflectionException $e ) {
            // Do nothing, transform() has not been overriden.
        }

        // Same database type?

        if ( ! $this->sourceEndpoint->getType() == $this->destinationEndpoint->getType() ) {
            $this->logger->debug("Source and destination endpoints are different types");
            return false;
        }

        // Endpoints on the same server?

        if ( ! $this->sourceEndpoint->isSameServer($this->destinationEndpoint) ) {
            $this->logger->debug("Source and destination endpoints are on different servers");
            return false;
        }

        // Can't optimize when writing data to more than 1 destination table

        if ( count($this->etlDestinationTableList) > 1 ) {
            $this->logger->debug("Multiple destination tables being populated");
            return false;
        }

        // When creating the INSERT INTO ... SELECT statement in singleDatabaseIngest() we use the
        // destination field map keys to generate the destination column list and use
        // $this->sourceQueryString as the source query. These need to have the same fields (and be
        // in the same order, but we will ensure proper order when generating the field list).

        reset($this->destinationFieldMappings);

        if ( 0 != count(array_diff($this->sourceRecordFields, array_keys(current($this->destinationFieldMappings)))) ) {
            $this->logger->debug("Mapping a subset of the source query fields");
            return false;
        }

        return true;

    }  // allowSingleDatabaseOptimization()
}  // class pdoIngestor
