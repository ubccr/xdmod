<?php
/* ==========================================================================================
 * Class for generating a query on a datasource. The following information used to generate a SELECT
 * statement:
 *
 * - A "records" key that defines the values for the table columns. NOTE: The formula is not
 *   included as an optional part of the column definition because this allows us the flexibility to
 *   define a column and not populate it during the aggration process.  Also, if records were
 *   specified in the column definition because we wouldn't be able to use the succinct version of
 *   the definition.
 * - A "groupby" key that defines an array of GROUP BY columns
 * - A "joins" key that defins an array of the tables in the FROM statement. At least one item is
 *    required
 * - An optional "where" key to restrict the results
 * - An optional "macros" key that allows us to define macros that can be reused when generating the
 *    table select statement
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-11-20
 * ==========================================================================================
 */

namespace ETL\DbEntity;

use ETL\Utilities;
use \Log;
use \stdClass;

class Query extends aNamedEntity
{
    // The list of ETL overseer restrictions supported by this query, as parsed from the query
    // definition. Queries are not required to support restrictions and if a value for a restriction
    // has not been set the restriction will not be applied. The ${VALUE} macro will be replaced by
    // the value provided by the Overseer. For example:
    //
    // "source_query": {
    //     "overseer_restrictions": {
    //         "start_date": "jf.start_date >= ${VALUE}",
    //         "end_date": "jf.end_date <= ${VALUE}",
    //         "include_only_resource_codes": "jf.resource_id IN ${VALUE}",
    //         "exclude_resource_codes": "jf.resource_id NOT IN ${VALUE}"
    //      }
    protected $overseerRestrictions = array();

    // Optional array of WHERE clauses corresponding to restrictions provided by the ETL
    // Overseer. These are intentionally kept separate from other query where clauses so we can
    // operate on them independently.
    protected $overseerRestrictionValues = array();

    // Records describing the fields used to populate the aggregation table
    protected $records = array();

    // A 2-element array containing the field names for start and end date/times. If present this
    // query support restricting the query to a particular date range.
    protected $dateFields = null;

    // Group by fields
    protected $groupBys = array();

    // Join tables. A single table generates the FROM clause while the rest are added as JOINS
    protected $joins = array();

    // Optional array of WHERE clauses
    protected $where = array();

    // Optional array of ORDER BY fields
    protected $orderBys = array();

    // Optional defined macros
    protected $macros = array();

    // Query hints (See http://dev.mysql.com/doc/refman/5.7/en/query-cache-in-select.html)
    protected $queryHint = null;

    /* ------------------------------------------------------------------------------------------
     * Construct a table object from a JSON definition file or a definition object. The definition
     * must contain, at a minimum, name and columns properties.
     *
     *  @param $config Mixed Either a filename for the JSON definition file or an object containing the
     *   table definition
     *
     * Optional 2nd and 3rd arguments:
     *
     * @param $variableMap An associative array specifying variables that will be substituted in the
     *   table DDL and the aggregation SELECT statement
     * @param $macroDir The directory where macro files are found.
     *
     * @throw Exception If the argument is not a string or instance of stdClass
     * @throw Exception If the table definition was incomplete
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($config, $systemQuoteChar = null, Log $logger = null)
    {
        parent::__construct($systemQuoteChar, $logger);

        if ( ! is_object($config) && is_string($config) ) {
            $config = $this->parseJsonFile($config, "Query Definition");
        } elseif ( ! $config instanceof stdClass) {
            $msg = __CLASS__ . ": Argument is not a filename or object";
            $this->logAndThrowException($msg);
        }

        // Support the query config directly or assigned to a "source_query" key

        if ( isset($config->source_query) ) {
            $config = $config->source_query;
        }

        // Check for required properties

        $requiredKeys = array("records", "joins");
        $this->verifyRequiredConfigKeys($requiredKeys, $config);

        $this->initialize($config);

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Verify the table. This includes ensuring any index colums match column names.
     *
     * @param $destinationTable The table that data from this query will be placed into.

     * @return true on success
     * @throws Exception If there are errors during validation
     * ------------------------------------------------------------------------------------------
     */

    public function verify(Table $destinationTable)
    {
        $columnNames = $destinationTable->getColumnNames();
        $missingColumnNames = array_diff(array_keys($this->records), $columnNames);

        if ( 0 != count($missingColumnNames) ) {
            $msg = "Columns in records not found in table: " . implode(", ", $missingColumnNames);
            $this->logAndThrowException($msg);
        }

        return true;

    }  // verify()

    /* ------------------------------------------------------------------------------------------
     * Initialize internal data structures.
     *
     * @throws Exception if any query data was not
     * int the correct format.
     * ------------------------------------------------------------------------------------------
     */

    protected function initialize(stdClass $config, $force = false)
    {
        if ( $this->initialized && ! $force ) {
            return true;
        }

        // Check for required properties (records and join)

        $this->initialized = false;
        $errorMsg = array();

        if ( ! isset($config->records) ) {
            $errorMsg[] = "records property not found";
        } elseif ( ! is_object($config->records) ) {
            $errorMsg[] = "records property must be an object";
        }

        if ( ! isset($config->joins) ) {
            $errorMsg[] = "joins property not found";
        } elseif ( ! is_array($config->joins) ) {
            $errorMsg[] = "joins property must be an array";
        } elseif ( 0 == count($config->joins) ) {
            $errorMsg[] = "joins property must include as least one element";
        }

        if ( isset($config->groupby) ) {
            if ( ! is_array($config->groupby) ) {
                $errorMsg[] = "groupby property must be an array";
            } elseif ( 0 == count($config->groupby) ) {
                $errorMsg[] = "groupby property must include as least one element";
            }
        }

        if ( isset($config->orderby) ) {
            if ( ! is_array($config->orderby) ) {
                $errorMsg[] = "orderby property must be an array";
            } elseif ( 0 == count($config->orderby) ) {
                $errorMsg[] = "orderby property must include as least one element";
            }
        }

        if ( isset($config->where) && ! is_array($config->where) ) {
            $errorMsg[] = "where property must be an array";
        }

        if ( isset($config->macros) && ! is_array($config->macros) ) {
            $errorMsg[] = "macros property must be an array";
        }

        if ( isset($config->query_hint) && ! is_string($config->query_hint) ) {
            $msg = "Query hints must be a string";
            $this->logAndThrowException($msg);
        }

        if ( isset($config->overseer_restrictions) && ! is_object($config->overseer_restrictions) ) {
            $msg = "ETL overseer restrictions must be an object";
            $this->logger->logAndThrowException($msg);
        }

        if ( 0 != count($errorMsg) ) {
            $msg = "Error in query definition (" . implode(", ", $errorMsg) . ")";
            $this->logAndThrowException($msg);
        }

        // Set records. Each formula must match an existing column.

        foreach ( $config->records as $column => $formula ) {
            $this->addRecord($column, $formula);
        }

        // Set joins. A single join is required but more may be included

        foreach ( $config->joins as $definition ) {
            $this->addJoin($definition);
        }

        if ( isset($config->groupby) ) {
            foreach ( $config->groupby as $groupby ) {
                $this->addGroupBy($groupby);
            }
        }

        // Set optional where clauses and macros

        if ( isset($config->where) ) {
            foreach ( $config->where as $where ) {
                $this->addWhere($where);
            }
        }

        if ( isset($config->orderby) ) {
            foreach ( $config->orderby as $orderby ) {
                $this->addOrderBy($orderby);
            }
        }

        if ( isset($config->macros) ) {
            foreach ( $config->macros as $macro ) {
                $this->addMacro($macro);
            }
        }

        if ( isset($config->query_hint) ) {
            $this->setHint($config->query_hint);
        }

        if ( isset($config->overseer_restrictions) ) {
            foreach ( $config->overseer_restrictions as $restriction => $template ) {
                $this->addOverseerRestriction($restriction, $template);
            }
        }

        $this->initialized = true;

        return true;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * Add a record to this query.  Records map column names to values in the SELECT statement.
     *
     * @param $columnName The column that the formula will be associated with.
     * @param $formula The formula associated with the column.
     *
     * @return This object to support method chaining.
     *
     * @throw Exception If the column name does not exist.
     * @throw Exception If the formula is empty.
     * @throw Exception If the column name has already been specified.
     * ------------------------------------------------------------------------------------------
     */

    public function addRecord($columnName, $formula)
    {
        // Note in PHP "" and "0" are both equal to 0 due to conversion comparing strings to integers.
        if ( null === $formula || "" === $formula ) {
            $msg = "Empty formula for column '$columnName' '$formula'";
            $this->logAndThrowException($msg);
        } elseif ( array_key_exists($columnName, $this->records) ) {
            $msg = "Column '$columnName' already has a formula specified";
            $this->logAndThrowException($msg);
        }

        $this->records[$columnName] = $formula;

        return $this;

    }  // addRecord()

    /* ------------------------------------------------------------------------------------------
     * Get the list of records.
     *
     * @return An associative array where the keys are column names and the values are records
     *  for those columns.
     * ------------------------------------------------------------------------------------------
     */

    public function getRecords()
    {
        return $this->records;
    }  // getRecords()

    /* ------------------------------------------------------------------------------------------
     * Get a formula for the specified column.
     *
     * @param $columnName The column to retrieve.
     *
     * @return The formula for the specified column, or false if none exists.
     * ------------------------------------------------------------------------------------------
     */

    public function getRecord($columnName)
    {
        return ( array_key_exists($columnName, $this->records) ? $this->records[$columnName] : false );
    }  // getRecord()

    /* ------------------------------------------------------------------------------------------
     * Remove all records from this query.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function deleteRecords()
    {
        $this->records = array();
        return $this;
    }  // deleteRecords()

    /* ------------------------------------------------------------------------------------------
     * Remove a column record if it exists and return the formula.
     *
     * @param $columnName The column to remove.
     *
     * @return The formula for the specified column, or false if none exists.
     * ------------------------------------------------------------------------------------------
     */

    public function removeRecord($columnName)
    {
        $record = $this->getRecord($columnName);
        if ( false !== $record ) {
            unset($this->records[$columnName]);
        }
        return $record;
    }  // removeRecord()

    /* ------------------------------------------------------------------------------------------
     * Add a group by clause to this query.
     *
     * @param $groupBy An array containing the group by column names.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function addGroupBy($groupBy)
    {
        if ( empty($groupBy) || ! is_string($groupBy) ) {
            $msg = "Cannot add an empty group by";
            $this->logAndThrowException($msg);
        }

        $this->groupBys[] = $groupBy;
        return $this;
    }  // addGroupBys()

    /* ------------------------------------------------------------------------------------------
     * Get the list of group by columns.
     *
     * @return An array of group by column names.
     * ------------------------------------------------------------------------------------------
     */

    public function getGroupBys()
    {
        return $this->groupBys;
    }  // getGroupBys()

    /* ------------------------------------------------------------------------------------------
     * Remove all group bys from this query.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function deleteGroupBys()
    {
        $this->groupBys = array();
        return $this;
    }  // deleteGroupBys()

    /* ------------------------------------------------------------------------------------------
     * Add a order by clause to this query.
     *
     * @param $orderBy An array containing the group by column names.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function addOrderBy($orderBy)
    {
        if ( empty($orderBy) || ! is_string($orderBy) ) {
            $msg = "Cannot add an empty group by";
            $this->logAndThrowException($msg);
        }

        $this->orderBys[] = $orderBy;
        return $this;
    }  // addOrderBys()

    /* ------------------------------------------------------------------------------------------
     * Get the list of order by columns.
     *
     * @return An array of group by column names.
     * ------------------------------------------------------------------------------------------
     */

    public function getOrderBys()
    {
        return $this->orderBys;
    }  // getOrderBys()

    /* ------------------------------------------------------------------------------------------
     * Remove all order bys from this query.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function deleteOrderBys()
    {
        $this->orderBys = array();
        return $this;
    }  // deleteOrderBys()

    /* ------------------------------------------------------------------------------------------
     * Add a join clause for this query.
     *
     * @param $definition  An object containing the column definition, or an instantiated Join
     *  object to add
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function addJoin($definition)
    {
        $item = ( $definition instanceof Join ? $definition : new Join($definition, $this->systemQuoteChar) );

        if ( ! ($item instanceof iTableItem) ) {
            $msg = "Join does not implement interface iTableItem";
            $this->logAndThrowException($msg);
        }

        $this->joins[] = $item;

        return $this;
    }  // setJoins()

    /* ------------------------------------------------------------------------------------------
     * Get the list of join clauses.
     *
     * @return An array of join clauses
     * ------------------------------------------------------------------------------------------
     */

    public function getJoins()
    {
        return $this->joins;
    }  // getJoins()

    /* ------------------------------------------------------------------------------------------
     * Remove all joins from this query.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function deleteJoins()
    {
        $this->joins = array();
        return $this;
    }  // deleteJoins()

    /* ------------------------------------------------------------------------------------------
     * Add a where clause for this query, appending to any existing where clauses.
     *
     * @param $where An string containing a single where clause.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function addWhere($where)
    {
        if ( empty($where) || ! is_string($where) ) {
            $msg = "WHERE clause is empty or not a string '$where'";
            $this->logAndThrowException($msg);
        }

        $this->where[] = $where;
        return $this;

    }  // addWhere()

    /* ------------------------------------------------------------------------------------------
     * Get the list of optional where clauses.
     *
     * @return An array of where clauses.
     * ------------------------------------------------------------------------------------------
     */

    public function getWheres()
    {
        return $this->where;
    }  // getWheres()

    /* ------------------------------------------------------------------------------------------
     * Remove all wheres from this query.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function deleteWheres()
    {
        $this->wheres = array();
        return $this;
    }  // deleteWheres()

    /* ------------------------------------------------------------------------------------------
     * Add a macro for this query, appending to any existing macros.
     *
     * @param $macro An object containing a single macro definition
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function addMacro(stdClass $macro)
    {
        $this->macros[] = $macro;
        return $this;
    }  // addMacro()

    /* ------------------------------------------------------------------------------------------
     * Get the list of optional macros.
     *
     * @return An array of macros.
     * ------------------------------------------------------------------------------------------
     */

    public function getMacros()
    {
        return $this->macros;
    }  // getMacros()

    /* ------------------------------------------------------------------------------------------
     * Remove all macros from this query.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function deleteMacros()
    {
        $this->macros = array();
        return $this;
    }  // deleteMacros()

    /* ------------------------------------------------------------------------------------------
     * Set a query hint string for the optimizer
     *
     * @param $hint The hint string
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setHint($hint)
    {
        $this->queryHint = $hint;
        return $this;
    }  // setHint()

    /* ------------------------------------------------------------------------------------------
     * Get the query hints
     *
     * @return The query hint string
     * ------------------------------------------------------------------------------------------
     */

    public function getHint()
    {
        return $this->queryHint;
    }  // getHint()

    /* ------------------------------------------------------------------------------------------
     * Remove all hints from this query.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function deleteHint()
    {
        $this->queryHint = null;
        return $this;
    }  // deleteHint()

    /* ------------------------------------------------------------------------------------------
     * Add an overseer restriction template to this query based on the parsed query definition.
     * Note that at this point we don't know if the restrictions are valid (i.e., supported by the
     * EtlOverseer).
     *
     * @param $restrictions The name of the restriction
     * @param $template A template for the restriction where ${VALUE} will be replaced by the value
     *
     * @throws Exception if the restriction or the template are invalid
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function addOverseerRestriction($restriction, $template)
    {
        if ( ! is_string($restriction) || "" == $restriction ) {
            $msg = "Overseer restriction key must be a non-empty string";
            $this->logAndThrowException($msg);
        } elseif ( ! is_string($template) || "" == $template ) {
            $msg = "Overseer restriction template must be a non-empty string";
            $this->logAndThrowException($msg);
        }

        $this->overseerRestrictions[$restriction] = $template;
        return $this;

    }  // addOverseerRestriction()

    /* ------------------------------------------------------------------------------------------
     * Get the list of configured overseer restrictions.
     *
     * @return An associative array where the keys are restriction names and the values are the
     *  templates for those restrictions.
     * ------------------------------------------------------------------------------------------
     */

    public function getOverseerRestrictions()
    {
        return $this->overseerRestrictions;
    }  // getOverseerRestrictions()

    /* ------------------------------------------------------------------------------------------
     * Remove all restrictions. Note that this does not remove them from the where clause if they
     * have already been added.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function deleteOverseerRestrictions()
    {
        $this->overseerRestrictions = array();
        $this->overseerRestrictionValues = array();
        return $this;
    }  // deleteOverseerRestrictions()

    /* ------------------------------------------------------------------------------------------
     * Add an overseer restriction value to this query. This is the template that has been processed
     * by the overseer. These values are kept separate from the other where clauses.
     *
     * @param $restrictions The name of the restriction
     * @param $value A processed overseer restriction template
     *
     * @throws Exception if the restriction or the value are invalid
     *
     * @return The value of the specified restriction, or FALSE if the name was not found.
     * ------------------------------------------------------------------------------------------
     */

    public function addOverseerRestrictionValue($restriction, $value)
    {
        if ( ! is_string($restriction) || "" == $restriction ) {
            $msg = "Overseer restriction key must be a non-empty string";
            $this->logAndThrowException($msg);
        } elseif ( ! is_string($value) || "" == $value ) {
            $msg = "Overseer restriction template must be a non-empty string";
            $this->logAndThrowException($msg);
        }

        $this->overseerRestrictionValues[$restriction] = $value;
        return $this;

    }  // addOverseerRestrictionValue()

    /* ------------------------------------------------------------------------------------------
     * Add an overseer restriction value to this query. This is the template that has been processed
     * by the overseer. These values are kept separate from the other where clauses.
     *
     * @param $restrictions The name of the restriction
     * @param $value A processed overseer restriction template
     *
     * @throws Exception if the restriction or the value are invalid
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function getOverseerRestrictionValues()
    {
        return $this->overseerRestrictionValues;
    }  // getOverseerRestrictionValues()

    /* ------------------------------------------------------------------------------------------
     * Generate a string containing the select statement described by the configuration.  This string
     * may contain macros that will need to be replaced before execution.
     *
     * @param $includeSchema true to include the schema in the item name, if appropriate.
     *
     * @return An array comtaining the SQL required for altering this item.
     * ------------------------------------------------------------------------------------------
     */

    public function getSelectSql($includeSchema = true)
    {
        if ( 0 == count($this->joins) ) {
            $msg = "At least one join is required";
            $this->logAndThrowException($msg);
        }

        // Use the records to generate the SELECT columns

        $columnList = array();
        $thisObj = $this;
        foreach ( $this->records as $columnName => $formula ) {
            if ( "#" == $columnName ) {
                continue;
            }

            /*
             * For now, do not quote field names because we may have functions in the query. -smg
             *
             * $formula = implode(".", array_map(function($part) use ($thisObj) { return $thisObj->quote($part); }, explode(".", $formula)));
             */
            $columnList[] = "$formula AS $columnName";
        }

        // Use the first join as the main FROM table, followined by other joins.

        $joinList = array();
        $joinList[] = "FROM " . $this->joins[0]->getCreateSql($includeSchema);

        for ( $i = 1; $i < count($this->joins); $i++ ) {
            if ( null === $this->joins[$i]->getOn() ) {
                $msg = "Join clause for table '" . $this->joins[$i]->getName() . "' does not provide ON condition";
            }

            // When we move to explictly marking the FROM clause this functionality may be moved
            // into the Join class

            $joinType = $this->joins[$i]->getType();

            // Handle various join types. STRAIGHT_JOIN is a mysql enhancement.

            $joinStr = "JOIN";

            if ( "STRAIGHT" == $joinType ) {
                $joinStr = "STRAIGHT_JOIN";
            } elseif (null !== $joinType) {
                $joinStr = $joinType . " JOIN";
            }

            $joinList[] = $joinStr . " " . $this->joins[$i]->getCreateSql($includeSchema);
        }  // for ( $i = 1; $i < count($this->joins); $i++ )

        // Construct the SELECT statement

        // Merge in where clauses along with any overseer restrictions provided
        $whereConditions = array_merge($this->where, $this->overseerRestrictionValues);

        $sql = "SELECT" .( null !== $this->queryHint ? " " . $this->queryHint : "" ) . "\n" .
            implode(",\n", $columnList) . "\n" .
            implode("\n", $joinList) . "\n" .
            ( count($whereConditions) > 0 ? "WHERE " . implode("\nAND ", $whereConditions) . "\n" : "" ) .
            ( count($this->groupBys) > 0 ? "GROUP BY " . implode(", ", $this->groupBys) : "" ) .
            ( count($this->orderBys) > 0 ? "ORDER BY " . implode(", ", $this->orderBys) : "" );

        // If any macros have been defined, process those macros now. Since macros can contain variables
        // themselves, we will process the variables later.

        if (count($this->macros) > 0) {
            foreach ( $this->macros as $macro ) {
                $sql = Utilities::processMacro($sql, $macro);
            }
        }

        return $sql;

    }  // getSelectSql()

    /* ------------------------------------------------------------------------------------------
     * Generate an object representation of this item suitable for encoding into JSON.
     *
     * @param $succinct true to use a succinct representation.
     * @param $includeSchema true to include the schema in the table definition
     *
     * @return An object representation for this item suitable for encoding into JSON.
     * ------------------------------------------------------------------------------------------
     */

    public function toJsonObj($succinct = false, $includeSchema = false)
    {
        $data = new stdClass;
        $data->records = $this->records;
        $data->joins = $this->joins;

        if ( count($this->groupBys) > 0 ) {
            $data->groupbys = $this->groupBys;
        }
        if ( count($this->orderBys) > 0 ) {
            $data->orderbys = $this->orderBys;
        }
        if ( count($this->where) > 0 ) {
            $data->where = $this->where;
        }
        if ( count($this->macros) > 0 ) {
            $data->macros = $this->macros;
        }

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
}  // class Query
