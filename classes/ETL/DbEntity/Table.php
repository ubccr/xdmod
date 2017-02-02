<?php
/* ==========================================================================================
 * Class for managing tables in the data warehouse.  Table and column/index/trigger names are fully
 * escaped to support special characters. Functionality is provided for:
 *
 * 1. Creating table structure by parsing a JSON file
 *    - Both succinct (array-based) and verbose (object-based) definition formats are supported
 * 2. Discovering table structure from an existing MySQL database
 * 3. Comparing an existing (parsed) table definition with a discovered table and determining the
 *    proper actions needed to bring the existing table into line with the definition
 * 4. Generating a MySQL CREATE TABLE and associated CREATE TRIGGER statements
 * 5. Generating an ALTER TABLE and DROP/CREATE TIGGER statements
 * 6. Generating a JSON representation of the table.
 *
 * The Table class makes use of the following classes that implement the iTableItem interface:
 *
 * - Column
 * - Index
 * - Trigger
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-10-29
 * ==========================================================================================
 */

namespace ETL\DbEntity;

use ETL\DataEndpoint\iRdbmsEndpoint;
use \Log;
use \stdClass;

class Table extends aNamedEntity
{
    // Optional filename to the definition file
    protected $filename = null;

    // Optional table comment
    protected $comment = null;

    // Optional table engine
    protected $engine = null;

    // Associative array where the keys are column names and the values are Column objects
    protected $columns = array();

    // Associative array where the keys are index names and the values are Index objects
    protected $indexes = array();

    // Associative array where the keys are trigger names and the values are Trigger objects
    protected $triggers = array();

    /* ------------------------------------------------------------------------------------------
     * Construct a table object from a JSON definition file or a definition object. The definition
     * must contain, at a minimum, name and columns properties.
     *
     * @param $config Mixed Either a filename for the JSON definition file or an object containing the
     *   table definition
     *
     * @throw Exception If the argument is not a string or instance of stdClass
     * @throw Exception If the table definition was incomplete
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($config, $systemQuoteChar = null, Log $logger = null)
    {
        parent::__construct($systemQuoteChar, $logger);

        // If an object was passed in of stdClass assume it is the table definition, otherwise assume it
        // is a filename and parse it. I am intentionally not storing the config as a property so we
        // don't need to carry it around if there are many of these objects.

        if ( ! is_object($config) && is_string($config) ) {
            $config = $this->parseJsonFile($config, "Table Definition");
        } elseif ( ! $config instanceof stdClass) {
            $msg = __CLASS__ . ": Argument is not a filename or object";
            $this->logAndThrowException($msg);
        }

        // Support the table config directly or assigned to a "table_definition" key

        if ( isset($config->table_definition) ) {
            $config = $config->table_definition;
        }

        // Check for required properties

        $requiredKeys = array("name", "columns");
        $this->verifyRequiredConfigKeys($requiredKeys, $config);

        $this->initialize($config);

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see aNamedEntity::initialize()
     * ------------------------------------------------------------------------------------------
     */

    protected function initialize(stdClass $config, $force = false)
    {
        if ( $this->initialized && ! $force ) {
            return true;
        }

        $this->initialized = false;

        $this->name = $config->name;
        $this->schema = ( isset($config->schema) ? $config->schema : null );
        $this->comment = ( isset($config->comment) ? $config->comment : null );
        $this->engine = ( isset($config->engine) ? $config->engine : null );

        // Set columns. The value of columns key can be an array of column arrays (numeric keys), or an
        // object containing multiple column objects. Both of these are iterables.

        $columns = $config->columns;

        foreach ( $columns as $key => $definition ) {

            if ( is_object($definition) &&
                 ! is_numeric($key)
                 && ! isset($definition->name) ) {
                // If the index name is not provided, allow shorthand for using the index key as the name
                $definition->name = $key;
            }

            $this->addColumn($definition, true);

        }  // foreach ( $columns as $key => $definition )


        // Set indexes

        if ( isset($config->indexes) ) {

            $indexes =  $config->indexes;
            foreach ( $indexes as $key => $definition ) {
                $this->addIndex($definition);
            }

        }  // if ( isset($config->indexes) )


        // Set triggers

        if ( isset($config->triggers) ) {
            $triggers =  $config->triggers;
            foreach ( $triggers as $key => $definition ) {
                // Default to the current table name and schema of the parent table.
                if ( ! isset($definition->table) ) {
                    $definition->table = $this->name;
                }
                if ( ! isset($definition->schema) ) {
                    $definition->schema = $this->schema;
                }
                $this->addTrigger($definition);
            }
        }

        $this->initialized = true;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * Verify the table. This includes ensuring any index colums match column names.
     *
     * @return true on success
     * @throws Exception If there are errors during validation
     * ------------------------------------------------------------------------------------------
     */

    public function verify()
    {
        // Verify index columns match table columns

        $columnNames = $this->getColumnNames();

        foreach ( $this->getIndexes() as $index ) {
            $indexColumns = $index->getColumnNames();
            $missingColumnNames = array_diff($indexColumns, $columnNames);
            if ( 0 != count($missingColumnNames) ) {
                $msg = "Columns in index '" . $index->getName() . "' not found in table definition: " .
                    implode(", ", $missingColumnNames);
                $this->logAndThrowException($msg);
            }
        }  // foreach ( $this->getIndexes() as $index )

    }  // verify()

    /* ------------------------------------------------------------------------------------------
     * Use the MySQL information schema to build a Table object from an existing table.
     *
     * @param $tableName The name of the table to discover
     * @param $endpoint The DataEndpoint used to connect to the database (provides schema)
     * @param $systemQuoteChar The system quote character for the database that we are
     *   interrogating. If NULL, the system quote character will be taken from the endpoint.
     * @param $log The system logger
     *
     * @return A Table object constructed from the table definition in MySQL, or false if the table
     *   name was not found.
     * @throws Exception If there was an error querying or constructing the table
     * ------------------------------------------------------------------------------------------
     */

    public static function discover(
        $tableName,
        iRdbmsEndpoint $endpoint,
        $systemQuoteChar = null,
        Log $logger = null
    ) {
        $schemaName = null;
        $qualifiedTableName = null;

        $systemQuoteChar = ( null === $systemQuoteChar
                             ? $endpoint->getSystemQuoteChar()
                             : $systemQuoteChar );

        // If a schema was specified in the table name use it, otherwise use the default schema

        if ( false === strpos($tableName, ".") ) {
            $schemaName = $endpoint->getSchema();
            $qualifiedTableName = $schemaName . "." . $tableName;
        } else {
            $qualifiedTableName = $tableName;
            list($schemaName, $tableName) = explode(".", $tableName);
        }

        $params = array(':schema'    => $schemaName,
                        ':tablename' => $tableName);

        if ( null !== $logger ) {
            $logger->debug("Discover table '$qualifiedTableName'");
        }

        // Query table properties

        $sql = "SELECT
engine, table_comment as comment
FROM information_schema.tables
WHERE table_schema = :schema
AND table_name = :tablename";

        try {
            $result = $endpoint->getHandle()->query($sql, $params);
            if ( count($result) > 1 ) {
                $msg = "Multiple rows returned for table";
                $this->logAndThrowException($msg);

            }

            // The table did not exist, return false

            if ( 0 == count($result) ) {
                return false;
            }

        } catch (Exception $e) {
            $msg = "Error discovering table '$qualifiedTableName': " . $e->getMessage();
            $this->logAndThrowException($msg);
        }

        $row = array_shift($result);

        $definition = array('name'    => $tableName,
                            'schema'  => $schemaName,
                            'engine'  => $row['engine'],
                            'columns' => array(),
                            'comment' => $row['comment'] );

        $newTable = new Table((object) $definition, $systemQuoteChar, $logger);

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
                $msg = "No columns returned";
                $this->logAndThrowException($msg);
            }
        } catch (Exception $e) {
            $msg = "Error discovering table '$qualifiedTableName': " . $e->getMessage();
            $this->logAndThrowException($msg);
        }

        foreach ( $result as $row ) {
            $newTable->addColumn((object) $row);
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
            $msg = "Error discovering table '$qualifiedTableName': " . $e->getMessage();
            $this->logAndThrowException($msg);
        }

        foreach ( $result as $row ) {
            $row['columns'] = explode(",", $row['columns']);
            $newTable->addIndex((object) $row);
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
            $msg = "Error discovering table '$qualifiedTableName': " . $e->getMessage();
            $this->logAndThrowException($msg);
        }

        foreach ( $result as $row ) {
            $newTable->addTrigger((object) $row);
        }

        return $newTable;

    }  // discover()

    /* ------------------------------------------------------------------------------------------
     * In addition to the table schema, set the schema on any triggers if they do not have one set.
     * Since a table's schema is typically set after the table is constructed, entities that can
     * maintain their own schema also need to be updated.
     *
     * @see aNamedEntity::setSchema()
     * ------------------------------------------------------------------------------------------
     */

    public function setSchema($schema)
    {
        parent::setSchema($schema);

        foreach ( $this->triggers as $trigger ) {
            if ( null === $trigger->getSchema() ) {
                $trigger->setSchema($schema);
            }
        }

        return $this;

    }  // setSchema()

    /* ------------------------------------------------------------------------------------------
     * @return The engine for this table
     * ------------------------------------------------------------------------------------------
     */

    public function getEngine()
    {
        return $this->engine;
    }  // getEngine()

    /* ------------------------------------------------------------------------------------------
     * @return The comment for this table
     * ------------------------------------------------------------------------------------------
     */

    public function getComment()
    {
        return $this->comment;
    }  // geComment()

    /* ------------------------------------------------------------------------------------------
     * Add a column to this table.
     *
     * @param $definition An array or object containing the column definition, or an instantiated
     *   Column object to add
     * @param $overwriteDuplicates true to allow overwriting of duplicate column names. If false, throw
     * an exception if a duplicate column is added.
     *
     * @return This object to support method chaining.
     *
     * @throw Exception if the new item does not implement the iTableItem interface
     * @throw Exception if the new item has the same name as an existing item
     * ------------------------------------------------------------------------------------------
     */

    public function addColumn($definition, $overwriteDuplicates = false)
    {
        $item = ( $definition instanceof Column ? $definition : new Column($definition, $this->getSystemQuoteChar()) );

        if ( ! ($item instanceof iTableItem) ) {
            $msg = "Column does not implement interface iTableItem";
            $this->logAndThrowException($msg);
        }

        $name = $item->getName();

        if ( array_key_exists($name, $this->columns) && ! $overwriteDuplicates ) {
            $this->logAndThrowException(
                "Cannot add duplicate column '$name'",
                array('log_level' => PEAR_LOG_WARNING)
            );
        }

        $this->columns[ $name ] = $item;

        return $this;

    }  // addColumn()

    /* ------------------------------------------------------------------------------------------
     * Get the list of column objects.
     *
     * @return An array of Column objects.
     * ------------------------------------------------------------------------------------------
     */

    public function getColumns()
    {
        return $this->columns;
    }  // getColumns()

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
     * @return The Column object with the specified name, or false if none exists.
     * ------------------------------------------------------------------------------------------
     */

    public function getColumn($name)
    {
        return ( array_key_exists($name, $this->columns) ? $this->columns[$name] : false );
    }  // getColumn()

    /* ------------------------------------------------------------------------------------------
     * Remove all columns from this Table.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function deleteColumns()
    {
        $this->columns = array();
        return $this;
    }  // deleteColumns()

    /* ------------------------------------------------------------------------------------------
     * Add an index to this table.
     *
     * @param $definition An array or object containing the index definition, or an instantiated
     *   Index object to add
     *
     * @return This object to support method chaining.
     *
     * @throw Exception if the new item does not implement the iTableItem interface
     * ------------------------------------------------------------------------------------------
     */

    public function addIndex($definition)
    {
        $item = ( $definition instanceof Index ? $definition : new Index($definition, $this->getSystemQuoteChar()) );

        if ( ! ($item instanceof iTableItem) ) {
            $msg = "Index does not implement interface iTableItem";
            $this->logAndThrowException($msg);
        }

        $name = $item->getName();

        if ( array_key_exists($name, $this->indexes) ) {
            $msg = "Cannot add duplicate index '$name'";
            $this->logAndThrowException($msg);
        }

        $this->indexes[ $name ] = $item;

        return $this;

    }  // addIndex()

    /* ------------------------------------------------------------------------------------------
     * Get the list of index objects.
     *
     * @return An array of Index objects.
     * ------------------------------------------------------------------------------------------
     */

    public function getIndexes()
    {
        return $this->indexes;
    }  // getIndexes()

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
     * @return The Index object with the specified name.
     * ------------------------------------------------------------------------------------------
     */

    public function getIndex($name)
    {
        return ( array_key_exists($name, $this->indexes) ? $this->indexes[$name] : false );
    }  // getIndex()

    /* ------------------------------------------------------------------------------------------
     * Remove all indexes from this Table.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function deleteIndexes()
    {
        $this->indexes = array();
        return $this;
    }  // deleteIndexes()

    /* ------------------------------------------------------------------------------------------
     * Add a trigger to this table.
     *
     * @param $definition An array or object containing the trigger definition, or an instantiated
     *   Trigger object to add
     *
     * @return This object to support method chaining.
     *
     * @throw Exception if the new item does not implement the iTableItem interface
     * ------------------------------------------------------------------------------------------
     */

    public function addTrigger($definition)
    {
        $item = ( $definition instanceof Trigger ? $definition : new Trigger($definition, $this->getSystemQuoteChar()) );

        if ( ! ($item instanceof iTableItem) ) {
            $msg = "Trigger does not implement interface iTableItem";
            $this->logAndThrowException($msg);
        }

        $this->triggers[ $item->getName() ] = $item;

        return $this;

    }  // addTrigger()

    /* ------------------------------------------------------------------------------------------
     * Get the list of trigger objects.
     *
     * @return An array of Trigger objects.
     * ------------------------------------------------------------------------------------------
     */

    public function getTriggers()
    {
        return $this->triggers;
    }  // getTriggers()

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
     * @return The Trigger object with the specified name.
     * ------------------------------------------------------------------------------------------
     */

    public function getTrigger($name)
    {
        return ( array_key_exists($name, $this->triggers) ? $this->triggers[$name] : false );
    }  // getTrigger()

    /* ------------------------------------------------------------------------------------------
     * Remove all triggers from this Table.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function deleteTriggers()
    {
        $this->triggers = array();
        return $this;
    }  // deleteTriggers()

    /* ------------------------------------------------------------------------------------------
     * Generate an array containing all SQL statements or fragments required to create the item. Note
     * that some items (such as triggers) may require multiple statements to alter them (e.g., DROP
     * TRIGGER, CREATE TRIGGER).
     *
     * @param $includeSchema true to include the schema in the item name, if appropriate.
     *
     * @return An array comtaining the SQL required for creating this item.
     * ------------------------------------------------------------------------------------------
     */

    public function getCreateSql($includeSchema = true)
    {
        if ( 0 == count($this->columns) ) {
            return false;
        }

        // Note: By using the name as the key, duplicate item names will use the last definition. This
        // can occur when creating aggregation tables that contain a "year" column and an
        // ${AGGREGATION_UNIT} column with an aggregation unit of "year".

        $columnCreateList = array();
        foreach ( $this->columns as $name => $column ) {
            $columnCreateList[$name] = $column->getCreateSql($includeSchema);
        }

        $indexCreateList = array();
        foreach ( $this->indexes as $name => $index ) {
            $indexCreateList[$name] = $index->getCreateSql($includeSchema);
        }

        $triggerCreateList = array();
        foreach ( $this->triggers as $name => $trigger ) {
            // The table schema may have been set after the table was initially created. If the trigger
            // doesn't explicitly define a schema, default to the table's schema.
            if ( null === $trigger->getSchema() ) {
                $trigger->setSchema($this->getSchema());
            }
            $triggerCreateList[$name] = $trigger->getCreateSql($includeSchema);
        }

        $tableName = ( $includeSchema ? $this->getFullName() : $this->getName(true) );

        $sqlList = array();
        $sqlList[] = "CREATE TABLE IF NOT EXISTS $tableName (\n" .
            "  " . implode(",\n  ", $columnCreateList) .
            ( 0 != count($indexCreateList) ? ",\n  " . implode(",\n  ", $indexCreateList) : "" ) . "\n" .
            ")" .
            ( null !== $this->engine ? " ENGINE = " . $this->engine : "" ) .
            ( null !== $this->comment && ! empty($this->comment) ? " COMMENT = '" . addslashes($this->comment) . "'" : "" ) .
            ";";

        foreach ( $triggerCreateList as $trigger ) {
            $sqlList[] = $trigger;
        }

        return $sqlList;

    }  // getCreateSql()

    /* ------------------------------------------------------------------------------------------
     * Generate an array containing all SQL statements or fragments required to alter the destination
     * table to match this table. Note that some items (such as triggers) may require multiple
     * statements to alter them (e.g., DROP TRIGGER, CREATE TRIGGER).
     *
     * @param $destTable A Table object containing the defintion of the table as we would like it to
     *   be.
     * @param $includeSchema true to include the schema in the item name, if appropriate.
     *
     * @return An array comtaining the SQL required for altering this item.
     * ------------------------------------------------------------------------------------------
     */

    public function getAlterSql(Table $destTable, $includeSchema = true)
    {
        $alterList = array();
        $triggerList = array();

        // Update names/docs to be clearer. We are migrating $this to $dest.

        // --------------------------------------------------------------------------------
        // Process columns

        $currentColNames = $this->getColumnNames();
        $destColNames = $destTable->getColumnNames();

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
            $hint = $destTable->getColumn($addName)->getHints();
            if ( null !== $hint
                 && isset($hint->rename_from)
                 && false !== ( $hintIndex = array_search($hint->rename_from, $dropColNames) ) )
            {
                $renameColNames[$hint->rename_from] = $addName;
                unset($addColNames[$index]);
                unset($dropColNames[$hintIndex]);
            }
        }  // foreach ( $addColNames as $addName )

        if ( $this->engine != $destTable->getEngine() ) {
            $alterList[] = "ENGINE = " . $destTable->getEngine();
        }

        if ( $this->comment != $destTable->getComment() ) {
            $alterList[] = "COMMENT = '" . addslashes($destTable->getComment()) . "'";
        }

        foreach ( $addColNames as $name ) {
            $alterList[] = "ADD COLUMN " . $destTable->getColumn($name)->getCreateSql($includeSchema);
        }

        foreach ( $dropColNames as $name ) {
            $alterList[] = "DROP COLUMN " . $this->quote($name);
        }

        foreach ( $changeColNames as $name ) {
            $destColumn = $destTable->getColumn($name);
            // Not all properties are required so a simple object comparison isn't possible
            if ( 0 == $destColumn->compare($this->getColumn($name)) ) {
                continue;
            }
            $alterList[] = "CHANGE COLUMN " . $destColumn->getName(true) . " " . $destColumn->getAlterSql($includeSchema);
        }

        foreach ( $renameColNames as $fromColumnName => $toColumnName ) {
            $destColumn = $destTable->getColumn($toColumnName);
            $currentColumn = $this->getColumn($fromColumnName);
            // Not all properties are required so a simple object comparison isn't possible
            if ( 0 == $destColumn->compare($currentColumn) ) {
                continue;
            }
            $alterList[] = "CHANGE COLUMN " . $currentColumn->getName(true) . " " . $destColumn->getAlterSql($includeSchema);
        }

        // --------------------------------------------------------------------------------
        // Processes indexes

        $currentIndexNames = $this->getIndexNames();
        $destIndexNames = $destTable->getIndexNames();

        $dropIndexNames = array_diff($currentIndexNames, $destIndexNames);
        $addIndexNames = array_diff($destIndexNames, $currentIndexNames);
        $changeIndexNames = array_intersect($currentIndexNames, $destIndexNames);

        foreach ( $dropIndexNames as $name ) {
            $alterList[] = "DROP INDEX " . $this->quote($name);
        }

        foreach ( $addIndexNames as $name ) {
            $alterList[] = "ADD " . $destTable->getIndex($name)->getCreateSql($includeSchema);
        }

        // Altered indexes need to be dropped then added
        foreach ( $changeIndexNames as $name ) {
            $destIndex = $destTable->getIndex($name);
            // Not all properties are required so a simple object comparison isn't possible
            if ( 0 == $destIndex->compare($this->getIndex($name)) ) {
                continue;
            }
            $alterList[] = "DROP INDEX " . $destIndex->getName(true);
            $alterList[] = "ADD " . $destIndex->getCreateSql($includeSchema);
        }

        // --------------------------------------------------------------------------------
        // Process triggers

        // The table schema may have been set after the table was initially created. If the trigger
        // doesn't explicitly define a schema, default to the table's schema.
        // if ( null === $trigger->getSchema() ) $trigger->setSchema($this->getSchema());

        $currentTriggerNames = $this->getTriggerNames();
        $destTriggerNames = $destTable->getTriggerNames();

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
            $destTrigger = $destTable->getTrigger($name);
            if ( 0 == $destTrigger->compare($this->getTrigger($name))) {
                continue;
            }

            $triggerList[] = "DROP TRIGGER " .
                ( null !== $this->schema && $includeSchema ? $this->quote($this->schema) . "." : "" ) .
                $this->quote($name) . ";";
            $triggerList[] = $destTable->getTrigger($name)->getCreateSql($includeSchema);
        }

        foreach ( $addTriggerNames as $name ) {
            $triggerList[] = $destTable->getTrigger($name)->getCreateSql($includeSchema);
        }

        // --------------------------------------------------------------------------------
        // Put it all together

        if ( 0 == count($alterList) && 0 == count($triggerList) ) {
            return false;
        }

        $tableName = ( $includeSchema ? $this->getFullName() : $this->getName() );

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
     * Generate an object representation of this item suitable for encoding into JSON.
     *
     * @param $includeSchema true to include the schema in the table definition
     *
     * @return An object representation for this item suitable for encoding into JSON.
     * ------------------------------------------------------------------------------------------
     */

    public function toJsonObj($succinct = false, $includeSchema = false)
    {
        $data = new stdClass;
        $data->name = $this->name;
        if ( null !== $this->schema && $includeSchema ) {
            $data->schema = $this->schema;
        }
        if ( null !== $this->engine ) {
            $data->engine = $this->engine;
        }
        if ( null !== $this->comment && "" != $this->comment ) {
            $data->comment = $this->comment;
        }

        $columns = array();
        foreach ( $this->columns as $column ) {
            $columns[] = $column->toJsonObj($succinct);
        }
        $data->columns = $columns;

        $indexes = array();
        foreach ( $this->indexes as $index ) {
            $indexes[] = $index->toJsonObj($succinct);
        }
        $data->indexes = $indexes;

        $triggers = array();
        foreach ( $this->triggers as $trigger ) {
            $triggers[] = $trigger->toJsonObj($succinct);
        }
        $data->triggers = $triggers;

        return $data;

    }  // toJsonObj()

    /* ------------------------------------------------------------------------------------------
     * Generate a JSON representation of this table.
     *
     * @param $succinct true if a succinct representation should be returned.
     * @param $includeSchema true to include the schema in the table definition
     *
     * @return A JSON formatted string representing the tabe.
     * ------------------------------------------------------------------------------------------
     */

    public function toJson($succinct = false, $includeSchema = false)
    {
        return json_encode($this->toJsonObj($succinct, $includeSchema));
    }  // toJson()
}  // class Table
