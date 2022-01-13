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
 * - ForeignKeyConstraint
 * - Trigger
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-28
 * ==========================================================================================
 */

namespace ETL\DbModel;

use CCR\Log;
use ETL\DataEndpoint\iRdbmsEndpoint;
use Psr\Log\LoggerInterface;
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

        // Optional table default character set
        'charset'  => null,

        // Optional table collation
        'collation' => null,

        // Associative array where the keys are column names and the values are Column objects
        'columns'  => array(),

        // Associative array where the keys are index names and the values are Index objects
        'indexes'  => array(),

        // Associative array where the keys are foreign key constraint names and the values are ForeignKeyConstraint objects
        'foreign_key_constraints'  => array(),

        // Associative array where the keys are trigger names and the values are Trigger objects
        'triggers' => array(),
    );

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($config, $systemQuoteChar = null, LoggerInterface $logger = null)
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
            case 'foreign_key_constraints':
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
            case 'charset':
            case 'collation':
                if ( ! is_string($value) ) {
                    $this->logAndThrowException(
                        sprintf("%s name must be a string, '%s' given", $property, gettype($value))
                    );
                }
                // Normalize property values to lowercase to match MySQL behavior
                if ( 'comment' != $property ) {
                    $value = strtolower($value);
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


        if ( count($this->foreign_key_constraints) > 0 && $this->engine !== 'innodb' ) {
            $this->logAndThrowException('Foreign key constraints are only supported by InnoDB');
        }

        // Verify foreign key constraint columns match table columns and are
        // contained in the beginning of an index.

        foreach ( $this->foreign_key_constraints as $constraint ) {
            $missingColumnNames = array_diff($constraint->columns, $columnNames);
            if ( 0 != count($missingColumnNames) ) {
                $this->logAndThrowException(
                    sprintf("Columns in foreign key constraint '%s' not found in table definition: %s", $constraint->name, implode(", ", $missingColumnNames))
                );
            }

            $foundCorrespondingIndex = false;
            foreach ( $this->indexes as $index ) {
                // Skip any index with fewer columns than the constraint.
                if ( count($constraint->columns) > count($index->columns) ) {
                    continue;
                }
                // Compare columns starting at the beginning of the index.
                foreach ( $constraint->columns as $i => $column ) {
                    if ( $column != $index->columns[$i] ) {
                        // Index doesn't match, check next index.
                        continue 2;
                    }
                }
                $foundCorrespondingIndex = true;
                break;
            }
            if ( ! $foundCorrespondingIndex ) {
                $this->logAndThrowException(
                    sprintf("Columns in foreign key constraint '%s' must be contained at the beginning of an index", $constraint->name)
                );
            }
        }  // foreach ( $this->foreign_key_constraints as $constraint )

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
t.engine,
ccsa.character_set_name as charset,
t.table_collation as collation,
t.table_comment as comment
FROM information_schema.tables t
JOIN information_schema.collation_character_set_applicability ccsa ON t.table_collation = ccsa.collation_name
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
        $this->charset = $row['charset'];
        $this->collation = $row['collation'];
        $this->comment = $row['comment'];

        // Query columns. Querying for the default needs some explaining. The information schema stores
        // the default as null unless one was specifically provided so we need some logic to get things
        // into the shape we want.

        // SMG: We should do a better job of detecting equivalent columns. For example "int unsigned" is
        // equivalent to "int(10) unsigned".

        // NOTE: An additional `IF` statement was added to the `COLUMN_DEFAULT` clause as MariaDB 10.2+ started
        // reporting default values differently than previous MySQL / MariaDB versions.
        // Related links:
        //   - https://jira.mariadb.org/browse/MDEV-15377
        //   - https://mariadb.com/kb/en/incompatibilities-and-feature-differences-between-mariadb-102-and-mysql-57/
        //       - "Since MariaDB supports expressions in the DEFAULT clause, in MariaDB, the INFORMATION_SCHEMA.COLUMNS
        //         table contains extra fields, and also quotes the DEFAULT value of a string in the COLUMN_DEFAULT
        //         field in order to distinguish it from an expression.
        $sql = "
SELECT column_name                                   AS name,
       column_type                                   AS type,
       is_nullable                                   AS nullable,
       character_set_name                            AS charset,
       collation_name                                AS collation,
       IF(
           INSTR(COLUMN_DEFAULT, '\''),
           SUBSTR(column_default, 2, LENGTH(COLUMN_DEFAULT) - 2),
           IF(COLUMN_DEFAULT = 'NULL',
               NULL,
               COLUMN_DEFAULT)
           ) as 'default',
       IF('' = extra, NULL, extra)                   AS extra,
       IF('' = column_comment, NULL, column_comment) AS 'comment'
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
GROUP BY index_name,
         index_type,
         is_unique
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

        // Query foreign key constraints.

        $sql = <<<SQL
SELECT
    tc.constraint_name AS name,
    GROUP_CONCAT(kcu.column_name ORDER BY position_in_unique_constraint ASC) AS columns,
    kcu.referenced_table_schema AS referenced_schema,
    kcu.referenced_table_name AS referenced_table,
    GROUP_CONCAT(kcu.referenced_column_name ORDER BY position_in_unique_constraint ASC) AS referenced_columns,
    rc.update_rule AS on_update,
    rc.delete_rule AS on_delete
FROM information_schema.table_constraints tc
INNER JOIN information_schema.key_column_usage kcu
    ON tc.table_schema = kcu.table_schema
    AND tc.table_name = kcu.table_name
    AND tc.constraint_schema = kcu.constraint_schema
    AND tc.constraint_name = kcu.constraint_name
INNER JOIN information_schema.referential_constraints rc
    ON tc.constraint_schema = rc.constraint_schema
    AND tc.constraint_name = rc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY'
    AND tc.table_schema = :schema
    AND tc.table_name = :tablename
GROUP BY tc.constraint_name,
         kcu.referenced_table_schema,
         kcu.referenced_table_name,
         rc.update_rule,
         rc.delete_rule
ORDER BY tc.constraint_name ASC
SQL;

        try {
            $result = $endpoint->getHandle()->query($sql, $params);
        } catch (Exception $e) {
            $this->logAndThrowException("Error discovering table '$qualifiedTableName' foreign key constraints: " . $e->getMessage());
        }

        foreach ( $result as $row ) {
            $row['columns'] = explode(',', $row['columns']);
            $row['referenced_columns'] = explode(',', $row['referenced_columns']);
            $this->addForeignKeyConstraint((object) $row);
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
                array('log_level' => Log::WARNING)
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
     * Add a foreign key constraint to this table.
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

    public function addForeignKeyConstraint($config, $overwriteDuplicates = false)
    {
        $item = ( is_object($config) && $config instanceof ForeignKeyConstraint
                  ? $config
                  : new ForeignKeyConstraint($config, $this->systemQuoteChar, $this->logger) );

        if ( array_key_exists($item->name, $this->foreign_key_constraints) && ! $overwriteDuplicates ) {
            $this->logAndThrowException(
                sprintf("Cannot add duplicate foreign key constraint '%s'", $item->name)
            );
        }

        $this->properties['foreign_key_constraints'][$item->name] = $item;

        return $this;

    }  // addForeignKeyConstraint()

    /* ------------------------------------------------------------------------------------------
     * Get the list of foreign key constraint names.
     *
     * @return array An array of column names.
     * ------------------------------------------------------------------------------------------
     */

    public function getForeignKeyConstraintNames()
    {
        return array_keys($this->foreign_key_constraints);
    }  // getForeignKeyConstraintNames()

    /* ------------------------------------------------------------------------------------------
     * Get an ForeignKeyConstraint object with the specified name.
     *
     * @param $name The name of the foreign key constraint to retrieve.
     *
     * @return The ForeignKeyConstraint object with the specified name or FALSE if the trigger does not exist
     * ------------------------------------------------------------------------------------------
     */

    public function getForeignKeyConstraint($name)
    {
        return ( array_key_exists($name, $this->foreign_key_constraints) ? $this->properties['foreign_key_constraints'][$name] : false );
    }  // getForeignKeyConstraint()

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

        $foreignKeyConstraintCreateList = array();
        foreach ( $this->foreign_key_constraints as $name => $constraint ) {
            $foreignKeyConstraintCreateList[$name] = $constraint->getSql($includeSchema);
        }

        $triggerCreateList = array();
        foreach ( $this->triggers as $name => $trigger ) {
            $triggerCreateList[$name] = $trigger->getSql($includeSchema);
        }

        $tableName = ( $includeSchema ? $this->getFullName() : $this->getName(true) );

        $sqlList = array();
        $sqlList[] = "CREATE TABLE IF NOT EXISTS $tableName (\n" .
            "  " . implode(",\n  ", $columnCreateList) .
            ( 0 != count($indexCreateList) ? ",\n  " . implode(",\n  ", $indexCreateList) : "" ) .
            ( 0 != count($foreignKeyConstraintCreateList) ? ",\n  " . implode(",\n  ", $foreignKeyConstraintCreateList) : "" ) .
            "\n" . ")" .
            ( null !== $this->engine ? " ENGINE = " . $this->engine : "" ) .
            ( null !== $this->charset ? " CHARSET = " . $this->charset : "" ) .
            ( null !== $this->collation ? " COLLATE = " . $this->collation : "" ) .
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

        // Track modifications to be made
        $alterList = array();
        $changeList = array();
        $triggerList = array();
        $foreignKeyList = array();

        // --------------------------------------------------------------------------------
        // Process columns

        // Note that MySQL can have a race condition where we want to add a new column using an
        // AFTER clause that will come after a column that is also being renamed (and possibly
        // re-ordered) in the same ALTER TABLE statement. In this case MySQL throw a 1054 "unknown
        // column" error. We could have the same issue if we rename and move a column to be after a
        // column that has not yet been added.  To address this, we handle the CHANGE COLUMN clauses
        // in their own ALTER TABLE statements.  For example, given:
        //
        // CREATE TABLE IF NOT EXISTS `test`.`modify_table_test` (
        //  `id` int(11) NOT NULL,
        //  `available` int(11) NULL,
        //  `requested` int(11) NULL,
        //  `awarded` int(11) NULL
        // )
        //
        // This statement will produce a 1054 "unknown column" error
        //
        // ALTER TABLE `test`.`modify_table_test`
        // ADD COLUMN `new_column` int(11) NULL AFTER `resource_id`,
        // CHANGE COLUMN `id` `resource_id` int(11) NOT NULL AFTER `awarded`;
        //
        // But these statements will succeed
        //
        // ALTER TABLE `test`.`modify_table_test` ADD COLUMN `new_column` int(11) NULL AFTER `id`;
        // ALTER TABLE `test`.`modify_table_test` CHANGE COLUMN `id` `resource_id` int(11) NOT NULL AFTER `awarded`;

        $currentColNames = $this->getColumnNames();
        $destColNames = $destination->getColumnNames();

        // Columns to be dropped, added, or renamed
        $dropColNames = array_diff($currentColNames, $destColNames);
        $addColNames = array_diff($destColNames, $currentColNames);
        $renameColNames = array();

        // Find the column names that are the same between the current and destination tables,
        // ordering the array based on the columns in the current table (1st argument).
        $changeColNamesCurrentOrder = array_intersect($currentColNames, $destColNames);

        // Find the column names that are the same between the current and destination tables,
        // ordering the array based on the columns in the destination table (1st argument).
        $changeColNamesDestinationOrder = array_intersect($destColNames, $changeColNamesCurrentOrder);

        // Determine which columns have been re-ordered by comparing the order between the current
        // and destination tables.  Buy using array_values() we reset the array element indexes so
        // array_diff will show us columns that changed order between the current and destination
        // tables.
        $reorderedColNames = array_diff_assoc(
            array_values($changeColNamesDestinationOrder),
            array_values($changeColNamesCurrentOrder)
        );

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
        }

        if ( $this->engine != $destination->engine ) {
            $alterList[] = sprintf("ENGINE = %s", $destination->engine);
        }

        if ( null !== $destination->charset && $this->charset != $destination->charset ) {
            $alterList[] = sprintf("CHARSET = %s", $destination->charset);
        }

        if ( null !== $destination->collation && $this->collation != $destination->collation ) {
            $alterList[] = sprintf("COLLATE = %s", $destination->collation);
        }

        if ( $this->comment != $destination->comment ) {
            $alterList[] = sprintf("COMMENT = '%s'", addslashes($destination->comment));
        }

        foreach ( $addColNames as $index => $name ) {

            // When adding columns, maintain the same order as in the definition array. Note that
            // array_diff() maintains the array index so we are able to look up the previous column.

            $position = "FIRST";
            if ( $index > 0 ) {
                $afterColName = $destColNames[$index-1];
                // If this column will be added after a column that is being renamed, use the
                // original name rather than the new name.
                if ( in_array($afterColName, $renameColNames) ) {
                    $afterColName = array_search($afterColName, $renameColNames);
                }
                $position = "AFTER " . $destination->quote($afterColName);
            }

            $alterList[] = sprintf(
                "ADD COLUMN %s %s",
                $destination->getColumn($name)->getSql($includeSchema),
                $position
            );
        }

        foreach ( $dropColNames as $name ) {
            $alterList[] = sprintf("DROP COLUMN %s", $this->quote($name));
            $this->logger->warning(sprintf("Dropping column %s!", $this->quote($name)));
        }

        // We must use the columns in order based on the destination table or MySQL will complain
        // about unknown columns when changing the order.

        foreach ( $changeColNamesDestinationOrder as $name ) {

            // We use the destination column object to properly apply system quote characters
            $destColumn = $destination->getColumn($name);
            $compareCode = $destColumn->compare($this->getColumn($name));

            if ( 0 == $compareCode && ! in_array($name, $reorderedColNames) ) {
                continue;
            }

            $position = "";

            if ( in_array($name, $reorderedColNames) ) {
                $index = array_search($name, $destColNames);
                $position = "FIRST";
                if ( $index > 0 ) {
                    $position = "AFTER " . $destination->quote($destColNames[$index-1]);
                }
            }

            $changeList[] = sprintf(
                "CHANGE COLUMN %s %s %s",
                $destination->quote($name),
                $destColumn->getSql($includeSchema),
                $position
            );
        }

        foreach ( $renameColNames as $fromColumnName => $toColumnName ) {
            $destColumn = $destination->getColumn($toColumnName);
            $currentColumn = $this->getColumn($fromColumnName);
            $index = array_search($toColumnName, $destColNames);

            $position = "FIRST";
            if ( $index > 0 ) {
                $position = "AFTER " . $destination->quote($destColNames[$index-1]);
            }

            $changeList[] = sprintf(
                "CHANGE COLUMN %s %s %s",
                $destination->quote($fromColumnName),
                $destColumn->getSql($includeSchema),
                $position
            );
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
        // Processes foreign key constraints

        $currentForeignKeyConstraintNames = $this->getForeignKeyConstraintNames();
        $destForeignKeyConstraintNames = $destination->getForeignKeyConstraintNames();

        $dropForeignKeyConstraintNames = array_diff($currentForeignKeyConstraintNames, $destForeignKeyConstraintNames);
        $addForeignKeyConstraintNames = array_diff($destForeignKeyConstraintNames, $currentForeignKeyConstraintNames);
        $changeForeignKeyConstraintNames = array_intersect($currentForeignKeyConstraintNames, $destForeignKeyConstraintNames);

        foreach ( $dropForeignKeyConstraintNames as $name ) {
            $alterList[] = 'DROP FOREIGN KEY ' . $this->quote($name);
        }

        foreach ( $addForeignKeyConstraintNames as $name ) {
            $alterList[] = 'ADD ' . $destination->getForeignKeyConstraint($name)->getSql($includeSchema);
        }

        // Altered foreign key constraints need to be dropped then added
        foreach ( $changeForeignKeyConstraintNames as $name ) {
            $destForeignKeyConstraint = $destination->getForeignKeyConstraint($name);
            // Not all properties are required so a simple object comparison isn't possible
            if ( 0 == $destForeignKeyConstraint->compare($this->getForeignKeyConstraint($name)) ) {
                continue;
            }
            $foreignKeyList[] = 'DROP FOREIGN KEY ' . $destForeignKeyConstraint->getName(true);
            $foreignKeyList[] = 'ADD ' . $destForeignKeyConstraint->getSql($includeSchema);
        }

        // --------------------------------------------------------------------------------
        // Process triggers

        $currentTriggerNames = $this->getTriggerNames();
        $destTriggerNames = $destination->getTriggerNames();

        $dropTriggerNames = array_diff($currentTriggerNames, $destTriggerNames);
        $addTriggerNames = array_diff($destTriggerNames, $currentTriggerNames);
        $changeTriggerNames = array_intersect($currentTriggerNames, $destTriggerNames);

        // Drop triggers first, then alter, then create

        foreach ( $dropTriggerNames as $name ) {
            $schema = $this->getTrigger($name)->schema;
            $triggerList[] = "DROP TRIGGER " .
                ( null !== $schema && $includeSchema ? $this->quote($schema) . "." : "" ) .
                $this->quote($name) . ";";
        }

        foreach ( $changeTriggerNames as $name ) {
            $destTrigger = $destination->getTrigger($name);
            if ( 0 == $destTrigger->compare($this->getTrigger($name))) {
                continue;
            }

            $schema = $this->getTrigger($name)->schema;
            $triggerList[] = "DROP TRIGGER " .
                ( null !== $schema && $includeSchema ? $this->quote($schema) . "." : "" ) .
                $this->quote($name) . ";";
            $triggerList[] = $destination->getTrigger($name)->getSql($includeSchema);
        }

        foreach ( $addTriggerNames as $name ) {
            $triggerList[] = $destination->getTrigger($name)->getSql($includeSchema);
        }

        // --------------------------------------------------------------------------------
        // Put it all together

        if ( 0 == count($alterList) && 0 == count($changeList) && 0 == count($triggerList) && 0 == count($foreignKeyList) ) {
            return false;
        }

        $tableName = ( $includeSchema ? $this->getFullName() : $this->getName(true) );

        $sqlList = array();
        if ( 0 != count($alterList) ) {
            $sqlList[] = sprintf("ALTER TABLE %s\n%s;", $tableName, implode(",\n", $alterList));
        }

        if ( 0 != count($changeList) ) {
            $sqlList[] = sprintf("ALTER TABLE %s\n%s;", $tableName, implode(",\n", $changeList));
        }

        foreach ($foreignKeyList as $fkey) {
            $sqlList[] = sprintf("ALTER TABLE %s\n%s;", $tableName, $fkey);
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
        $data->foreign_key_constraints = array_values((array) $data->foreign_key_constraints);
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
        $specialCaseProperties = array('columns', 'indexes', 'foreign_key_constraints', 'triggers', 'schema');

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

            case 'foreign_key_constraints':
                $this->properties[$property] = array();
                // Clear the array no matter what, that way NULL is handled properly.
                if ( null !== $value ) {
                    foreach ( $value as $item ) {
                        if ( is_object($item) && $item instanceof ForeignKeyConstraint ) {
                            $this->properties[$property][$item->name] = $item;
                        } else {
                            if ( $item instanceof stdClass ) {
                                // Default to the schema of the parent table.
                                if ( ! isset($item->schema) ) {
                                    $item->schema = $this->schema;
                                }
                            }
                            $constraint = new ForeignKeyConstraint($item, $this->systemQuoteChar, $this->logger);
                            $this->properties[$property][$constraint->name] = $constraint;
                        }
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

            case 'schema':
                // The schema is not required, but may be defined in both the
                // table definition and the destination endpoint.  This is fine
                // as long as they both specify the same schema.
                if (isset($this->properties[$property])
                    && $this->properties[$property] != $value) {
                    $this->logAndThrowException('Table schema may not be changed');
                }

                // Set schema for all SchemaEntity properties if not set.
                foreach ($this->foreign_key_constraints as $constraint) {
                    if ($constraint->schema === null) {
                        $constraint->schema = $value;
                    }
                }
                foreach ($this->triggers as $trigger) {
                    if ($trigger->schema === null) {
                        $trigger->schema = $value;
                    }
                }
                $this->properties[$property] = $value;
                break;

            default:
                break;
        }  // switch($property)

    }  // __set()
}  // class Table
