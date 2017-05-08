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

namespace ETL\DbModel;

use ETL\Utilities;
use Log;

class Query extends Entity implements iEntity
{

    protected $overseerRestrictionValues = array();

    /*
    // Records describing the fields used to populate the aggregation table
    protected $records = array();

    // A 2-element array containing the field names for start and end date/times. If present this
    // query support restricting the query to a particular date range.
    // protected $dateFields = null;

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
    */

    // Properties required by this class. These will be merged with other required
    // properties up the call chain. See @Entity::$requiredProperties
    private $localRequiredProperties = array(
        'records',
        'joins'
    );

    // Properties provided by this class. These will be merged with other properties up
    // the call chain. See @Entity::$properties
    private $localProperties = array(
        // Records describing the fields used to populate the aggregation table
        'records'  => array(),

        // Join tables. A single table generates the FROM clause while the rest are added as JOINS
        'joins' => array(),

        // Optional array of WHERE clauses
        'where'   => array(),

        // Optional array of GROUP BY clauses
        'groupby'   => array(),

        // Optional array of ORDER BY fields
        'orderby'   => array(),

        // Optional defined macros
        'macros'     => array(),

        // Query hints (See http://dev.mysql.com/doc/refman/5.7/en/query-cache-in-select.html)
        'query_hint' => null,

        // The list of ETL overseer restrictions supported by this query, as parsed from
        // the query definition. Queries are not required to support restrictions and if a
        // value for a restriction has not been set the restriction will not be
        // applied. The ${VALUE} macro will be replaced by the value provided by the
        // Overseer. For example:
        //
        // "source_query": {
        //     "overseer_restrictions": {
        //         "start_date": "jf.start_date >= ${VALUE}",
        //         "end_date": "jf.end_date <= ${VALUE}",
        //         "include_only_resource_codes": "jf.resource_id IN ${VALUE}",
        //         "exclude_resource_codes": "jf.resource_id NOT IN ${VALUE}"
        //      }
        'overseer_restrictions' => array()
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
            case 'records':
            case 'overseer_restrictions':
                if ( ! is_object($value) ) {
                    $this->logAndThrowException(
                        sprintf("%s name must be an object, '%s' given", $property, gettype($value))
                    );
                }
                break;

            case 'where':
            case 'groupby':
            case 'orderby':
            case 'macros':
            case 'joins':
                // Note that we are only checking that the value is an array here and not
                // the array elements. That must come later.

                if ( ! is_array($value) ) {
                    $this->logAndThrowException(
                        sprintf("%s name must be an array, '%s' given", $property, gettype($value))
                    );
                }
                break;

            case 'query_hint':
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
     * Verify the query. Check that any columns referenced in the query are present in the
     * destination table.
     *
     * @param Table $destinationTable The table that the query will be checked against.
     *
     * @see iEntity::verify()
     * ------------------------------------------------------------------------------------------
     */

    public function verify()
    {
        if ( 1 != func_num_args() ) {
            $this->logAndThrowException(
                sprintf('%s expected 1 argument, got %d', __FUNCTION__, func_num_args())
            );
        }

        $destinationTable = func_get_arg(0);

        if ( ! $destinationTable instanceof Table ) {
            $this->logAndThrowException(
                sprintf(
                    '%s expected object of type Table, got %s',
                    __FUNCTION__,
                    ( is_object($destinationTable) ? get_class($destinationTable) : gettype($destinationTable) )
                )
            );
        }

        $columnNames = $destinationTable->getColumnNames();
        $missingColumnNames = array_diff(array_keys($this->records), $columnNames);

        if ( 0 != count($missingColumnNames) ) {
            $this->logAndThrowException("Columns in records not found in table: " . implode(", ", $missingColumnNames));
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

    /*
    public function initialize(stdClass $config)
    {
        if ( $this->initialized && ! $force ) {
            return true;
        }

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

        return parent::initialize($config);

    }  // initialize()
    */

    /* ------------------------------------------------------------------------------------------
     * Add (or overwrite) a record to this query.  Records map column names to values in the SELECT statement.
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

        if ( empty($columnName) ) {
            $this->logAndThrowException("Empty column name");
        }

        if ( empty($formula) ) {
            $this->logAndThrowException(sprintf("Empty formula for column '%s'", $columnName));
        }

        $this->properties['records'][$columnName] = $formula;

        return $this;

    }  // addRecord()

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
        return ( array_key_exists($columnName, $this->properties['records']) ? $this->properties['records'][$columnName] : false );
    }  // getRecord()

    /* ------------------------------------------------------------------------------------------
     * Remove a column record if it exists and return the formula.
     *
     * @param $columnName The column to remove.
     *
     * @return The formula for the specified column, or FALSE if none exists.
     * ------------------------------------------------------------------------------------------
     */

    public function removeRecord($columnName)
    {
        $record = $this->getRecord($columnName);
        if ( false !== $record ) {
            unset($this->properties['records'][$columnName]);
        }
        return $record;
    }  // removeRecord()


    /* ------------------------------------------------------------------------------------------
     * Add a join clause for this query.
     *
     * @param $definition  An object containing the column definition, or an instantiated Join
     *  object to add
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function addJoin($config)
    {
        $item = ( is_object($config) && $config instanceof Join
                  ? $config
                  : new Join($config, $this->systemQuoteChar, $this->logger) );

        $this->properties['joins'][] = $item;

        return $this;
    }  // addJoin()

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
            $this->logAndThrowException("Overseer restriction key must be a non-empty string");
        } elseif ( ! is_string($template) || "" == $template ) {
            $this->logAndThrowException("Overseer restriction template must be a non-empty string");
        }

        // $this->overseerRestrictions[$restriction] = $template;
        $this->properties['overseer_restrictions'][$restriction] = $template;
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
        return $this->properties['overseer_restrictions'];
    }  // getOverseerRestrictions()

    /* ------------------------------------------------------------------------------------------
     * Remove all restrictions. Note that this does not remove them from the where clause if they
     * have already been added.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    /*
    public function deleteOverseerRestrictions()
    {
        $this->properties['overseer_restrictions'] = array();
        $this->overseerRestrictionValues = array();
        return $this;
    }  // deleteOverseerRestrictions()
    */

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
            $this->logAndThrowException("Overseer restriction key must be a non-empty string");
        } elseif ( ! is_string($value) || "" == $value ) {
            $this->logAndThrowException("Overseer restriction template must be a non-empty string");
        }

        // $this->overseerRestrictionValues[$restriction] = $value;
        $this->properties['overseer_restrictions'][$restriction] = $value;
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

    public function getSql($includeSchema = true)
    {
        if ( 0 == count($this->joins) ) {
            $this->logAndThrowException("At least one join is required");
        }

        // Use the records to generate the SELECT columns

        $columnList = array();
        $thisObj = $this;
        foreach ( $this->records as $columnName => $formula ) {

            // Do not quote field names because we may have functions in the query. -smg
            $columnList[] = "$formula AS $columnName";
        }

        // Use the first join as the main FROM table, followined by other joins.

        $myJoins = $this->joins;

        $joinList = array();
        $joinList[] = "FROM " . $myJoins[0]->getSql($includeSchema);

        for ($i = 1; $i < count($myJoins); $i++) {
            if ( null === $myJoins[$i]->on ) {
                $this->logger->debug(
                    sprintf("Join clause for table '%s' does not provide ON condition", $myJoins[$i]->name)
                );
            }

            // When we move to explictly marking the FROM clause this functionality may be moved
            // into the Join class

            $joinType = $myJoins[$i]->type;

            // Handle various join types. STRAIGHT_JOIN is a mysql enhancement.

            $joinStr = "JOIN";

            if ( "STRAIGHT" == $joinType ) {
                $joinStr = "STRAIGHT_JOIN";
            } elseif (null !== $joinType) {
                $joinStr = $joinType . " JOIN";
            }

            $joinList[] = $joinStr . " " . $myJoins[$i]->getSql($includeSchema);
        }  // for ( $i = 1; $i < count($this->joins); $i++ )

        // Construct the SELECT statement

        // Merge in where clauses along with any overseer restrictions provided
        $whereConditions = array_merge($this->where, $this->overseerRestrictionValues);

        $sql = "SELECT" .( null !== $this->query_hint ? " " . $this->query_hint : "" ) . "\n" .
            implode(",\n", $columnList) . "\n" .
            implode("\n", $joinList) . "\n" .
            ( count($whereConditions) > 0 ? "WHERE " . implode("\nAND ", $whereConditions) . "\n" : "" ) .
            ( count($this->groupby) > 0 ? "GROUP BY " . implode(", ", $this->groupby) : "" ) .
            ( count($this->orderby) > 0 ? "ORDER BY " . implode(", ", $this->orderby) : "" );

        // If any macros have been defined, process those macros now. Since macros can contain variables
        // themselves, we will process the variables later.

        if (count($this->macros) > 0) {
            foreach ( $this->macros as $macro ) {
                $sql = Utilities::processMacro($sql, $macro);
            }
        }

        return $sql;

    }  // getSql()

    /* ------------------------------------------------------------------------------------------
     * iEntity::toStdClass()
     * ------------------------------------------------------------------------------------------
     */

    public function toStdClass()
    {
        $data = parent::toStdClass();

        // Overwrite arrays that are expected to be objects. If overseer_restrictions is
        // an empty array Entity::_toStdClass() won't know that it should be an object.

        if ( is_array($data->overseer_restrictions) ) {
            $data->overseer_restrictions = (object) $data->overseer_restrictions;
        }

        return $data;

    }  // toStdClasS()

    /* ------------------------------------------------------------------------------------------
     * @see Entity::__set()
     * ------------------------------------------------------------------------------------------
     */

    public function __set($property, $value)
    {
        // If we are not setting a property that is a special case, just call the main setter

        $specialCaseProperties = array('joins', 'records', 'overseer_restrictions');

        if ( ! in_array($property, $specialCaseProperties) ) {
            parent::__set($property, $value);
            return;
        }

        // Verify values prior to doing anything with them so we can make assumptions later.

        $value = $this->filterAndVerifyValue($property, $value);

        // Handle special cases.

        switch ($property) {
            case 'joins':
                // Clear the array no matter what, that way NULL is handled properly.
                $this->properties[$property] = array();
                if ( null !== $value ) {
                    foreach ( $value as $item ) {
                        $this->properties[$property][] =
                            ( is_object($item) && $item instanceof Join
                              ? $item
                              : new Join($item, $this->systemQuoteChar, $this->logger) );
                    }
                }
                break;

            case 'records':
                // Clear the array no matter what, that way NULL is handled properly.
                $this->properties[$property] = array();
                if ( null !== $value ) {
                    foreach ( $value as $column => $formula ) {
                        // Provide a method for adding and verifying more complex information
                        $this->addRecord($column, $formula);
                    }
                }
                break;

            case 'overseer_restrictions':
                // Clear the array no matter what, that way NULL is handled properly.
                $this->properties[$property] = array();
                $this->overseerRestrictionValues = array();
                if ( null !== $value ) {
                    foreach ( $value as $restriction => $template ) {
                        $this->addOverseerRestriction($restriction, $template);
                    }
                }
                break;

            default:
                break;
        }  // switch($property)

    }  // __set()
}  // class Query
