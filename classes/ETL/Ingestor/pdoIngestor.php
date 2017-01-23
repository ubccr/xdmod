<?php
/* ==========================================================================================
 * Ingestor using the PDO driver. The pdoIngestor is responsible for handling the bulk of
 * the operations necessary for ingesting data using PDO source and destination endpoints. The
 * aRdbmsDestinationAction class implements functionality specific to supporting a database
 * destination.
 *
 * Optionally, the following methods may be overriden to perform additional actions or override
 * default actions:
 *
 * getDestinationFields() (if the fields could not be parsed from the source query)
 * getPreActionQueries()
 * getPostActionQueries()
 * getTruncateQueries()
 *
 * The ingestion process is as follows:
 *
 * 1. Collect and verify query information by calling methods in the extending class.
 * 2. Run pre-ingestion queries, if defined.
 * 3. Disable foreign keys on the database.
 * 4. Truncate the destination table using the truncate SQL provided by the child class or a simple
 *    TRUNCATE TABLE statement.
 * 5. Execute the query on the source database.
 * 6. Iterate over the result set, writing each row to a file that will be used in a "load data
 *    infile" statement to load the data, possibly to a database on another host.  The load is
 *    broken into chunks defined by MAX_ROWS_PER_INFILE.
 * 7. Run post-ingestion queries, if defined.
 * 8. Re-enable foreign keys on the database.
 * 9. Cleanup
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
use ETL\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\Utilities;
use ETL\DbEntity\Query;

use CCR\DB\MySQLHelper;
use \PDOException;
use \PDO;
use \Log;

class pdoIngestor extends aIngestor
{
    // Maximum number of times to attempt to execute the source query
    const MAX_QUERY_ATTEMPTS = 3;

    // Write a log message after processing this many source rows
    const NUM_ROWS_PER_LOG_MSG = 100000;

    // Maximum number of rows to import at once
    const MAX_ROWS_PER_INFILE = 250000;

    // ------------------------------------------------------------------------------------------

    // An 2-dimensional associative array where the keys are ETL table definition keys and the
    // values are a mapping between ETL table columns (keys) and source query columns (values).
    protected $destinationFieldMappings = array();

    // Set to TRUE to indicate a destination field mapping was not specified in the configuration
    // file and was auto-generated using all source query columns.  This can be used for
    // optimizations later.
    protected $fullSourceToDestinationMapping = false;

    // Query for extracting data from the source endpoint.
    private $sourceQueryString = null;

    // A Query object containing the source query for this ingestor
    protected $etlSourceQuery = null;

    // The list of field names in the source query
    protected $availableSourceQueryFields = null;

    /* ------------------------------------------------------------------------------------------
     * Set up data endpoints and other options.
     *
     * @param IngestorOptions $options Options specific to this Ingestor
     * @param EtlConfiguration $etlConfig Parsed configuration options for this ETL
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);

        // Get the handles for the various database endpoints

        $this->utilityEndpoint = $etlConfig->getDataEndpoint($this->options->utility);
        if ( ! $this->utilityEndpoint instanceof iRdbmsEndpoint ) {
            $this->utilityEndpoint = null;
            $msg = "Utility endpoint does not implement of ETL\\DataEndpoint\\iRdbmsEndpoint";
            $this->logAndThrowException($msg);
        }
        $this->utilityHandle = $this->utilityEndpoint->getHandle();

        $this->sourceEndpoint = $etlConfig->getDataEndpoint($this->options->source);
        if ( ! $this->sourceEndpoint instanceof iRdbmsEndpoint ) {
            $this->sourceEndpoint = null;
            $msg = "Source endpoint is not an instance of ETL\\DataEndpoint\\iRdbmsEndpoint";
            $this->logAndThrowException($msg);
        }
        $this->sourceHandle = $this->sourceEndpoint->getHandle();
        $this->logger->debug("Source endpoint: " . $this->sourceEndpoint);

        if ( "mysql" == $this->destinationHandle->_db_engine ) {
            $this->_dest_helper = MySQLHelper::factory($this->destinationHandle);
        }

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Retrieve the query data and perform any necessary verification.
     *
     * @throws Exception if any query data was not int the correct format.
     * ------------------------------------------------------------------------------------------
     */

    protected function initialize()
    {
        if ( $this->isInitialized() ) {
            return;
        }

        $this->initialized = false;

        parent::initialize();

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

            $this->etlOverseerOptions->applyOverseerRestrictions($this->etlSourceQuery, $this->sourceEndpoint, $this->overseerRestrictionOverrides);

        }  // ( null === $this->etlSourceQuery )

        // By default, queries may include references to the schema names so they can be changed in the
        // config file.

        $this->variableMap["UTILITY_SCHEMA"] = $this->utilityEndpoint->getSchema();
        $this->variableMap["SOURCE_SCHEMA"] = $this->sourceEndpoint->getSchema();

        $this->sourceQueryString = $this->getSourceQueryString();

        if ( null !== $this->sourceQueryString &&
             ! (is_string($this->sourceQueryString) || empty($this->sourceQueryString)) )
        {
            $msg = "Source query must be null or a non-empty string";
            $this->logAndThrowException($msg);
        }

        // Get the list of available source query fields. If we have described the source query in
        // the JSON config, use the record keys otherwise we need to parse the SQL string.

        $this->availableSourceQueryFields =
            ( null !== $this->etlSourceQuery
              ? array_keys($this->etlSourceQuery->getRecords())
              : $this->getSqlColumnNames($this->sourceQueryString) );

        $this->destinationFieldMappings = $this->getDestinationFields();

        // Generate and verify destination fields.
        //
        // Use Cases:
        //
        // >= 1 table & mismatch in # tables vs # dest fields = error
        // 1 table & 0 field list = create destination fields from query
        // 1 table & 1 field list = verify columns
        // >= 1 table & # tables = # dest fields = verify columns
        //
        // 1. If a single destination table definition has been provided and destination fields have
        // not been defined, create the destination fields assuming all of the columns from the
        // query will be used.
        //
        // 2. If multiple destination tables have been defined the destination fields must be
        // provided in the configuration or a subclass. Verify that the number of tables and
        // destination field lists match.
        //
        // 3. Verify that all destination field mappings are valid.

        if ( 1 == count($this->etlDestinationTableList)
             && 0 == count($this->destinationFieldMappings) )
        {
            // Use all of the source columns as destination fields. Check that the all of the
            // parsed columns are found in the table definition. If not, throw an error and the
            // developer will need to provide them.

            reset($this->etlDestinationTableList);
            $etlTableKey = key($this->etlDestinationTableList);

            // We only need to parse the SQL if it has been provided as a string, otherwise use:
            // array_keys($this->etlSourceQuery->getRecords());

            $this->destinationFieldMappings[$etlTableKey] =
                array_combine($this->availableSourceQueryFields, $this->availableSourceQueryFields);
            $this->logger->debug("Destination fields parsed from source query (table definition key '$etlTableKey'): " .
                                 implode(", ", $this->destinationFieldMappings[$etlTableKey]));
            $this->fullSourceToDestinationMapping = true;
        } elseif ( count($this->etlDestinationTableList) > 1
                    && count($this->destinationFieldMappings) != count($this->etlDestinationTableList) )
        {
            if ( 0 == count($this->destinationFieldMappings) ) {
                $msg = "destination_field_map must be defined when > 1 table definitions are provided";
            } else {
                $msg = "Destination fields missing for destination tables (" .
                    implode(",", array_diff(array_keys($this->etlDestinationTableList), array_keys($this->destinationFieldMappings))) .
                    ")";
            }
            $this->logAndThrowException($msg);
        }

        // Ensure that the keys in the destination record map match a defined table

        foreach ( array_keys($this->destinationFieldMappings) as $destinationTableKey ) {
            if ( ! array_key_exists($destinationTableKey, $this->etlDestinationTableList) ) {
                $msg = "Destination record map references undefined table: $destinationTableKey";
                $this->logAndThrowException($msg);
            }
        }

        // Verify that the destination column keys match the table columns and the values match a
        // column in the query.

        $undefinedDestinationTableColumns = array();
        $undefinedSourceQueryColumns = array();

        foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {
            $availableTableFields = $etlTable->getColumnNames();

            // Ensure destination table columns exist (keys)

            $destinationTableMap = array_keys($this->destinationFieldMappings[$etlTableKey]);
            $missing = array_diff($destinationTableMap, $availableTableFields);
            if ( 0  != count($missing) ) {
                $undefinedDestinationTableColumns[] = "Table '$etlTableKey' has undefined table columns/keys (" .
                    implode(",", $missing) . ")";
            }

            // Ensure source query columns exist (values)
            $sourceQueryFields = $this->destinationFieldMappings[$etlTableKey];
            $missing = array_diff($sourceQueryFields, $this->availableSourceQueryFields);
            if ( 0  != count($missing) ) {
                $missing = array_map(
                    function ($k, $v) {
                        return "$k = $v";
                    },
                    array_keys($missing),
                    $missing
                );
                $undefinedSourceQueryColumns[] = "Table '$etlTableKey' has undefined source query records for keys (" .
                    implode(", ", $missing) . ")";
            }

        }  // foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable )

        if ( 0 != count($undefinedDestinationTableColumns) || 0 != count($undefinedSourceQueryColumns) ) {
            $msg = "Undefined keys or values in ETL destination_record_map. ";
            if ( 0 != count($undefinedDestinationTableColumns) ) {
                $msg .= implode("; ", $undefinedDestinationTableColumns) . ", ";
            }
            if ( 0 != count($undefinedSourceQueryColumns) ) {
                $msg .= implode("; ", $undefinedSourceQueryColumns);
            }
            $this->logAndThrowException($msg);
        }

        $this->initialized = true;

        return true;

    } // initialize()

    /* ------------------------------------------------------------------------------------------
     * By default, we will attempt to parse the destination fields from the source query unless this
     * method returns a non-null value. Child classes may override this method if parsing the source
     * query is not appropriate.
     *
     * @return NULL to attempt to parse the destination fields from the source query, or an array
     *   where the keys match etl table definitions and values map table columns (destination) to
     *   query result columns (source).
     *   ------------------------------------------------------------------------------------------
     */

    protected function getDestinationFields()
    {
        if ( ! isset($this->parsedDefinitionFile->destination_record_map) ) {
            return null;
        } elseif ( ! is_object($this->parsedDefinitionFile->destination_record_map) ) {
            $msg = "destination_fields must be an object where keys match table definition keys";
            $this->logAndThrowException($msg);
        }

        $destinationFieldMappings = array();

        foreach ( $this->parsedDefinitionFile->destination_record_map as $etlTableKey => $fieldMap ) {
            if ( ! is_object($fieldMap) ) {
                $msg = "Destination field map for table '$etlTableKey' must be an object";
                $this->logAndThrowException($msg);
            } elseif ( 0 == count(array_keys((array) $fieldMap)) ) {
                $msg = "destination_record_map for '$etlTableKey' is empty";
                $this->logger->warning($msg);
            }
            // Convert the field map from an object to an associative array. Keys are table columns
            // (destination) and values are query result columns (source)
            $destinationFieldMappings[$etlTableKey] = (array) $fieldMap;
        }

        return $destinationFieldMappings;
    }  // getDestinationFields()

    /* ------------------------------------------------------------------------------------------
     * Get the query to be run against the source data endpoint that will extract the data.
     *
     * @return A string containing the query on the source endpoint.
     * ------------------------------------------------------------------------------------------
     */

    protected function getSourceQueryString()
    {
        if ( null === $this->etlSourceQuery ) {
            $msg ="ETL source query object not instantiated. " .
                "Perhaps it is not specified in the definition file and not implemented in the Ingestor.";
            $this->logAndThrowException($msg);
        }

        $sql = $this->etlSourceQuery->getSelectSql();
        if ( null !== $this->variableMap ) {
            $sql = Utilities::substituteVariables($sql, $this->variableMap);
        }

        return $sql;

    }  // getSourceQueryString()

    /* ------------------------------------------------------------------------------------------
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

        $this->logger->info(get_class($this) . ': Querying...');

        // Execute the source query. If we are unable to connect, continue to attempt up the
        // MAX_QUERY_ATTEMPTS

        $query_success = false;
        $n_attempts = 0;

        while (false == $query_success) {
            $n_attempts += 1;

            try {

                $srcStatement = $this->sourceHandle->prepare($this->sourceQueryString);

                $start = microtime(true);
                $srcStatement->execute();
                $query_success = true;

            } catch (PDOException $e) {

                // ER_LOCK_DEADLOCK: Deadlock found when trying to get lock; try restarting transaction
                if ( $srcStatement->errorCode() != "40001" ) {
                    $this->logAndThrowSqlException($this->sourceQueryString, $e, "Error querying source");
                } elseif ( $n_attempts > self::MAX_QUERY_ATTEMPTS ) {
                    $msg = "Could not execute source query after " . self::MAX_QUERY_ATTEMPTS . " attempts. Exiting.";
                    $this->logAndThrowException($msg);
                }

                $this->logger->info(
                    get_class($this)
                    . ': Query was cancelled by server with error '
                    . $srcStatement->errorCode() . '. Retries left = ' . (self::MAX_QUERY_ATTEMPTS - $n_attempts)
                );
            }
        }  // while (FALSE == $query_success)

        // We can query the number of source rows if we are using a buffered query.

        if ( $this->options->buffered_query ) {
            $this->logger->debug("Source row count: " . $srcStatement->rowCount());
        }

        return $srcStatement;

    }  // getSourceData()

  /* ------------------------------------------------------------------------------------------
   * By default, there are no pre-execution tasks.
   *
   * @see iAction::performPreExecuteTasks()
   * ------------------------------------------------------------------------------------------
   */

    protected function performPreExecuteTasks() {

        // ------------------------------------------------------------------------------------------
        // Update the start/end dates for this query and get the source query string. It is important
        // to do it in the pre-execute stage because if we are chunking our ingest it will get
        // updated every time.

        if ( null === $this->etlSourceQuery && isset($this->parsedDefinitionFile->source_query) ) {
            $this->logger->info("Update source query date range");
            // If supported by the source query, set the date ranges here

            $startDate = $this->sourceEndpoint->quote($this->etlOverseerOptions->getCurrentStartDate());
            $endDate = $this->sourceEndpoint->quote($this->etlOverseerOptions->getCurrentEndDate());

            if ( false === $this->etlSourceQuery->setDateRange($startDate, $endDate) ) {
                $msg = "Ingestion date ranges not supported by source query";
                $this->logger->info($msg);
            }
        }

        $this->sourceQueryString = $this->getSourceQueryString();

        // ------------------------------------------------------------------------------------------
        // ETL table management. We can extract the table name, schema, and column names from this
        // object.

        $sqlList = array();
        $disableForeignKeys = false;

        try {

            // Bring the destination table in line with the configuration if necessary.  Note that
            // manageTable() is DRYRUN aware.

            foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {
                $qualifiedDestTableName = $etlTable->getFullName();

                if ( "myisam" == strtolower($etlTable->getEngine()) ) {
                    $disableForeignKeys = true;
                    if ( $this->options->disable_keys ) {
                        $this->logger->info("Disable keys on $qualifiedDestTableName");
                        $sqlList[] = "ALTER TABLE $qualifiedDestTableName DISABLE KEYS";
                    }
                }

                $this->manageTable($etlTable, $this->destinationEndpoint);

            }  // foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable )

        } catch ( Exception $e ) {
            $msg = "Error managing ETL table for " . $this->getName() . ": " . $e->getMessage();
            $this->logAndThrowException($msg);
        }

        if ( $disableForeignKeys ) {
            // See http://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_foreign_key_checks
            $sqlList[] = "SET FOREIGN_KEY_CHECKS = 0";
        }

        $this->executeSqlList($sqlList, $this->destinationHandle, "Pre-execute tasks");

        return true;

    }  // performPreExecuteTasks()

    /* ------------------------------------------------------------------------------------------
     * By default, there are no pre-execution tasks.
     *
     * @see iAction::performPostExecuteTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performPostExecuteTasks($numRecordsProcessed)
    {
        $sqlList = array();
        $enableForeignKeys = false;

        foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {
            $qualifiedDestTableName = $etlTable->getFullName();

            if ( "myisam" == strtolower($etlTable->getEngine()) ) {
                $enableForeignKeys = true;
                if ( $this->options->disable_keys ) {
                    $this->logger->info("Enable keys on $qualifiedDestTableName");
                    $sqlList[] = "ALTER TABLE $qualifiedDestTableName ENABLE KEYS";
                }
            }

            if ( $numRecordsProcessed > 0 ) {
                $sqlList[] = "ANALYZE TABLE $qualifiedDestTableName";
            }
        }

        if ( $enableForeignKeys ) {
            // See http://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_foreign_key_checks
            $sqlList[] = "SET FOREIGN_KEY_CHECKS = 1";
        }

        $this->executeSqlList($sqlList, $this->destinationHandle, "Post-execute tasks");

        return true;
    }  // performPostExecuteTasks()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::execute()
     * ------------------------------------------------------------------------------------------
     */

    protected function _execute()
    {
        // Since the overseer may split the ingestion period up into chunks. Apply the current
        // start/end date range here.

        if ( null !== $this->etlSourceQuery) {
            $this->etlOverseerOptions->applyOverseerRestrictions($this->etlSourceQuery, $this->sourceEndpoint, $this->overseerRestrictionOverrides);
            $this->sourceQueryString = $this->getSourceQueryString();
        }

        $this->logger->debug("Source query:\n" . $this->sourceQueryString);

        // ------------------------------------------------------------------------------------------
        // Main ingest

        $totalRowsProcessed = 0;

        // If we can execute the ingestion query directly on the server instead of querying,
        // returning the data locally, chunking it into a file, and loading it on the remote server
        // we may gaina speed enhancement. For ~860,000 records this executes in roughly 65% of the
        // time.

        $optimize = $this->allowSingleDatabaseOptimization();

        if ( $optimize ) {
            $this->logger->info("Allowing same-server SQL optimizations");
            $totalRowsProcessed = $this->singleDatabaseIngest();
        } else {
            $totalRowsProcessed = $this->multiDatabaseIngest();
        }

        return $totalRowsProcessed;

    }  // _execute()

    /* ------------------------------------------------------------------------------------------
     * If the source and destination endpoints are on the same database server we can optimize the
     * ingestion by executing an INSERT...SELECT statement directly on the server rather than
     * selecting the data, brining it back to this host, chunking it into a file, and running LOAD
     * DATA INFILE to load it back into the database. Tests on 875K records show a 29% improvement
     * using INSERT...SELECT and a 48% improvement using INSERT...SELECT and disabling keys during
     * load.
     *
     * NOTE: This method assumes that data is being mapped from one source table to one destination
     * table and all columns are being used.
     *
     * @return The number of rows processed
     * ------------------------------------------------------------------------------------------
     */

    private function singleDatabaseIngest()
    {
        reset($this->destinationFieldMappings);
        reset($this->etlDestinationTableList);
        $qualifiedDestTableName = current($this->etlDestinationTableList)->getFullName();

        // Keys are table definition columns (destination) and values are query result columns (source). For a
        // single database ingest it is assumed that no mapping is taking place (i.e., all source
        // columns are mapped to the same destination columns)
        $destColumnList = array_keys(current($this->destinationFieldMappings));

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
            $destColumns = implode(',', $destColumnList);
            $updateColumnList = array_map(
                function ($s) {
                    return "$s=VALUES($s)";
                },
                $destColumnList
            );
            $updateColumns = implode(',', $updateColumnList);
            $sql = "INSERT INTO $qualifiedDestTableName ($destColumns) " . $this->sourceQueryString
                . "\nON DUPLICATE KEY UPDATE $updateColumns";
        }

        $this->logger->debug($sql);

        if ( $this->etlOverseerOptions->isDryrun() ) {
            return 0;
        }

        $totalRowsProcessed = $this->destinationHandle->execute($sql);
        $this->logger->info("Processed: $totalRowsProcessed (each replaced row counts as 2 records)");

        return $totalRowsProcessed;

    }  // singleDatabaseIngest()

    /* ------------------------------------------------------------------------------------------
     * If the source and destination endpoints are not on the same database server (or other
     * criteria are met such as needing to update rather than replace a row) we will need to select
     * the data, brining it back to this host, chunk it into a file, and run LOAD DATA INFILE to
     * load it into the database.
     *
     * @return The number of rows processed
     * ------------------------------------------------------------------------------------------
     */

    private function multiDatabaseIngest()
    {

        // Set up one infile and output file descriptor for each destination

        $infileList = array();
        $outFdList = array();
        $loadStatementList = array();

        foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {
            $qualifiedDestTableName = $etlTable->getFullName();

            // If there are no source query columns mapped to this table, skip it.

            if ( 0 == count($this->destinationFieldMappings[$etlTableKey]) ) {
                continue;
            }

            $infileName = tempnam(
                sys_get_temp_dir(),
                sprintf('%s.data.ts_%s.%s', $etlTable->getFullName(false), time(), rand())
            );

            $this->logger->debug("Using temporary file '$infileName' for destination table key '$etlTableKey'");

            // Keys are table columns (destination) and values are query result columns (source)
            $destColumnList = array_keys($this->destinationFieldMappings[$etlTableKey]);

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
                    . "fields terminated by 0x1e optionally enclosed by 0x1f lines terminated by 0x1d "
                    . "(" . implode(',', $destColumnList) . ")";
            } else {
                $tmpTable = $etlTable->getSchema(true)
                    . "."
                    . $this->destinationEndpoint->quoteSystemIdentifier("tmp_" . $etlTable->getName() . "_" . time());

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
                    . "FIELDS TERMINATED BY 0x1e OPTIONALLY ENCLOSED BY 0x1f LINES TERMINATED BY 0x1d "
                    . "($destColumns); "
                    . "INSERT INTO $qualifiedDestTableName ($destColumns) "
                    . "SELECT $destColumns FROM $tmpTable ON DUPLICATE KEY UPDATE $updateColumns; "
                    . "DROP TABLE $tmpTable;";

            }

            $this->logger->debug("load statement for destination table key '$etlTableKey'\n$loadStatement");

            $infileList[$etlTableKey] = $infileName;
            $loadStatementList[$etlTableKey] = $loadStatement;

        }  // foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable )

        if ( $this->etlOverseerOptions->isDryrun() ) {
            // If this is DRYRUN mode clean up the files that tempnam() created
            foreach ( $infileList as $etlTableKey => $infileName ) {
                @unlink($infileName);
            }
            return 0;
        }

        // Open file descriptors. This is not done in the loop above so we can support DRYRUN.

        foreach ( $infileList as $etlTableKey => $infileName ) {
            if ( false === ($outFd = fopen($infileName, 'w')) ) {
                $msg = "Failed to open temporary file for database ingest: '$infileName'";
                $this->logAndThrowException($msg);
            }
            $outFdList[$etlTableKey] = $outFd;
        }  // foreach ( $infileList as $etlTableKey => $infileName )

        // Turn off buffering if necessary. This is a MySQL specific optimization.

        if ( ! $this->options->buffered_query && $this->sourceEndpoint instanceof Mysql ) {
            $this->logger->info("Switching to un-buffered query mode");
            $pdo = $this->sourceHandle->handle();
            $originalBufferedQueryAttribute = $pdo->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }

        // Create a file to be used with the "load data infile" statement. Note these values are
        // used so we don't have to escape quotes and such.

        $fieldSep  = chr(30); // 0x1e record separator
        $lineSep   = chr(29); // 0x1d group separator
        $stringEnc = chr(31); // 0x1f unit separator

        // Total number of rows processed for the ingestion
        $totalRowsProcessed = 0;

        // Number of rows in the current file
        $numRowsInFile = 0;

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

        while ( $srcRow = $sourceStatement->fetch(PDO::FETCH_ASSOC) ) {

            // Handle NULL and empty values in the source data set

            foreach ( $srcRow as $key => &$value ) {
                if ( 'order_id' == $key) {
                    $value = $totalRowsProcessed;
                } elseif ( null === $value ) {
                    $value = '\N';
                } elseif ( empty($value) ) {
                    $value = $stringEnc . '' . $stringEnc;
                }
            }

            // Write the requested rows to the infile for each destination, performing any requested
            // mapping.

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

                $destRow = array();

                foreach ($destinationFields as $tableDestField => $sourceQueryField) {
                    $destRow[$tableDestField] = $srcRow[$sourceQueryField];
                }

                fwrite($outFdList[$etlTableKey], implode($fieldSep, $destRow) . $lineSep);

            }  // foreach ($destinationFields as $destField)

            $totalRowsProcessed++;
            $numRowsInFile++;

            // Periodically log the number of rows processed

            if (0 == $totalRowsProcessed % self::NUM_ROWS_PER_LOG_MSG) {
                $msg = sprintf('%s: Processed %d records', get_class($this), $totalRowsProcessed);
                $this->logger->info($msg);
            }

            // If we've reached the maximum number of rows per chunk, load the data.

            if ( $numRowsInFile == self::MAX_ROWS_PER_INFILE ) {
                foreach ( $loadStatementList as $etlTableKey => $loadStatement ) {
                    try {
                        $this->_dest_helper->executeStatement($loadStatement);
                        $this->logger->debug("Loaded $numRowsInFile records into '$etlTableKey'");

                        // Clear the infile

                        ftruncate($outFdList[$etlTableKey], 0);
                        rewind($outFdList[$etlTableKey]);
                    }
                    catch (Exception $e) {
                        $msg = array('message'    => $e->getMessage(),
                                     'stacktrace' => $e->getTraceAsString(),
                                     'statement'  => $loadStatement
                            );
                        $this->logger->err($msg);
                        throw $e;
                    }
                }  // foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable )
                $numRowsInFile = 0;
            }  // if ( $numRowsInFile == self::MAX_ROWS_PER_INFILE )

        }  // while ( $srcRow = $srcStatement->fetch(...) )

        // Process the final chunk.

        if ( $numRowsInFile > 0 ) {
            foreach ( $loadStatementList as $etlTableKey => $loadStatement ) {
                try {
                    $this->_dest_helper->executeStatement($loadStatement);
                    $this->logger->debug("Loaded $numRowsInFile records into '$etlTableKey'");
                }
                catch (Exception $e) {
                    $msg = array('message'    => $e->getMessage(),
                                 'stacktrace' => $e->getTraceAsString(),
                                 'statement'  => $loadStatement
                        );
                    $this->logger->err($msg);
                    throw $e;
                }

                // Cleanup

                fclose($outFdList[$etlTableKey]);
                unlink($infileList[$etlTableKey]);

            }  // foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable )
        }  // if ( $numRowsInFile)

        $msg = sprintf('%s: Processed %d records', get_class($this), $totalRowsProcessed);
        $this->logger->info($msg);

        // Return buffering to its original state.  This is a MySQL specific optimization.

        if ( ! $this->options->buffered_query && $this->sourceEndpoint instanceof Mysql ) {
            $this->logger->info("Returning buffered query mode to: " . ($originalBufferedQueryAttribute ? "true" : "false") );
            $pdo = $this->sourceHandle->handle();
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $originalBufferedQueryAttribute);
        }

        return $totalRowsProcessed;

    }  // multiDatabaseIngest()

    /* ------------------------------------------------------------------------------------------
     * Determine if we can support optimizations for queries within a single database. Not only must
     * our source and destination databases be the same, but we cannot optimize if we are dealing
     * with multiple destination tables, or other factors.  Optimization may be performed as a
     * "INSERT...SELECT" directly in the database rather than a SELECT returning the data and then a
     * separate INSERT.
     *
     * @return true If both the source and destination are the same server.
     * ------------------------------------------------------------------------------------------
     */

    protected function allowSingleDatabaseOptimization()
    {
        if ( ! $this->options->optimize_query ) {
            return false;
        }

        // Same database type?

        if ( ! $this->sourceEndpoint->getType() == $this->destinationEndpoint->getType() ) {
            return false;
        }

        // Endpoints on the same server?

        if ( ! $this->sourceEndpoint->isSameServer($this->destinationEndpoint) ) {
            return false;
        }

        // Can't optimize more than 1 destination table

        if ( count($this->etlDestinationTableList) > 1 ) {
            return false;
        }

        // Can't optimize if mapping a subset of the query fields

        reset($this->destinationFieldMappings);

        if ( count($this->availableSourceQueryFields) != count(current($this->destinationFieldMappings)) ) {
            return false;
        }

        return true;

    }  // allowSingleDatabaseOptimization()
}  // class pdoIngestor
