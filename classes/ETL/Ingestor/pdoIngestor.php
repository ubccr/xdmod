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
use ETL\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\Utilities;
use ETL\DbEntity\Query;

use CCR\DB\MySQLHelper;
use PDOException;
use PDO;
use Log;

class pdoIngestor extends aIngestor
{
    // Maximum number of times to attempt to execute the source query
    const MAX_QUERY_ATTEMPTS = 3;

    // Write a log message after processing this many source records
    const NUM_RECORDS_PER_LOG_MSG = 100000;

    // Maximum number of records to import at once
    const MAX_RECORDS_PER_INFILE = 250000;

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

    // Note these values are used so we don't have to escape quotes and such.

    // Line separator for MySQL LOAD DATA INFILE LINES TERMINATED BY.
    protected $lineSeparator = '\n';

    // Field separator for MySQL LOAD DATA INFILE FIELDS TERMINATED BY.
    protected $fieldSeparator = '\t';

    // String enclosure for MySQL LOAD DATA INFILE ENCLOSED BY.
    protected $stringEnclosure = '';

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

        // This action supports chunking of the ETL date period

        $this->supportDateRangeChunking = true;

        // Use ASCII group, record, and unit separators

        $this->lineSeparator = chr(0x1d);   // Group separator
        $this->fieldSeparator = chr(0x1e);  // Record separator
        $this->stringEnclosure = chr(0x1f); // Unit separator

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
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
            $msg = "Utility endpoint does not implement of ETL\\DataEndpoint\\iRdbmsEndpoint";
            $this->logAndThrowException($msg);
        }

        if ( ! $this->sourceEndpoint instanceof iRdbmsEndpoint ) {
            $msg = "Source endpoint is not an instance of ETL\\DataEndpoint\\iRdbmsEndpoint";
            $this->logAndThrowException($msg);
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

            $this->getEtlOverseerOptions()->applyOverseerRestrictions($this->etlSourceQuery, $this->sourceEndpoint, $this);

        }  // ( null === $this->etlSourceQuery )

        // By default, queries may include references to the schema names so they can be changed in the
        // config file.

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
            $sql = Utilities::substituteVariables(
                $sql,
                $this->variableMap,
                $this,
                "Undefined macros found in source query"
            );
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

        // We can query the number of source records if we are using a buffered query.

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

        $this->executeSqlList($sqlList, $this->destinationEndpoint, "Pre-execute tasks");

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

        $this->executeSqlList($sqlList, $this->destinationEndpoint, "Post-execute tasks");

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
            $this->getEtlOverseerOptions()->applyOverseerRestrictions($this->etlSourceQuery, $this->sourceEndpoint, $this);
            $this->sourceQueryString = $this->getSourceQueryString();
        }

        // ------------------------------------------------------------------------------------------
        // Main ingest

        $totalRecordsProcessed = 0;

        // If we can execute the ingestion query directly on the server instead of querying,
        // returning the data locally, chunking it into a file, and loading it on the remote server
        // we may gaina speed enhancement. For ~860,000 records this executes in roughly 65% of the
        // time.

        $optimize = $this->allowSingleDatabaseOptimization();

        if ( $optimize ) {
            $this->logger->info("Allowing same-server SQL optimizations");
            $totalRecordsProcessed = $this->singleDatabaseIngest();
        } else {
            $this->logger->info("Using multi-database ingest");
            $totalRecordsProcessed = $this->multiDatabaseIngest();
        }

        return $totalRecordsProcessed;

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
     * @return The number of records processed
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

        $this->logger->debug("Single DB ingest SQL " . $this->destinationEndpoint . ":\n$sql");

        if ( $this->getEtlOverseerOptions()->isDryrun() ) {
            return 0;
        }

        $totalRecordsProcessed = $this->destinationHandle->execute($sql);
        $this->logger->info(
            sprintf('%s: Processed %d records', get_class($this), number_format($totalRecordsProcessed))
        );

        return $totalRecordsProcessed;

    }  // singleDatabaseIngest()

    /* ------------------------------------------------------------------------------------------
     * If the source and destination endpoints are not on the same database server (or other
     * criteria are met such as needing to update rather than replace a row) we will need to select
     * the data, brining it back to this host, chunk it into a file, and run LOAD DATA INFILE to
     * load it into the database.
     *
     * @return The number of records processed
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

            $this->logger->debug("LOAD statement for destination table key '$etlTableKey' " . $this->destinationEndpoint . ":\n$loadStatement");

            $infileList[$etlTableKey] = $infileName;
            $loadStatementList[$etlTableKey] = $loadStatement;

        }  // foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable )

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

        while ( $srcRecord = $sourceStatement->fetch(PDO::FETCH_ASSOC) ) {

            $numSourceRecordsProcessed++;

            // Note that an array of transformed records is returned because a single source
            // record may be transformed into multiple records.

            $transformedRecords = $this->transform($srcRecord);

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
                        sprintf('%s: Processed %d records', get_class($this), number_format($totalRecordsProcessed))
                    );
                }

                // If we've reached the maximum number of records per chunk, load the data.

                if ( $numRecordsInFile == self::MAX_RECORDS_PER_INFILE ) {
                    foreach ( $loadStatementList as $etlTableKey => $loadStatement ) {
                        try {
                            $this->_dest_helper->executeStatement($loadStatement);
                            $this->logger->debug("Loaded $numRecordsInFile records into '$etlTableKey'");

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
                    $numRecordsInFile = 0;
                }  // if ( $numRecordsInFile == self::MAX_RECORDS_PER_INFILE )

            } // foreach ( $record )

        }  // while ( $srcRecord = $srcStatement->fetch(...) )

        // Process the final chunk.

        if ( $numRecordsInFile > 0 ) {
            foreach ( $loadStatementList as $etlTableKey => $loadStatement ) {
                try {
                    $this->_dest_helper->executeStatement($loadStatement);
                    $this->logger->debug("Loaded $numRecordsInFile records into '$etlTableKey'");
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
                @unlink($infileList[$etlTableKey]);

            }  // foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable )
        }  // if ( $numRecordsInFile)

        $msg = sprintf('%s: Processed %d records (%d source records)', get_class($this), $totalRecordsProcessed, $numSourceRecordsProcessed);
        $this->logger->info($msg);

        // Return buffering to its original state.  This is a MySQL specific optimization.

        if ( ! $this->options->buffered_query && $this->sourceEndpoint instanceof Mysql ) {
            $this->logger->info("Returning buffered query mode to: " . ($originalBufferedQueryAttribute ? "true" : "false") );
            $pdo = $this->sourceHandle->handle();
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $originalBufferedQueryAttribute);
        }

        return $totalRecordsProcessed;

    }  // multiDatabaseIngest()

    /* ------------------------------------------------------------------------------------------
     * Perform a transformation on a single data record (row). Transformation may alter
     * the values of the record and may create multiple records from a single record, but
     * it should not modify the struture of the record itself (e.g., the keys). Because we
     * support the ability to transform a single source record into multiple result
     * records, an array of records is returned, even for a single result record.
     *
     * @param $record An associative array containing the source record
     *
     * @return An array of transformed records.
     * ------------------------------------------------------------------------------------------
     */

    protected function transform(array $srcRecord)
    {
        foreach ( $srcRecord as $key => &$value ) {
            if ( null === $value ) {
                // Transform NULL values for MySQL LOAD FILE
                $value = '\N';
            } elseif ( empty($value) ) {
                $value = $this->stringEnclosure . '' . $this->stringEnclosure;
            } else {
                // Handle proper escaping of backslashes to preserve source data containing them.
                $value = str_replace('\\', '\\\\', $value);
            }
        }

        return array($srcRecord);

    }  // transform()

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
            // No nothing, transform() has not been overriden.
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

        // Can't optimize more than 1 destination table

        if ( count($this->etlDestinationTableList) > 1 ) {
            $this->logger->debug("Multiple destination tables being populated");
            return false;
        }

        // Can't optimize if mapping a subset of the query fields

        reset($this->destinationFieldMappings);

        if ( count($this->availableSourceQueryFields) != count(current($this->destinationFieldMappings)) ) {
            $this->logger->debug("Mapping a subset of the source query fields");
            return false;
        }

        return true;

    }  // allowSingleDatabaseOptimization()
}  // class pdoIngestor
