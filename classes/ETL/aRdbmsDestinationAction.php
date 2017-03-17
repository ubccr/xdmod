<?php
/* ==========================================================================================
 * Most ETL processes culminate in the addition of data to an RDMBS. This class encapsulates methods
 * and properties common to all actions on a database destination. These include:
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

use ETL\EtlConfiguration;
use ETL\DataEndpoint\iDataEndpoint;
use ETL\DataEndpoint\iRdbmsEndpoint;
use ETL\aOptions;
use ETL\DbEntity\Table;

use PHPSQLParser\PHPSQLParser;

use Exception;
use PDOException;
use stdClass;
use Log;

abstract class aRdbmsDestinationAction extends aAction
{
    // Path to the JSON configuration file containing ETL table and source query configurations, among
    // other things.

    // The stdClass representing a parsed definition file

    // A list of one or more Table objects representing the ETL destination tables supported by this
    // action where the keys are the table names
    protected $etlDestinationTableList = array();

    /* ------------------------------------------------------------------------------------------
     * @see aAction::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        $requiredKeys = array("destination", "definition_file");
        $this->verifyRequiredConfigKeys($requiredKeys, $options);

        parent::__construct($options, $etlConfig, $logger);

        $this->destinationEndpoint = $etlConfig->getDataEndpoint($this->options->destination);
        if ( ! $this->destinationEndpoint instanceof iRdbmsEndpoint ) {
            $this->destinationEndpoint = null;
            $msg = "Destination endpoint does not implement ETL\\DataEndpoint\\iRdbmsEndpoint";
            $this->logAndThrowException($msg);
        }
        $this->destinationHandle = $this->destinationEndpoint->getHandle();
        $this->logger->debug("Destination endpoint: " . $this->destinationEndpoint);

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::verify()
     * ------------------------------------------------------------------------------------------
     */

    public function verify(EtlOverseerOptions $etlOptions = null)
    {
        if ( $this->isVerified() ) {
            return;
        }

        $this->verified = false;
        if ( null !== $etlOptions ) {
            $this->etlOverseerOptions = $etlOptions;
        }

        // Perform verification by fetching the query data and parsing the table
        // configuration. Exceptions will be thrown if there were errors.

        $this->initialize();

        if ( 0 == count($this->etlDestinationTableList) ) {
            $msg = "No ETL destination tables defined";
            $this->logAndThrowException($msg);
        }

        foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {

            if ( ! $etlTable instanceof Table ) {
                $msg = "ETL destination table with key '$etlTableKey' is not an instance of Table";
                $this->logAndThrowException($msg);
            }

            $etlTable->verify();
        }

        $this->verified = true;

        return true;

    }  // verify()

    /* ------------------------------------------------------------------------------------------
     * Initialize data required to perform the action.  Since this is an action of a target database
     * we must parse the definition of the target table.
     *
     * @throws Exception if any query data was not
     * int the correct format.
     * ------------------------------------------------------------------------------------------
     */

    protected function initialize()
    {
        if ( $this->isInitialized() ) {
            return;
        }

        $this->initialized = false;

        // If the destaination table list is not empty, assume that a child class has set it and do
        // not override.  Otherwise populate it based on the parsed definition.

        if ( 0 == count($this->etlDestinationTableList) ) {

            if ( ! isset($this->parsedDefinitionFile->table_definition) ) {
                $msg = "Definition file does not contain a 'table_definition' key";
                $this->logAndThrowException($msg);
            }

            // A table definition can be either (1) A single table definition object (current
            // default for a single destination table) or (2) An array of one or more table
            // definitions (how we will initially handle multiple destination tables). In the
            // future, we will support an object with key value pairs where the key is the table
            // name and the value is the definition object. In the mean time, we will generate this
            // format here so the rest of the code does not need to change later.

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
                        "Created ETL destination table object for table definition key '"
                        . $etlTable->getName()
                        . "'"
                    );
                    $etlTable->setSchema($this->destinationEndpoint->getSchema());
                    $tableName = $etlTable->getFullName();

                    if ( ! is_string($tableName) || empty($tableName) )
                    {
                        $msg = "Destination table name must be a non-empty string";
                        $this->logAndThrowException($msg);
                    }

                    $this->etlDestinationTableList[$etlTable->getName()] = $etlTable;
                } catch (Exception $e) {
                    $this->logAndThrowException($e->getMessage() . " in file '" . $this->definitionFile . "'");
                }

            }  // foreach ( $tableDefinitionList as $etlTableKey => $tableDefinition )

            if ( 0 == count($this->etlDestinationTableList) ) {
                $msg = "No table definitions specified";
                $this->logAndThrowException($msg);
            }

        }  // if ( 0 == count($this->etlDestinationTableList) )

        // Set substitution variables provided by this class

        $this->variableMap["DESTINATION_SCHEMA"] = $this->destinationEndpoint->getSchema();

        $this->initialized = true;

        return true;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * Truncate the destination table. Note that performTruncateDestinationTasks() will be called to
     * do the actual work.
     *
     * @return TRUE on success
     * @throws Exception If any operations failed
     * ------------------------------------------------------------------------------------------
     */

    protected function truncateDestination()
    {
        if ( ! $this->options->truncate_destination ) {
            return;
        }

        // Truncate the old table, if requested. If queries are provided use them, otherwise truncate
        // the table.

        return $this->performTruncateDestinationTasks();

    }  // truncateDestination()

    /* ------------------------------------------------------------------------------------------
     * The default task for truncating the destination table is executing a single TRUNCATE statement
     * on the table. If other actions are required, this method should be extended. Note that DELETE
     * triggers will not fire when the table is truncated.
     *
     * NOTE: This method must check if we are in DRYRUN mode before executing any tasks.
     *
     * @see iIngestor::truncateDestinationTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performTruncateDestinationTasks()
    {

        foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {

            $tableName = $etlTable->getFullName();
            $this->logger->info("Truncate destination table: $tableName");

            try {

                if ( false === $this->destinationEndpoint->tableExists($etlTable->getName(), $etlTable->getSchema()) ) {
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
                if ( ! $this->etlOverseerOptions->isDryrun() ) {
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

    /* ------------------------------------------------------------------------------------------
     * Execute a list of SQL statements on the specified database handle, throwing an exception if
     * there was an error.
     *
     * @param $sqlList An array of SQL statements to execute
     * @param $endpoint An endpoint implementing iDataEndpoint
     * @param $msgPrefix String to prefix log messages with
     *
     * @return TRUE on success
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
                if ( ! $this->etlOverseerOptions->isDryrun() ) {
                    $endpoint->getHandle()->execute($sql);
                }
            }
            catch (PDOException $e) {
                $this->logAndThrowException(
                    "Error executing " . ( "" != $msgPrefix ? "$msgPrefix " : "" ) . "SQL",
                    array('exception' => $e, 'sql' => $sql, 'endpoint' => $endpoint)
                );
            }
        }  // foreach ($sqlList as $sql)

        return true;

    }  // executeSqlList()

    /* ------------------------------------------------------------------------------------------
     * Parse an SQL statement to retrieve column names, tables used, etc.
     * @ See https://code.google.com/p/php-sql-parser/
     *
     * @param $sql The SQL statement to parse
     *
     * @return An associative array containing the parsed SQL
     *
     * @throws Exception If the SQL was empty
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

    /* ------------------------------------------------------------------------------------------
     * Parse an SQL SELECT statement and return the selected colum names.
     * @ See https://code.google.com/p/php-sql-parser/
     *
     * @param $sql The SQL statement to parse
     *
     * @return An array containing the parsed column names
     *
     * @throws Exception If the SQL was empty
     * @throws Exception If there was no SELECT clause detected
     * ------------------------------------------------------------------------------------------
     */

    public function getSqlColumnNames($sql)
    {
        $parsedSql = $this->parseSql($sql);

        if ( ! array_key_exists("SELECT", $parsedSql) ) {
            $msg = "Select block not found in parsed SQL";
            $this->logAndThrowException($msg);
        }

        $columnNames = array();

        foreach ( $parsedSql['SELECT'] as $item ) {
            if ( array_key_exists('alias', $item)
                 && $item['alias']['as']
                 && array_key_exists('name', $item['alias']) ) {
                $columnNames[] = $item['alias']['name'];
            } else {
                $pos = strrpos($item['base_expr'], ".");
                $columnNames[] = ( false === $pos ? $item['base_expr'] : substr($item['base_expr'], $pos + 1) );
            }
        }  // foreach ( $parsedSql['SELECT'] as $item )

        return $columnNames;

    } // getSqlColumnNames()

    /* ------------------------------------------------------------------------------------------
     * Compare the columns from the table object to those parsed from the SQL SELECT clause and verify
     * that all of the parsed SQL columns are present in the table object. If the table object
     * contains all columns parsed from SELECT clause of the SQL statement return the list of parsed
     * column names, otherwise throw an exception.
     *
     * @param $sql The SQL statement to parse.
     * @param $table A Table object containing a table definition
     *
     * @return An array containing all columns in the SELECT clause of the $sql parameter.
     *
     * @throws Exception If any of the columns from the SQL SELECT clause were not found in the Table
     *   object
     * ------------------------------------------------------------------------------------------
     */

    public function verifySqlColumns($sql, Table $table)
    {
        $sqlColumnNames = $this->getSqlColumnNames($sql);
        $tableColumnNames = $table->getColumnNames();
        $missingColumnNames = array_diff($sqlColumnNames, $tableColumnNames);

        if ( 0 != count($missingColumnNames) ) {
            $msg = "The following columns from the SQL SELECT were not found in table definition for '{$table->getName()}': " .
                implode(", ", $missingColumnNames);
            $this->logAndThrowException($msg);
        }

        return $sqlColumnNames;

    } // verifySqlColumns()

    /* ------------------------------------------------------------------------------------------
     * Manage an ETL tables. Based on the table object, create a new table or alter an existing table
     * to bring it in line with the configuration in the table object. If we are in dryrun mode, do
     * not perform any actions, only logging.
     *
     * @param $table A Table object
     * @param $endpoint The destination data endpoint where the table will be created
     *
     * @return The Table object generated from the table configuration file
     *
     * @throws Exception If any query data was not int the correct format.
     * @throws Exception If the ETLOverseerOptions have not been set.
     * ------------------------------------------------------------------------------------------
     */

    public function manageTable(Table $table, iDataEndpoint $endpoint)
    {
        if ( null === $this->etlOverseerOptions ) {
            $msg = "ETL overseer options are not set. These are typically set in iAction::execute() or iAction::verify()";
            $this->logAndThrowException($msg);
        }

        // Check for an existing table with the same name

        $existingTable = Table::discover(
            $table->getName(),
            $endpoint,
            $endpoint->getSystemQuoteChar(),
            $this->logger
        );

        // If no table with that name exists, create it. Otherwise check for differences and apply them.

        if ( false === $existingTable ) {

            $this->logger->notice("Table " . $table->getFullName() . " does not exist, creating.");

            $sqlList = $table->getCreateSql();

            foreach ( $sqlList as $sql ) {
                $this->logger->debug("Create table SQL " . $endpoint . ":\n$sql");
                if ( ! $this->etlOverseerOptions->isDryrun() ) {
                    $endpoint->getHandle()->execute($sql);
                }
            }

        } else {
            // A return value of FALSE indicates no changes to be made
            $sqlList = $existingTable->getAlterSql($table);

            if ( false !== $sqlList ) {
                $this->logger->notice("Altering table " . $existingTable->getFullName());

                foreach ( $sqlList as $sql ) {
                    $this->logger->debug("Alter table SQL " . $endpoint . ":\n$sql");
                    if ( ! $this->etlOverseerOptions->isDryrun() ) {
                        $endpoint->getHandle()->execute($sql);
                    }
                }
            }  // if ( false !== $sqlList )

        }  // else ( false === $existingTable )

        return $table;

    }  // manageTable()
}  // abstract class aRdbmsDestinationAction
