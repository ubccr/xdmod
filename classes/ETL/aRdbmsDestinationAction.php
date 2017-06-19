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
     * Initialize data required to perform the action.  Since this is an action of a
     * target database we must parse the definition of the target table.
     *
     * @param EtlOverseerOptions $etloverseeroptions The options provided to the overseer.
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
        if ( ! isset($this->parsedDefinitionFile->table_definition) ) {
            $this->logAndThrowException("Definition file does not contain a 'table_definition' key");
        }

        // A table definition can be either:
        //
        // (1) A single table definition object (current default for a single destination
        // table) or (2) An array of one or more table definitions (how we will initially
        // handle multiple destination tables).
        //
        // In the future, we will support an object with key value pairs where the key is
        // the table name and the value is the definition object. In the mean time, we
        // will generate this format here so the rest of the code does not need to change
        // later.

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
                $tableName = $etlTable->getFullName();

                if ( ! is_string($tableName) || empty($tableName) ) {
                    $this->logAndThrowException("Destination table name must be a non-empty string");
                }

                $this->etlDestinationTableList[$etlTable->name] = $etlTable;
            } catch (Exception $e) {
                $this->logAndThrowException(sprintf("%s in file '%s'", $e->getMessage(), $this->definitionFile));
            }

        }  // foreach ( $tableDefinitionList as $etlTableKey => $tableDefinition )

        $numTableDefinitions = count($this->etlDestinationTableList);
        if ( 0 == $numTableDefinitions ) {
            $this->logAndThrowException("No table definitions specified");
        }

        return $numTableDefinitions;
    }  // createDestinationTableObjects()

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
     * Parse an SQL SELECT statement and return the fields (columns) that are being queried.
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
                    implode(", ", $missingColumnNames)
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
