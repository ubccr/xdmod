<?php
/** =========================================================================================
 * Most ETL processes write their data into an RDMBS. This class encapsulates methods and
 * properties common to all actions that deposit data into an RDBMS destination. These
 * include:
 *
 * 1. Verifying the destination endpoint
 * 2. Parsing the table definition file
 * 3. Verifying post-action queries, destination fields, and truncate queries
 * 4. Optionally truncating the destination table
 * 5. Performing optional post-action queries
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-11-11
 *
 * @see aAction
 * @see iAction
 * ==========================================================================================
 */

namespace ETL;

use ETL\Configuration\EtlConfiguration;
use ETL\DataEndpoint\iDataEndpoint;
use ETL\DataEndpoint\iRdbmsEndpoint;
use ETL\aOptions;

use ETL\DbModel\Table;
use PHPSQLParser\PHPSQLParser;

use Exception;
use PDOException;
use stdClass;
use Log;

abstract class aRdbmsDestinationAction extends aAction
{
    /** -----------------------------------------------------------------------------------------
     * An array of one or more Table objects representing the destination tables supported
     * by this action, The keys are the table names.
     *
     * @var array
     * ------------------------------------------------------------------------------------------
     */

    protected $etlDestinationTableList = array();

    /** -----------------------------------------------------------------------------------------
     * A 2-dimensional associative array where the keys are ETL table names and the values
     * are a mapping between ETL table columns (keys) and source query columns (values).
     *
     * @var array
     * ------------------------------------------------------------------------------------------
     */

    protected $destinationFieldMappings = array();

    /** -----------------------------------------------------------------------------------------
     * Set to TRUE to indicate a destination field mapping was not specified in the
     * configuration file and was auto-generated using all source query columns.  This can
     * be used for optimizations later.
     *
     * @var boolean
     * ------------------------------------------------------------------------------------------
     */

    protected $fullSourceToDestinationMapping = false;

    /** -----------------------------------------------------------------------------------------
     * @see aAction::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        $requiredKeys = array("destination", "definition_file");
        $this->verifyRequiredConfigKeys($requiredKeys, $options);

        parent::__construct($options, $etlConfig, $logger);

    }  // __construct()

    /** -----------------------------------------------------------------------------------------
     * @see iAction::initialize()
     *
     * @throws Exception if any query data was not in the correct format.
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(EtlOverseerOptions $etlOverseerOptions = null)
    {
        if ( $this->isInitialized() ) {
            return;
        }

        $this->initialized = false;

        parent::initialize($etlOverseerOptions);

        if ( ! $this->destinationEndpoint instanceof iRdbmsEndpoint ) {
            $this->destinationEndpoint = null;
            $this->logAndThrowException(
                "Destination endpoint does not implement ETL\\DataEndpoint\\iRdbmsEndpoint"
            );
        }

        if ( ! isset($this->parsedDefinitionFile->table_definition) ) {
            $this->logAndThrowException("Definition file does not contain a 'table_definition' key");
        }

        // Create the objects representing the destination tables. This method can be
        // overriden in the case of an aggregator to use AggregationTable objects instead
        // of Table objects.

        $this->createDestinationTableObjects();

        if ( 0 == count($this->etlDestinationTableList) ) {
            $this->logAndThrowException("No ETL destination tables defined");
        }

        foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {

            if ( ! $etlTable instanceof Table ) {
                $this->logAndThrowException(
                    sprintf("ETL destination table with key '%s' is not an instance of Table", $etlTableKey)
                );
            }

            $etlTable->verify();
        }

        $this->initialized = true;

        return true;

    }  // initialize()

    /** -----------------------------------------------------------------------------------------
     * Populate the $etlDestinationTableList with Table objects representing the tables
     * described in the table definition configuration block from the definition file. If
     * another type of table object is needed (e.g., AggregationTable for aggregation
     * actions) then this method can be overriden.
     *
     * @return int The number of table definitions processed.
     * ------------------------------------------------------------------------------------------
     */

    protected function createDestinationTableObjects()
    {
        // A table definition can be either:
        //
        // (1) A single table definition object (current default for a single destination
        // table) or (2) An array of one or more table definitions. Both are stored
        // internally as an associative array where the key is the name of the table. We
        // could also represent multiple tables using the name as the key but I can't
        // think of a current use case where we would need to do this

        // Normalize the table definition into a set of key-value pairs where the key is the
        // table name and the value is the definition object.

        $parsedTableDefinition = $this->parsedDefinitionFile->table_definition;

        $parsedTableDefinitionList =
            ( ! is_array($parsedTableDefinition)
              ? array($parsedTableDefinition)
              : $parsedTableDefinition );

        foreach ( $parsedTableDefinitionList as $tableDefinition ) {

            try {
                $etlTable = new Table(
                    $tableDefinition,
                    $this->destinationEndpoint->getSystemQuoteChar(),
                    $this->logger
                );
                $this->logger->debug(
                    sprintf("Created ETL destination table object for table definition key '%s'", $etlTable->name)
                );
                $etlTable->schema = $this->destinationEndpoint->getSchema();

                if ( ! is_string($etlTable->name) || empty($etlTable->name) ) {
                    $this->logAndThrowException("Destination table name must be a non-empty string");
                }

                $this->etlDestinationTableList[$etlTable->name] = $etlTable;
            } catch (Exception $e) {
                $this->logAndThrowException(sprintf("%s in file '%s'", $e->getMessage(), $this->definitionFile));
                continue;
            }

        }  // foreach ( $tableDefinitionList as $etlTableKey => $tableDefinition )

        $numTableDefinitions = count($this->etlDestinationTableList);
        if ( 0 == $numTableDefinitions ) {
            $this->logAndThrowException("No table definitions specified");
        }

        return $numTableDefinitions;
    }  // createDestinationTableObjects()

    /** -----------------------------------------------------------------------------------------
     * Parse and verify the mapping between source record fields and destination table fields. If a
     * mapping has not been provided, generate one automatically. The destination field map
     * specifies a mapping from source record fields to destination table fields for one or more
     * destination tables.
     *
     * Use Cases:
     *
     * 1. There are >= 1 destination tables and no destination field map
     *
     * Automatically create the destination field map by mapping source fields to destination table
     * fields **where the fields match, excluding non-matching fields.** Log a warning for any
     * fields that do not match.
     *
     * 2. There are >= 1 destination tables and a destination field map is specified.
     *
     * Verify that the destination fields specified in the mapping are valid fields for the
     * destination table that they reference. Also verify that the source fields are valid (and at
     * least 1 source field exists). It is not required that all source fields are mapped to
     * destination fields but care should be exercised that resonable defaults are specified in the
     * table definitions.
     *
     * @param array An array containing the fields available from the source record
     * @param iDataEndpoint $sourceEndpoint Optional source endpoint used to validate values
     *
     * @return array | null A 2-dimensional array where the keys match etl table definitions and
     *   values map table columns (destination) to query result columns (source), or null if no
     *   destination record map was specified.
     * ------------------------------------------------------------------------------------------
     */

    protected function parseDestinationFieldMap(
        array $sourceFields,
        iDataEndpoint $sourceEndpoint = null
    ) {
        $this->destinationFieldMappings = array();

        if ( ! isset($this->parsedDefinitionFile->destination_record_map) ) {

            $this->logger->debug("No destination_field_map specified");
            $this->destinationFieldMappings = $this->generateDestinationFieldMap($sourceFields);

        } elseif ( ! is_object($this->parsedDefinitionFile->destination_record_map) ) {

            $this->logAndThrowException("destination_record_map must be an object");

        } else {

            foreach ( $this->parsedDefinitionFile->destination_record_map as $etlTableKey => $fieldMap ) {

                if ( ! is_object($fieldMap) ) {
                    $this->logAndThrowException(
                        sprintf("destination_record_map for table key '%s' must be an object", $etlTableKey)
                    );
                } elseif ( 0 == count(array_keys((array) $fieldMap)) ) {
                    $this->logger->warning(
                        sprintf(
                            "%s: destination_record_map for table key '%s' is empty, skipping.",
                            $this,
                            $etlTableKey
                        )
                    );
                    continue;
                }

                // Convert the field map from an object to an associative array where keys
                // are destination table columns and values are source record fields
                $this->destinationFieldMappings[$etlTableKey] = (array) $fieldMap;

            }
        }

        $success = true;
        $success &= $this->verifyDestinationMapKeys();
        $success &= $this->verifyDestinationMapSourceFields($sourceFields, $sourceEndpoint);

        return $success;

    }  // parseDestinationFieldMap()

    /** -----------------------------------------------------------------------------------------
     * Generate a destination field map for each destination table based on the
     * intersection of the source record fields and table fields. Only fields common to
     * both source and destination are mapped with unknown fields logged as warnings.
     *
     * @param array An array containing the fields available from the source record
     *
     * @return array A 2-dimensional array where the keys match ETL table names and values
     *  are a 2-dimensional array mapping destination table fields to source record
     *  fields.
     * ------------------------------------------------------------------------------------------
     */

    protected function generateDestinationFieldMap(array $sourceFields)
    {
        $destinationFieldMap = array();
        $fieldMapDebugOutput = array();
        $numSourceFields = count($sourceFields);

        $this->logger->debug(
            sprintf(
                "Auto-generating destination_field_map using %d source fields: %s",
                $numSourceFields,
                implode(', ', $sourceFields)
            )
        );

        // If there are no source fields then we have no data to map.

        if ( 0 == $numSourceFields ) {
            $this->logger->debug("No source record fields, creating empty destination field map");
            return $destinationFieldMap;
        }

        foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {

            $availableTableFields = $etlTable->getColumnNames();

            $this->logger->debug(
                sprintf("Available fields for table key '%s': %s", $etlTableKey, implode(', ', $availableTableFields))
            );

            // Map common fields and log warnings for fields that are not mapped

            $commonFields = array_intersect($availableTableFields, $sourceFields);
            $unmappedSourceFields = array_diff($sourceFields, $availableTableFields);

            // If there were no common fields between the source record and destination table,
            // don't bother creating a map for this table.

            if ( 0 == count($commonFields) ) {
                $this->logger->warning(
                    sprintf("No source fields match available fields for table key '%s', skipping.", $etlTableKey)
                );
                continue;
            }

            $destinationFieldMap[$etlTableKey] = array_combine($commonFields, $commonFields);

            // Generate a more succinct representation of the field map

            $fieldMapDebugOutput[] = sprintf(
                "Table: %s%s",
                $etlTableKey,
                array_reduce(
                    $commonFields,
                    function ($carry, $item) {
                        $carry .= sprintf("%s  %s -> %s", PHP_EOL, $item, $item);
                        return $carry;
                    },
                    ''
                )
            );

            if ( 0 != count($unmappedSourceFields) ) {
                $this->logger->warning(
                    sprintf(
                        "%s: The following %d source record fields were not mapped for table '%s': (%s)",
                        $this,
                        count($unmappedSourceFields),
                        $etlTableKey,
                        implode(', ', $unmappedSourceFields)
                    )
                );
            }
        }

        $this->logger->debug(
            sprintf("Generated destination_field_map:\n%s", implode(PHP_EOL, $fieldMapDebugOutput))
        );

        return $destinationFieldMap;

    }  // generateDestinationFieldMap()

    /** -----------------------------------------------------------------------------------------
     * Verify that the destination map keys are valid table fields. Remember that the
     * destination record map translates source (query, structured file, etc.) fields to
     * destination table fields. The keys in the map must be valid destination table
     * fields.
     *
     * Note that when a destination map is auto-generated, source fields not found in the
     * destination are not added.
     *
     * @return bool TRUE on success
     *
     * @throws Exception If a key is not a valid table field.
     * ------------------------------------------------------------------------------------------
     */

    protected function verifyDestinationMapKeys()
    {
        // For each table field specified in the destination table field mapping, verify
        // that it is present in one of the destination table definitions.

        $undefinedFields = array();

        foreach ( $this->destinationFieldMappings as $etlTableKey => $destinationTableMap ) {

            if ( ! array_key_exists($etlTableKey, $this->etlDestinationTableList) ) {
                $this->logAndThrowException(
                    sprintf("Unknown table key '%s' referenced in destination_record_map", $etlTableKey)
                );
            }

            $availableTableFields = $this->etlDestinationTableList[$etlTableKey]->getColumnNames();
            // Remember that the keys in the field map are table field names
            $destinationTableFields = array_keys($destinationTableMap);
            $missing = array_diff($destinationTableFields, $availableTableFields);

            if ( 0  != count($missing) ) {
                $undefinedFields[] = sprintf(
                    "Table '%s' has undefined table columns/keys (%s)",
                    $etlTableKey,
                    implode(",", $missing)
                );
            }
        }

        if ( 0 != count($undefinedFields) ) {
            $this->logAndThrowException(
                sprintf(
                    "Undefined keys (destination table fields) in ETL destination_record_map: (%s)",
                    implode(', ', $undefinedFields)
                )
            );
        }

        return true;

    }  // verifyDestinationMapKeys()

    /** -----------------------------------------------------------------------------------------
     * Verify that the destination map values are valid source record fields. Remember that the
     * destination record map translates source (query, structured file, etc.) fields to
     * destination table fields. The values in the map must be valid source record fields.
     *
     * @param array An array containing the fields available from the source record
     * @param iDataEndpoint $sourceEndpoint Optional source endpoint used to validate values
     *
     * @return bool TRUE on success
     *
     * @throws Exception If a value is not a valid source record field.
     * ------------------------------------------------------------------------------------------
     */

    protected function verifyDestinationMapSourceFields(
        array $sourceFields,
        iDataEndpoint $sourceEndpoint = null
    ) {
        $undefinedFields = array();

        foreach ( $this->destinationFieldMappings as $etlTableKey => $destinationTableMap ) {
            if ( ! array_key_exists($etlTableKey, $this->etlDestinationTableList) ) {
                $this->logAndThrowException(
                    sprintf("Unknown table '%s' referenced in destination_record_map", $etlTableKey)
                );

            }

            // By default, we verify that the source fields specified in the destination map are
            // present in the fields provided by the source records (this is not automated for RDBMS
            // endpoints yet). Endpoints that support complex data records perform their own
            // validation of the source fields in the destination field map.

            if ( null !== $sourceEndpoint && $sourceEndpoint->supportsComplexDataRecords() ) {
                $missing = $sourceEndpoint->validateDestinationMapSourceFields($destinationTableMap);
            } else {
                $missing = array_diff($destinationTableMap, $sourceFields);
            }

            // Allow the destination map values (source fields) to contain variables.

            $missing = array_filter(
                $missing,
                function ($item) {
                    return ! Utilities::containsVariable($item);
                }
            );

            if ( 0  != count($missing) ) {
                $missing = array_map(
                    function ($k, $v) {
                        return "$k = $v";
                    },
                    array_keys($missing),
                    $missing
                );
                $undefinedFields[] = sprintf(
                    "Table '%s' has undefined source query fields for keys (%s)",
                    $etlTableKey,
                    implode(', ', $missing)
                );
            }

        }  // foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable )

        if ( 0 != count($undefinedFields) ) {
            $this->logAndThrowException(
                sprintf(
                    "Undefined values (source record fields) in ETL destination_record_map: (%s)",
                    implode(', ', $undefinedFields)
                )
            );
        }

        return true;

    }  // verifyDestinationMapSourceFields()

    /** -----------------------------------------------------------------------------------------
     * Truncate records from the destination table. Note that
     * performTruncateDestinationTasks() will be called to do the actual work.
     *
     * @return bool TRUE on success
     * ------------------------------------------------------------------------------------------
     */

    protected function truncateDestination()
    {
        if ( ! $this->options->truncate_destination ) {
            return;
        }

        // Truncate the old table, if requested. If queries are provided use them,
        // otherwise truncate the table.

        return $this->performTruncateDestinationTasks();

    }  // truncateDestination()

    /** -----------------------------------------------------------------------------------------
     * The default task for truncating the destination table is executing a single
     * TRUNCATE statement on the table. If other actions are required, this method should
     * be extended. Note that DELETE triggers will not fire when the table is truncated.
     *
     * NOTE: This method must check if we are in DRYRUN mode before executing any tasks.
     * ------------------------------------------------------------------------------------------
     */

    protected function performTruncateDestinationTasks()
    {

        foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {

            $tableName = $etlTable->getFullName();
            $this->logger->info("Truncate destination table: $tableName");

            try {

                if ( false === $this->destinationEndpoint->tableExists($etlTable->name, $etlTable->schema) ) {
                    $this->logger->info("Table does not exist: '$tableName', skipping.");
                    continue;
                }

            } catch (PDOException $e) {
                $this->logAndThrowException(
                    "Error verifying table $tableName",
                    array('exception' => $e, 'sql' => $sql, 'endpoint' => $this->destinationEndpoint)
                );
            }

            $sql = "TRUNCATE TABLE $tableName";

            try {
                $this->logger->debug("Truncate destination task " . $this->destinationEndpoint . ":\n$sql");
                if ( ! $this->getEtlOverseerOptions()->isDryrun() ) {
                    $this->destinationHandle->execute($sql);
                }
            } catch (PDOException $e) {
                $this->logAndThrowException(
                    "Error truncating destination with key '$etlTableKey'",
                    array('exception' => $e, 'sql' => $sql, 'endpoint' => $this->destinationEndpoint)
                );
            }
        }  // foreach ( $this->etlDestinationTableList as $etlTable )

    }  // performTruncateDestinationTasks()

    /** -----------------------------------------------------------------------------------------
     * Manage destination tables and disable foreign keys if needed.
     *
     * @see aAction::performPreExecuteTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performPreExecuteTasks()
    {
        $sqlList = array();
        $disableForeignKeys = false;

        try {

            // Bring the destination table in line with the configuration if necessary.  Note that
            // manageTable() is DRYRUN aware so we don't need to handle that here.

            foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {
                $qualifiedDestTableName = $etlTable->getFullName();

                if ( "myisam" == strtolower($etlTable->engine) ) {
                    $disableForeignKeys = true;
                    if ( $this->options->disable_keys ) {
                        $this->logger->info("Disable keys on $qualifiedDestTableName");
                        $sqlList[] = "ALTER TABLE $qualifiedDestTableName DISABLE KEYS";
                    }
                }

                $this->manageTable($etlTable, $this->destinationEndpoint);

            }  // foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable )

        } catch ( Exception $e ) {
            $this->logAndThrowException(
                sprintf("Error managing ETL table for '%s': %s", $this->getName(), $e->getMessage())
            );
        }

        if ( $disableForeignKeys ) {
            // See http://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_foreign_key_checks
            $sqlList[] = "SET FOREIGN_KEY_CHECKS = 0";
        }

        $this->executeSqlList($sqlList, $this->destinationEndpoint, "Pre-execute tasks");

        return true;

    }  // performPreExecuteTasks()

    /** -----------------------------------------------------------------------------------------
     * Perform post-execution tasks such as re-enabling foreign key constraints and
     * analyzing or optimizing the table.
     *
     * @see aAction::performPostExecuteTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performPostExecuteTasks($numRecordsProcessed = null)
    {
        $sqlList = array();
        $enableForeignKeys = false;

        foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {
            $qualifiedDestTableName = $etlTable->getFullName();

            if ( "myisam" == strtolower($etlTable->engine) ) {
                $enableForeignKeys = true;
                if ( $this->options->disable_keys ) {
                    $this->logger->info("Enable keys on $qualifiedDestTableName");
                    $sqlList[] = "ALTER TABLE $qualifiedDestTableName ENABLE KEYS";
                }
            }

            if ( null !== $numRecordsProcessed && $numRecordsProcessed > 0 ) {
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

    /** -----------------------------------------------------------------------------------------
     * Execute a list of SQL statements on the specified database handle, throwing an exception if
     * there was an error.
     *
     * @param array $sqlList The list of SQL statements to execute
     * @param iDataEndpoint $endpoint An endpoint implementing iDataEndpoint
     * @param string $msgPrefix Log message with prefix
     *
     * @return bool TRUE on success
     *
     * @throws Exception If there was an error executing a statement
     * ------------------------------------------------------------------------------------------
     */

    protected function executeSqlList(array $sqlList, iDataEndpoint $endpoint, $msgPrefix = "")
    {
        if ( 0 == count($sqlList) ) {
            return true;
        }

        $this->logger->info("Execute" . ( "" != $msgPrefix ? " $msgPrefix" : "" ) .": " . $endpoint);
        foreach ($sqlList as $sql) {
            try {
                $this->logger->debug($sql);
                if ( ! $this->getEtlOverseerOptions()->isDryrun() ) {
                    $endpoint->getHandle()->execute($sql);
                }
            }
            catch (PDOException $e) {
                $this->logAndThrowException(
                    sprintf("Error executing %s SQL", ( "" != $msgPrefix ? "$msgPrefix " : "" )),
                    array('exception' => $e, 'sql' => $sql, 'endpoint' => $endpoint)
                );
            }
        }  // foreach ($sqlList as $sql)

        return true;

    }  // executeSqlList()

    /** -----------------------------------------------------------------------------------------
     * Parse an SQL statement to retrieve column names, tables used, etc. This uses the
     * Google SQL parser.
     *
     * @see https://code.google.com/p/php-sql-parser/
     *
     * @param string $sql The SQL statement to parse
     *
     * @return array An associative array containing the parsed SQL
     *
     * @throws Exception If $sql was empty
     * ------------------------------------------------------------------------------------------
     */

    public function parseSql($sql)
    {
        if ( null === $sql || "" == $sql ) {
            $this->logAndThrowException("Empty SQL statement");
        }

        $parser = new PHPSQLParser($sql);
        return $parser->parsed;

    } // parseSql()

    /** -----------------------------------------------------------------------------------------
     * Parse an SQL SELECT statement and return the fields (columns) that are being queried. It is
     * expected that the order of the column names is the same as they appear in the query.
     * @see https://code.google.com/p/php-sql-parser/
     *
     * @param string $sql The SQL statement to parse
     *
     * @return array A lit of the parsed fieldnames
     *
     * @throws Exception If $sql was empty
     * @throws Exception If there was no SELECT clause detected
     * ------------------------------------------------------------------------------------------
     */

    public function getSqlColumnNames($sql)
    {
        $parsedSql = $this->parseSql($sql);

        if ( ! array_key_exists("SELECT", $parsedSql) ) {
            $this->logAndThrowException("Select block not found in parsed SQL");
        }

        $columnNames = array();

        foreach ( $parsedSql['SELECT'] as $item ) {
            if ( array_key_exists('alias', $item)
                 && $item['alias']['as']
                 && array_key_exists('name', $item['alias'])
            ) {
                $columnNames[] = $item['alias']['name'];
            } else {
                $pos = strrpos($item['base_expr'], ".");
                $columnNames[] = ( false === $pos ? $item['base_expr'] : substr($item['base_expr'], $pos + 1) );
            }
        }  // foreach ( $parsedSql['SELECT'] as $item )

        return $columnNames;

    } // getSqlColumnNames()

    /** -----------------------------------------------------------------------------------------
     * Compare the fields from the table object to those parsed from the SQL SELECT
     * statement and verify that all of the parsed SQL fields are present in the table
     * object. If the table object contains all columns parsed from SELECT clause of the
     * SQL statement return the list of parsed column names, otherwise throw an exception.
     *
     * @param string $sql The SQL statement to parse.
     * @param Table $table An object containing the table definition
     *
     * @return array A list of all field names found in $sql
     *
     * @throws Exception If any of the fields from $sql were not found in the table object
     * ------------------------------------------------------------------------------------------
     */

    public function verifySqlColumns($sql, Table $table)
    {
        $sqlColumnNames = $this->getSqlColumnNames($sql);
        $tableColumnNames = $table->getColumnNames();
        $missingColumnNames = array_diff($sqlColumnNames, $tableColumnNames);

        if ( 0 != count($missingColumnNames) ) {
            $this->logAndThrowException(
                sprintf(
                    "The following columns from the SQL SELECT were not found in table definition for '%s': %s",
                    $table->name,
                    implode(', ', $missingColumnNames)
                )
            );
        }

        return $sqlColumnNames;

    } // verifySqlColumns()

    /** -----------------------------------------------------------------------------------------
     * Manage an ETL table in a data endpoint to bring it in line with the structure
     * specified in the table object. This includes creating a new table or alter an
     * existing table.  If we are in dryrun mode, do not perform any actions, only
     * logging.
     *
     * @param Table $table An object describing the desired table structure
     * @param iDataEndpoint $endpoint The destination data endpoint where the table will
     *   be created/altered
     *
     * @return Table The table object to support method chaining
     * ------------------------------------------------------------------------------------------
     */

    public function manageTable(Table $table, iDataEndpoint $endpoint)
    {
        // Check for an existing table with the same name

        $existingTable = new Table(null, $endpoint->getSystemQuoteChar(), $this->logger);

        // If no table with that name exists, create it. Otherwise check for differences and apply them.

        if ( false === $existingTable->discover($table->name, $endpoint) ) {

            $this->logger->notice(sprintf("Table %s does not exist, creating.", $table->getFullName()));

            $sqlList = $table->getSql();

            foreach ( $sqlList as $sql ) {
                $this->logger->debug(sprintf("Create table SQL %s:\n%s", $endpoint, $sql));
                if ( ! $this->getEtlOverseerOptions()->isDryrun() ) {
                    $endpoint->getHandle()->execute($sql);
                }
            }

        } else {
            // A return value of FALSE indicates no changes to be made
            $sqlList = $existingTable->getAlterSql($table);

            if ( false !== $sqlList ) {
                $this->logger->notice(sprintf("Altering table %s", $existingTable->getFullName()));

                foreach ( $sqlList as $sql ) {
                    $this->logger->debug(sprintf("Alter table SQL %s:\n%s", $endpoint, $sql));
                    if ( ! $this->getEtlOverseerOptions()->isDryrun() ) {
                        $endpoint->getHandle()->execute($sql);
                    }
                }
            }  // if ( false !== $sqlList )

        }  // else ( false === $existingTable )

        return $table;

    }  // manageTable()
}  // abstract class aRdbmsDestinationAction
