<?php
/* ==========================================================================================
 * Class for managing tables in the data warehouse.  Table and column/index/trigger names
 * are fully escaped to support special characters. Functionality is provided for:
 *
 * 1. Creating table structure by parsing a JSON file
 * 2. Discovering table structure from an existing MySQL database
 * 3. Comparing an existing (parsed) table definition with a discovered table and determining the
 *    proper actions needed to bring the existing table into line with the definition
 * 4. Generating a MySQL CREATE TABLE and associated CREATE TRIGGER statements
 * 5. Generating an ALTER TABLE and DROP/CREATE TIGGER statements
 * 6. Generating a JSON representation of the table.
 *
 * The Table class makes use of the following classes:
 * - Column
 * - Index
 * - Constraint
 * - Trigger
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-28
 * ==========================================================================================
 */

namespace ETL\DbModel;

use ETL\DataEndpoint\iRdbmsEndpoint;
use Log;
use stdClass;
use Exception;

class Table extends SchemaEntity implements iEntity, iDiscoverableEntity, iAlterableEntity
{
    // Properties required by this class. These will be merged with other required
    // properties up the call chain. See @Entity::$requiredProperties
    private $localRequiredProperties = array(
        'columns'
    );

    // Properties provided by this class. These will be merged with other properties up
    // the call chain. See @Entity::$properties
    private $localProperties = array(
        // Optional table comment
        'comment'  => null,

        // Optional table engine
        'engine'   => null,

        // Associative array where the keys are column names and the values are Column objects
        'columns'  => array(),

        // Associative array where the keys are index names and the values are Index objects
        'indexes'  => array(),

        // Associative array where the keys are constraint names and the values are Constraint objects
        'constraints'  => array(),

        // Associative array where the keys are trigger names and the values are Trigger objects
        'triggers' => array(),
    );

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($config, $systemQuoteChar = null, Log $logger = null)
    {
        // Property merging is performed first so the values can be used in the constructor
        parent::mergeProperties($this->localRequiredProperties, $this->localProperties);
        parent::__construct($config, $systemQuoteChar, $logger);
    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see aNamedEntity::initialize()
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(stdClass $config)
    {
        // The table object only cares about the table definition but there may be other configuration keys present. Only continue on with the table definition.

        if ( isset($config->table_definition) ) {
            parent::initialize($config->table_definition);
        } else {
            parent::initialize($config);
        }

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * @see Entity::filterAndVerifyValue()
     * ------------------------------------------------------------------------------------------
     */

    protected function filterAndVerifyValue($property, $value)
    {
        $value = parent::filterAndVerifyValue($property, $value);

        if ( null === $value ) {
            return $value;
        }

        switch ( $property ) {
            case 'columns':
            case 'indexes':
            case 'constraints':
            case 'triggers':
                // Note that we are only checking that the value is an array here and not
                // the array elements. That must come later.

                if ( ! is_array($value) ) {
                    $this->logAndThrowException(
                        sprintf("%s must be an array, '%s' given", $property, gettype($value))
                    );
                }
                break;

            case 'comment':
            case 'engine':
                if ( ! is_string($value) ) {
                    $this->logAndThrowException(
                        sprintf("%s name must be a string, '%s' given", $property, gettype($value))
                    );
                }
                break;

            default:
                break;
        }  // switch ( $property )

        return $value;

    }  // filterAndVerifyValue()

    /* ------------------------------------------------------------------------------------------
     * Verify the table by checking that any columns referenced in the indexes are present
     * in the column definitions.
     *
     * @see iEntity::verify()
     *
     * @throws Exception If there are errors during validation
     * ------------------------------------------------------------------------------------------
     */

    public function verify()
    {
        // Verify index columns match table columns

        $columnNames = $this->getColumnNames();

        foreach ( $this->indexes as $index ) {
            $missingColumnNames = array_diff($index->columns, $columnNames);
            if ( 0 != count($missingColumnNames) ) {
                $this->logAndThrowException(
                    sprintf("Columns in index '%s' not found in table definition: %s", $index->name, implode(", ", $missingColumnNames))
                );
            }
        }  // foreach ( $this->indexes as $index )

        // Verify constraint columns match table columns

        foreach ( $this->constraints as $constraint ) {
            $missingColumnNames = array_diff($constraint->columns, $columnNames);
            if ( 0 != count($missingColumnNames) ) {
                $this->logAndThrowException(
                    sprintf("Columns in constraint '%s' not found in table definition: %s", $constraint->name, implode(", ", $missingColumnNames))
                );
            }
        }  // foreach ( $this->constraints as $constraint )

        return true;

    }  // verify()

    /* ------------------------------------------------------------------------------------------
     * Discover a Table using the database information schema and populate this object
     * with the result. When creating a Table to be discovered, pass a NULL $config
     * object to the constructor.
     *
     * @param string $source The name of the table to discover
     * @param iRdbmsEndpoint $endpoint The DataEndpoint used to connect to the database
     *   (provides schema)
     *
     * @see iDiscoverableEntity::discover()
     * ------------------------------------------------------------------------------------------
     */

    public function discover($source)
    {
        if ( 2 != func_num_args() ) {
            $this->logAndThrowException(
                sprintf('%s expected 2 arguments, got %d', __FUNCTION__, func_num_args())
            );
        }

        $endpoint = func_get_arg(1);

        if ( ! $endpoint instanceof iRdbmsEndpoint ) {
            $this->logAndThrowException(
                sprintf(
                    '%s expected object implementing iRdbmsEndpoint, got %s',
                    __FUNCTION__,
                    ( is_object($endpoint) ? get_class($endpoint) : gettype($endpoint) )
                )
            );
        }

        $this->resetPropertyValues();

        $schemaName = null;
        $qualifiedTableName = null;
        $systemQuoteChar = $endpoint->getSystemQuoteChar();

        // If a schema was specified in the table name use it, otherwise use the default schema

        if ( false === strpos($source, ".") ) {
            $schemaName = $endpoint->getSchema();
            $qualifiedTableName = sprintf('%s.%s', $schemaName, $source);
        } else {
            $qualifiedTableName = $source;
            list($schemaName, $source) = explode(".", $source);
        }

        $params = array(':schema'    => $schemaName,
                        ':tablename' => $source);

        $this->logger->debug("Discover table '$qualifiedTableName'");

        // Query table properties

        $sql = "SELECT
engine, table_comment as comment
FROM information_schema.tables
WHERE table_schema = :schema
AND table_name = :tablename";

        try {
            $result = $endpoint->getHandle()->query($sql, $params);
            if ( count($result) > 1 ) {
                $this->logAndThrowException("Multiple rows returned for table '$qualifiedTableName'");
            }

            // The table did not exist, return false

            if ( 0 == count($result) ) {
                return false;
            }

        } catch (Exception $e) {
            $this->logAndThrowException("Error discovering table '$qualifiedTableName': " . $e->getMessage());
        }

        $row = array_shift($result);

        $this->name = $source;
        $this->schema = $schemaName;
        $this->engine = $row['engine'];
        $this->comment = $row['comment'];

        // Query columns. Querying for the default needs some explaining. The information schema stores
        // the default as null unless one was specifically provided so we need some logic to get things
        // into the shape we want.

        // SMG: We should do a better job of detecting equivalent columns. For example "int unsigned" is
        // equivalent to "int(10) unsigned".

        $sql = "SELECT
column_name as name, column_type as type, is_nullable as nullable,
column_default as " . $endpoint->quoteSystemIdentifier("default") . ",
IF('' = extra, NULL, extra) as extra,
IF('' = column_comment, NULL, column_comment) as " . $endpoint->quoteSystemIdentifier("comment") . "
FROM information_schema.columns
WHERE table_schema = :schema
AND table_name = :tablename
ORDER BY ordinal_position ASC";

        try {
            $result = $endpoint->getHandle()->query($sql, $params);
            if ( 0 == count($result) ) {
                $this->logAndThrowException("No columns returned for table '$qualifiedTableName'");
            }
        } catch (Exception $e) {
            $this->logAndThrowException("Error discovering table '$qualifiedTableName' columns: " . $e->getMessage());
        }

        foreach ( $result as $row ) {
            $this->addColumn((object) $row);
        }

        // Query indexes.

        $sql = "SELECT
index_name as name, index_type as " . $endpoint->quoteSystemIdentifier("type") . ", (non_unique = 0) as is_unique,
GROUP_CONCAT(column_name ORDER BY seq_in_index ASC) as columns
FROM information_schema.statistics
WHERE table_schema = :schema
AND table_name = :tablename
GROUP BY index_name
ORDER BY index_name ASC";

        try {
            $result = $endpoint->getHandle()->query($sql, $params);
        } catch (Exception $e) {
            $this->logAndThrowException("Error discovering table '$qualifiedTableName' indexes: " . $e->getMessage());
        }

        foreach ( $result as $row ) {
            $row['columns'] = explode(",", $row['columns']);
            $this->addIndex((object) $row);
        }

        // Query constraints.

        $sql = <<<SQL
SELECT
    tc.constraint_name AS name,
    GROUP_CONCAT(kcu.column_name ORDER BY position_in_unique_constraint ASC) AS columns,
    kcu.referenced_table_name AS referenced_table,
    GROUP_CONCAT(kcu.referenced_column_name ORDER BY position_in_unique_constraint ASC) AS referenced_columns
FROM information_schema.table_constraints tc
INNER JOIN information_schema.key_column_usage kcu
    ON tc.table_schema = kcu.table_schema
    AND tc.table_name = kcu.table_name
    AND tc.constraint_schema = kcu.constraint_schema
    AND tc.constraint_name = kcu.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY'
    AND tc.table_schema = :schema
    AND tc.table_name = :tablename
GROUP BY tc.constraint_name
ORDER BY tc.constraint_name ASC
SQL;

        try {
            $result = $endpoint->getHandle()->query($sql, $params);
        } catch (Exception $e) {
            $this->logAndThrowException("Error discovering table '$qualifiedTableName' constraints: " . $e->getMessage());
        }

        foreach ( $result as $row ) {
            $row['columns'] = explode(',', $row['columns']);
            $row['referenced_columns'] = explode(',', $row['referenced_columns']);
            $this->addConstraint((object) $row);
        }

        // Query triggers

        $sql = "SELECT
trigger_name as name, action_timing as time, event_manipulation as event,
event_object_schema as " . $endpoint->quoteSystemIdentifier("schema") . ", event_object_table as " . $endpoint->quoteSystemIdentifier("table") . ", definer,
action_statement as body
FROM information_schema.triggers
WHERE event_object_schema = :schema
and event_object_table = :tablename
ORDER BY trigger_name ASC";

        try {
            $result = $endpoint->getHandle()->query($sql, $params);
        } catch (Exception $e) {
            $this->logAndThrowException("Error discovering table '$qualifiedTableName' triggers: " . $e->getMessage());
        }

        foreach ( $result as $row ) {
            $this->addTrigger((object) $row);
        }

        return true;

    }  // discover()

    /* ------------------------------------------------------------------------------------------
     * Add a column to this table.
     *
     * @param $config An object containing the column definition, or a Column object to add
     * @param $overwriteDuplicates TRUE to allow overwriting of duplicate column names. If false, throw
     * an exception if a duplicate column is added.
     *
     * @return This object to support method chaining.
     *
     * @throw Exception if the new item has the same name as an existing item and
     *   overwrite is not TRUE
     * ------------------------------------------------------------------------------------------
     */

    public function addColumn($config, $overwriteDuplicates = false)
    {
        $item = ( is_object($config) && $config instanceof Column
                  ? $config
                  : new Column($config, $this->systemQuoteChar, $this->logger) );

        if ( array_key_exists($item->name, $this->columns) && ! $overwriteDuplicates ) {
            $this->logAndThrowException(
                sprintf("Cannot add duplicate column '%s'", $item->name),
                array('log_level' => PEAR_LOG_WARNING)
            );
        }

        $this->properties['columns'][$item->name] = $item;

        return $this;

    }  // addColumn()

    /* ------------------------------------------------------------------------------------------
     * Get the list of column names.
     *
     * @return An array of column names.
     * ------------------------------------------------------------------------------------------
     */

    public function getColumnNames()
    {
        return array_keys($this->columns);
    }  // getColumnNames()

    /* ------------------------------------------------------------------------------------------
     * Get a Column object with the specified name.
     *
     * @param $name The column to retrieve.
     *
     * @return The Column object with the specified name or FALSE if the trigger does not exist
     * ------------------------------------------------------------------------------------------
     */

    public function getColumn($name)
    {
        return ( array_key_exists($name, $this->columns) ? $this->properties['columns'][$name] : false );
    }  // getColumn()

    /* ------------------------------------------------------------------------------------------
     * Add an index to this table.
     *
     * @param $config An object containing the column definition, or a Column object to add
     * @param $overwriteDuplicates TRUE to allow overwriting of duplicate column names. If false, throw
     * an exception if a duplicate column is added.
     *
     * @return This object to support method chaining.
     *
     * @throw Exception if the new item has the same name as an existing item and
     *   overwrite is not TRUE
     * ------------------------------------------------------------------------------------------
     */

    public function addIndex($config, $overwriteDuplicates = false)
    {
        $item = ( is_object($config) && $config instanceof Index
                  ? $config
                  : new Index($config, $this->systemQuoteChar, $this->logger) );

        if ( array_key_exists($item->name, $this->indexes) && ! $overwriteDuplicates ) {
            $this->logAndThrowException(
                sprintf("Cannot add duplicate index '%s'", $item->name)
            );
        }

        $this->properties['indexes'][$item->name] = $item;

        return $this;

    }  // addIndex()

    /* ------------------------------------------------------------------------------------------
     * Get the list of column names.
     *
     * @return An array of column names.
     * ------------------------------------------------------------------------------------------
     */

    public function getIndexNames()
    {
        return array_keys($this->indexes);
    }  // getIndexNames()

    /* ------------------------------------------------------------------------------------------
     * Get an Index object with the specified name.
     *
     * @param $name The name of the index to retrieve.
     *
     * @return The Index object with the specified name or FALSE if the trigger does not exist
     * ------------------------------------------------------------------------------------------
     */

    public function getIndex($name)
    {
        return ( array_key_exists($name, $this->indexes) ? $this->properties['indexes'][$name] : false );
    }  // getIndex()

    /* ------------------------------------------------------------------------------------------
     * Add a constraint to this table.
     *
     * @param $config An object containing the column definition, or a Column object to add
     * @param $overwriteDuplicates TRUE to allow overwriting of duplicate column names. If false, throw
     * an exception if a duplicate column is added.
     *
     * @return This object to support method chaining.
     *
     * @throw Exception if the new item has the same name as an existing item and
     *   overwrite is not TRUE
     * ------------------------------------------------------------------------------------------
     */

    public function addConstraint($config, $overwriteDuplicates = false)
    {
        $item = ( is_object($config) && $config instanceof Constraint
                  ? $config
                  : new Constraint($config, $this->systemQuoteChar, $this->logger) );

        if ( array_key_exists($item->name, $this->constraints) && ! $overwriteDuplicates ) {
            $this->logAndThrowException(
                sprintf("Cannot add duplicate constraint '%s'", $item->name)
            );
        }

        $this->properties['constraints'][$item->name] = $item;

        return $this;

    }  // addConstraint()

    /* ------------------------------------------------------------------------------------------
     * Get the list of constraint names.
     *
     * @return array An array of column names.
     * ------------------------------------------------------------------------------------------
     */

    public function getConstraintNames()
    {
        return array_keys($this->constraints);
    }  // getConstraintNames()

    /* ------------------------------------------------------------------------------------------
     * Get an Constraint object with the specified name.
     *
     * @param $name The name of the constraint to retrieve.
     *
     * @return The Constraint object with the specified name or FALSE if the trigger does not exist
     * ------------------------------------------------------------------------------------------
     */

    public function getConstraint($name)
    {
        return ( array_key_exists($name, $this->constraints) ? $this->properties['constraints'][$name] : false );
    }  // getConstraint()

    /* ------------------------------------------------------------------------------------------
     * Add a trigger to this table.
     *
     * @param $config An object containing the column definition, or a Column object to add
     * @param $overwriteDuplicates TRUE to allow overwriting of duplicate column names. If false, throw
     * an exception if a duplicate column is added.
     *
     * @return This object to support method chaining.
     *
     * @throw Exception if the new item has the same name as an existing item and
     *   overwrite is not TRUE
     * ------------------------------------------------------------------------------------------
     */

    public function addTrigger($config, $overwriteDuplicates = false)
    {

        $item = ( is_object($config) && $config instanceof Trigger
                  ? $config
                  : new Trigger($config, $this->systemQuoteChar, $this->logger) );

        if ( array_key_exists($item->name, $this->triggers) && ! $overwriteDuplicates ) {
            $this->logAndThrowException(
                sprintf("Cannot add duplicate trigger '%s'", $item->name)
            );
        }

        $this->properties['triggers'][$item->name] = $item;

        return $this;

    }  // addTrigger()

    /* ------------------------------------------------------------------------------------------
     * Get the list of trigger names.
     *
     * @return An array of trigger names.
     * ------------------------------------------------------------------------------------------
     */

    public function getTriggerNames()
    {
        return array_keys($this->triggers);
    }  // getTriggerNames()

    /* ------------------------------------------------------------------------------------------
     * Get a Trigger object with the specified name.
     *
     * @param $name The trigger to retrieve.
     *
     * @return The Trigger object with the specified name or FALSE if the trigger does not exist
     * ------------------------------------------------------------------------------------------
     */

    public function getTrigger($name)
    {
        return ( array_key_exists($name, $this->triggers) ? $this->properties['triggers'][$name] : false );
    }  // getTrigger()

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::getSql()
     * ------------------------------------------------------------------------------------------
     */

    public function getSql($includeSchema = true)
    {
        if ( null === $this->name || 0 == count($this->columns) ) {
            return false;
        }

        // Note: By using the name as the key, duplicate item names will use the last definition. This
        // can occur when creating aggregation tables that contain a "year" column and an
        // ${AGGREGATION_UNIT} column with an aggregation unit of "year".

        $columnCreateList = array();
        foreach ( $this->columns as $name => $column ) {
            $columnCreateList[$name] = $column->getSql($includeSchema);
        }

        $indexCreateList = array();
        foreach ( $this->indexes as $name => $index ) {
            $indexCreateList[$name] = $index->getSql($includeSchema);
        }

        $constraintCreateList = array();
        foreach ( $this->constraints as $name => $constraint ) {
            $constraintCreateList[$name] = $constraint->getSql($includeSchema);
        }

        $triggerCreateList = array();
        foreach ( $this->triggers as $name => $trigger ) {
            // The table schema may have been set after the table was initially created. If the trigger
            // doesn't explicitly define a schema, default to the table's schema.
            if ( null === $trigger->schema ) {
                $trigger->schema = $this->schema;
            }
            $triggerCreateList[$name] = $trigger->getSql($includeSchema);
        }

        $tableName = ( $includeSchema ? $this->getFullName() : $this->getName(true) );

        $sqlList = array();
        $sqlList[] = "CREATE TABLE IF NOT EXISTS $tableName (\n" .
            "  " . implode(",\n  ", $columnCreateList) .
            ( 0 != count($indexCreateList) ? ",\n  " . implode(",\n  ", $indexCreateList) : "" ) .
            ( 0 != count($constraintCreateList) ? ",\n  " . implode(",\n  ", $constraintCreateList) : "" ) .
            "\n" . ")" .
            ( null !== $this->engine ? " ENGINE = " . $this->engine : "" ) .
            ( null !== $this->comment && ! empty($this->comment) ? " COMMENT = '" . addslashes($this->comment) . "'" : "" ) .
            ";";

        foreach ( $triggerCreateList as $trigger ) {
            $sqlList[] = $trigger;
        }

        return $sqlList;

    }  // getSql()

    /* ------------------------------------------------------------------------------------------
     * @param Table $destination The desired Table definition
     *
     * @see iAlterableEntity::getAlterSql()
     * ------------------------------------------------------------------------------------------
     */

    public function getAlterSql($destination, $includeSchema = true)
    {
        if ( ! $destination instanceof Table ) {
            $this->logAndThrowException(
                sprintf(
                    '%s expected Table object, got %s',
                    __FUNCTION__,
                    ( is_object($destination) ? get_class($destination) : gettype($destination) )
                )
            );
        }

        $alterList = array();
        $triggerList = array();

        // Update names/docs to be clearer. We are migrating $this to $dest.

        // --------------------------------------------------------------------------------
        // Process columns

        $currentColNames = $this->getColumnNames();
        $destColNames = $destination->getColumnNames();

        // Columns to be dropped, added, changed, or renamed
        $dropColNames = array_diff($currentColNames, $destColNames);
        $addColNames = array_diff($destColNames, $currentColNames);
        $changeColNames = array_intersect($currentColNames, $destColNames);
        $renameColNames = array();

        // When renaming a column, be smart about it or a simple rename will mean that the new column is
        // added and the old column is dropped causing potential data loss.  Check for any processing
        // hints on new columns: If a column is to be added, and there is a "rename_from" hint that
        // matches an existing column name, mark this column to be renamed instead of added and dropped.
        // We can then construct the CHANGE COLUMN statement.

        foreach ( $addColNames as $index => $addName ) {
            $hint = $destination->getColumn($addName)->hints;
            if ( null !== $hint
                 && isset($hint->rename_from)
                 && false !== ( $hintIndex = array_search($hint->rename_from, $dropColNames) ) )
            {
                $renameColNames[$hint->rename_from] = $addName;
                unset($addColNames[$index]);
                unset($dropColNames[$hintIndex]);
            }
        }  // foreach ( $addColNames as $addName )

        if ( $this->engine != $destination->engine ) {
            $alterList[] = "ENGINE = " . $destination->engine;
        }

        if ( $this->comment != $destination->comment ) {
            $alterList[] = "COMMENT = '" . addslashes($destination->comment) . "'";
        }

        foreach ( $addColNames as $name ) {
            $alterList[] = "ADD COLUMN " . $destination->getColumn($name)->getSql($includeSchema);
        }

        foreach ( $dropColNames as $name ) {
            $alterList[] = "DROP COLUMN " . $this->quote($name);
            $this->logger->warning(sprintf("Dropping column %s!", $this->quote($name)));
        }

        foreach ( $changeColNames as $name ) {
            $destColumn = $destination->getColumn($name);
            // Not all properties are required so a simple object comparison isn't possible
            if ( 0 == ($compareCode = $destColumn->compare($this->getColumn($name))) ) {
                continue;
            } else {
                $this->logger->debug(
                    sprintf("Column comparison for '%s' returned %d", $name, $compareCode)
                );
            }

            $alterList[] = "CHANGE COLUMN " . $destColumn->getName(true) . " " . $destColumn->getSql($includeSchema);
        }

        foreach ( $renameColNames as $fromColumnName => $toColumnName ) {
            $destColumn = $destination->getColumn($toColumnName);
            $currentColumn = $this->getColumn($fromColumnName);
            // Not all properties are required so a simple object comparison isn't possible
            if ( 0 == ($compareCode = $destColumn->compare($currentColumn)) ) {
                continue;
            } else {
                $this->logger->debug(
                    sprintf("Column comparison for '%s' returned %d", $fromColumnName, $compareCode)
                );
            }
            $alterList[] = "CHANGE COLUMN " . $currentColumn->getName(true) . " " . $destColumn->getSql($includeSchema);
        }

        // --------------------------------------------------------------------------------
        // Processes indexes

        $currentIndexNames = $this->getIndexNames();
        $destIndexNames = $destination->getIndexNames();

        $dropIndexNames = array_diff($currentIndexNames, $destIndexNames);
        $addIndexNames = array_diff($destIndexNames, $currentIndexNames);
        $changeIndexNames = array_intersect($currentIndexNames, $destIndexNames);

        foreach ( $dropIndexNames as $name ) {
            $alterList[] = "DROP INDEX " . $this->quote($name);
        }

        foreach ( $addIndexNames as $name ) {
            $alterList[] = "ADD " . $destination->getIndex($name)->getSql($includeSchema);
        }

        // Altered indexes need to be dropped then added
        foreach ( $changeIndexNames as $name ) {
            $destIndex = $destination->getIndex($name);
            // Not all properties are required so a simple object comparison isn't possible
            if ( 0 == $destIndex->compare($this->getIndex($name)) ) {
                continue;
            }
            $alterList[] = "DROP INDEX " . $destIndex->getName(true);
            $alterList[] = "ADD " . $destIndex->getSql($includeSchema);
        }

        // --------------------------------------------------------------------------------
        // Processes constraints

        $currentConstraintNames = $this->getConstraintNames();
        $destConstraintNames = $destination->getConstraintNames();

        $dropConstraintNames = array_diff($currentConstraintNames, $destConstraintNames);
        $addConstraintNames = array_diff($destConstraintNames, $currentConstraintNames);
        $changeConstraintNames = array_intersect($currentConstraintNames, $destConstraintNames);

        foreach ( $dropConstraintNames as $name ) {
            $alterList[] = 'DROP FOREIGN KEY ' . $this->quote($name);
        }

        foreach ( $addConstraintNames as $name ) {
            $alterList[] = 'ADD ' . $destination->getConstraint($name)->getSql($includeSchema);
        }

        // Altered constraints need to be dropped then added
        foreach ( $changeConstraintNames as $name ) {
            $destConstraint = $destination->getConstraint($name);
            // Not all properties are required so a simple object comparison isn't possible
            if ( 0 == $destConstraint->compare($this->getConstraint($name)) ) {
                continue;
            }
            $alterList[] = 'DROP FOREIGN KEY ' . $destConstraint->getName(true);
            $alterList[] = 'ADD ' . $destConstraint->getSql($includeSchema);
        }

        // --------------------------------------------------------------------------------
        // Process triggers

        // The table schema may have been set after the table was initially created. If the trigger
        // doesn't explicitly define a schema, default to the table's schema.

        // if ( null === $trigger->getSchema() ) $trigger->setSchema($this->getSchema());

        $currentTriggerNames = $this->getTriggerNames();
        $destTriggerNames = $destination->getTriggerNames();

        $dropTriggerNames = array_diff($currentTriggerNames, $destTriggerNames);
        $addTriggerNames = array_diff($destTriggerNames, $currentTriggerNames);
        $changeTriggerNames = array_intersect($currentTriggerNames, $destTriggerNames);

        // Drop triggers first, then alter, then create

        foreach ( $dropTriggerNames as $name ) {
            $triggerList[] = "DROP TRIGGER " .
                ( null !== $this->schema && $includeSchema ? $this->quote($this->schema) . "." : "" ) .
                $this->quote($name) . ";";
        }

        foreach ( $changeTriggerNames as $name ) {
            $destTrigger = $destination->getTrigger($name);
            if ( 0 == $destTrigger->compare($this->getTrigger($name))) {
                continue;
            }

            $triggerList[] = "DROP TRIGGER " .
                ( null !== $this->schema && $includeSchema ? $this->quote($this->schema) . "." : "" ) .
                $this->quote($name) . ";";
            $triggerList[] = $destination->getTrigger($name)->getSql($includeSchema);
        }

        foreach ( $addTriggerNames as $name ) {
            $triggerList[] = $destination->getTrigger($name)->getSql($includeSchema);
        }

        // --------------------------------------------------------------------------------
        // Put it all together

        if ( 0 == count($alterList) && 0 == count($triggerList) ) {
            return false;
        }

        $tableName = ( $includeSchema ? $this->getFullName() : $this->getName(true) );

        $sqlList = array();
        if ( 0 != count($alterList) ) {
            $sqlList[] = "ALTER TABLE $tableName\n" .
                implode(",\n", $alterList) . ";";
        }

        if ( 0 != count($triggerList) ) {
            foreach ( $triggerList as $trigger ) {
                $sqlList[] = $trigger;
            }
        }

        return $sqlList;

    }  // getAlterSql()


    /* ------------------------------------------------------------------------------------------
     * iEntity::toStdClass()
     * ------------------------------------------------------------------------------------------
     */

    public function toStdClass()
    {
        $data = parent::toStdClass();

        // When we add columns, indexes, and triggers to a table we add them as an
        // associative array where the keys are the column names. When generating the
        // config With string keys Entity::_toStdClass() will assume an object because the
        // keys are strings so convert them to arrays here.

        $data->columns = array_values((array) $data->columns);
        $data->indexes = array_values((array) $data->indexes);
        $data->constraints = array_values((array) $data->constraints);
        $data->triggers = array_values((array) $data->triggers);

        return $data;

    }  // toStdClass()

    /* ------------------------------------------------------------------------------------------
     * @see Entity::__set()
     * ------------------------------------------------------------------------------------------
     */

    public function __set($property, $value)
    {
        // If we are not setting a property that is a special case, just call the main setter
        $specialCaseProperties = array('columns', 'indexes', 'constraints', 'triggers');

        if ( ! in_array($property, $specialCaseProperties) ) {
            parent::__set($property, $value);
            return;
        }

        // Verify values prior to doing anything with them

        $value = $this->filterAndVerifyValue($property, $value);

        // Handle special cases.

        switch ($property) {
            case 'columns':
                $this->properties[$property] = array();
                // Clear the array no matter what, that way NULL is handled properly.
                if ( null !== $value ) {
                    foreach ( $value as $item ) {
                        $column = ( is_object($item) && $item instanceof Column
                                    ? $item
                                    : new Column($item, $this->systemQuoteChar, $this->logger) );
                        $this->properties[$property][$column->name] = $column;
                    }
                }
                break;

            case 'indexes':
                $this->properties[$property] = array();
                // Clear the array no matter what, that way NULL is handled properly.
                if ( null !== $value ) {
                    foreach ( $value as $item ) {
                        $index = ( is_object($item) && $item instanceof Index
                                   ? $item
                                   : new Index($item, $this->systemQuoteChar, $this->logger) );
                        $this->properties[$property][$index->name] = $index;
                    }
                }
                break;

            case 'constraints':
                $this->properties[$property] = array();
                // Clear the array no matter what, that way NULL is handled properly.
                if ( null !== $value ) {
                    foreach ( $value as $item ) {
                        $constraint = ( is_object($item) && $item instanceof Constraint
                                   ? $item
                                   : new Constraint($item, $this->systemQuoteChar, $this->logger) );
                        $this->properties[$property][$constraint->name] = $constraint;
                    }
                }
                break;

            case 'triggers':
                $this->properties[$property] = array();
                // Clear the array no matter what, that way NULL is handled properly.
                if ( null !== $value ) {
                    foreach ( $value as $item ) {
                        if ( is_object($item) && $item instanceof Trigger ) {
                            $this->properties[$property][$item->name] = $item;
                        } else {
                            if ( $item instanceof stdClass ) {
                                // Default to the current table name and schema of the parent table.
                                if ( ! isset($item->table) ) {
                                    $item->table = $this->name;
                                }
                                if ( ! isset($item->schema) ) {
                                    $item->schema = $this->schema;
                                }
                            }
                            $trigger = new Trigger($item, $this->systemQuoteChar, $this->logger);
                            $this->properties[$property][$trigger->name] = $trigger;
                        }
                    }
                }
                break;

            default:
                break;
        }  // switch($property)

    }  // __set()
}  // class Table
