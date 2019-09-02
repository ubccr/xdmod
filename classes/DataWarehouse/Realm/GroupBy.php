<?php
/**
 * XDMoD implements descriptive attributes using GroupBy classes.
 * @see iGroupBy
 */

namespace Realm;

use Log as Logger;  // PEAR logger
use ETL\DbModel\Query as DbQuery;
use \DataWarehouse\Query\Model\OrderBy;
use \DataWarehouse\Query\Model\Parameter;
use \DataWarehouse\Query\Model\Schema;
use \DataWarehouse\Query\Model\Field;
use \DataWarehouse\Query\Model\Table;
use \DataWarehouse\Query\Model\TableField;
use \DataWarehouse\Query\Model\WhereCondition;

class GroupBy extends \CCR\Loggable implements iGroupBy
{
    /**
     * @var Delimiter used to separate individual column values for attributes with multi-column
     *   keys.
     */

    const FILTER_DELIMITER = '^';

    /**
     * @var Realm The realm that this GroupBy belongs to.
     */

    protected $realm = null;

    /**
     * @var string The name of the module that defined this group by
     */

    protected $moduleName = null;

    /**
     * @var string The short identifier.
     */

    protected $id = null;

    /**
     * @var string The display name.
     */

    protected $name = null;

    /**
     * @var string Human-readable description supporting basic HTML formatting.
     */

    protected $description = null;

    /**
     * @var string Schema for the attribute table.
     */

    protected $attributeTableSchema = null;

    /**
     * @var string Name of the table where attributes are stored.
     */

    protected $attributeTableName = null;

    /**
     * @var \DataWarehouse\Query\Model\Table Attribute table object.
     */

    protected $attributeTableObj = null;

    /**
     * @var array Two dimensional array describing the mapping between attribute table keys and the
     *   corresponding aggregate table keys. Keys are the attribute table columns and values are the
     *   aggregate table columns used for constructing join clauses. Multi-key columns are supported
     *   by using multiple ordered key-value pairs.
     */

    protected $attributeToAggregateKeyMap = null;

    /**
     * @var DbQuery The Attribute Values Query provides a mechanism for the GroupBy class to
     *   enumerate a list of the current values for the dimensional attribute that it describes. This
     *   is constructed from the same JSON configuration object used by the ETL.
     */

    protected $attributeValuesQuery = null;

    /**
     * @var stdClass The Attribute Values Query represented as a stdClass.
     */

    protected $attributeValuesQueryAsStdClass = null;

    /**
     * @var array An associative array describing how to apply attribute filters to the aggregate
     *   data using a subquery in cases where there is not a direct mapping. The keys are aggregate
     *   table colum keys and must be present as values in the attribute to aggregate key map while
     *   the values are subqueries to be used to filter results.
     */

    protected $attributeFilterMapSqlList = null;

    /**
     * @var int A numerical ordering hint as to how this realm should be displayed visually relative
     *   to other realms. Lower numbers are displayed first. If no order is specified, 0 is assumed.
     */

    protected $order = 0;

    /**
     * @var string Optional query to retrieve human-readable names for any attribute value filters
     *   that have been specified in a request. This should only be used when the query to retrieve
     *   the human-readable names of the specified filter values cannot be derived from the possible
     *   values query. The _filtervalues_ marker will be replaced by the values of the filters.
     */

    protected $attributeDescriptionSql = null;

    /**
     * @var string Category used to group attributes, used in the Job Viewer filters.
     */

    protected $category = 'uncategorized';

    /**
     * @var boolean Indicates that this dimension is available for drill-down in the user interface.
     */

    protected $availableForDrilldown = true;

    /**
     * @var int PHP order specificaiton to determine how the query should sort results containing
     *   this GroupBy.
     * @see http://php.net/manual/en/function.array-multisort.php
     */

    protected $sortOrder = SORT_DESC;

    /**
     * @var boolean Set to true if this group by should not be utilized.
     */

    protected $disabled = false;

    /**
     * @see iRealm::factory()
     */

    public static function factory($shortName, \stdClass $config, Realm $realm, Logger $logger = null)
    {
        return new static($shortName, $config, $realm, $logger);
    }

    /**
     * This constructor is meant to be called by factory() but it cannot be made private because we
     * extend Loggable, which has a public constructor.
     *
     * @param string $shortName The short name for this statistic
     * @param stdClass $config An object contaning the configuration specificaiton.
     * @param Realm $realm Realm object that this GroupBy will belong to.
     * @param Log|null $logger A Log instance that will be utilized during processing.
     */

    public function __construct($shortName, \stdClass $config, Realm $realm, Logger $logger = null)
    {
        parent::__construct($logger);

        // The __toString() method needs these to be set and logAndThrowException() calls
        // __toString() so assign these at the top.

        $this->id = $shortName;
        $this->attributeTableSchema = $realm->getAggregateTableSchema(); // Default to Realm schema
        $this->moduleName = $realm->getModuleName();
        $this->realm = $realm;

        if ( empty($shortName) ) {
            $this->logger->logAndThrowException('GroupBy short name not provided');
        } elseif ( ! is_string($shortName) ) {
            $this->logger->logAndThrowException(
                sprintf('GroupBy short name must be a string, %s provided: %s', $shortName, gettype($shortName))
            );
        } elseif ( null === $config ) {
            $this->logger->logAndThrowException('No GroupBy configuration provided');
        }

        // Verify the types of the various configuration options

        $messages = array();
        $configTypes = array(
            'attribute_table' => 'string',
            'attribute_to_aggregate_table_key_map' => 'array',
            'attribute_values_query' => 'object',
            'description_html' => 'string',
            'name' => 'string'
        );

        if ( ! \xd_utilities\verify_object_property_types($config, $configTypes, $messages) ) {
            $this->logAndThrowException(
                sprintf('Error verifying GroupBy configuration: %s', implode(', ', $messages))
            );
        }

        $optionalConfigTypes = array(
            'attribute_description_query' => 'int',
            'attribute_filter_map_query' => 'object',
            'attribute_table_schema' => 'string',
            'available_for_drilldown' => 'bool',
            'category' => 'string',
            'data_sort_order' => 'string',
            'disabled' => 'bool',
            'module' => 'string',
            'order' => 'int'
        );

        if ( ! \xd_utilities\verify_object_property_types($config, $optionalConfigTypes, $messages, true) ) {
            $this->logAndThrowException(
                sprintf('Error verifying GroupBy configuration: %s', implode(', ', $messages))
            );
        }

        foreach ( $config as $key => $value ) {
            switch ($key) {
                case 'attribute_table':
                    $this->attributeTableName = trim($value);
                    break;
                case 'attribute_values_query':
                    $this->attributeValuesQuery = new DbQuery($value, '`', $logger);
                    $this->attributeValuesQueryAsStdClass = $this->attributeValuesQuery->toStdClass();
                    break;
                case 'attribute_filter_map_query':
                    $this->attributeFilterMapSqlList = (array) $value;
                    break;
                case 'attribute_table_schema':
                    $this->attributeTableSchema = trim($value);
                    break;
                case 'attribute_to_aggregate_table_key_map':
                    // Convert an ordered array of objects into an ordered associative array.
                    $this->attributeToAggregateKeyMap = array();
                    foreach ( $value as $obj ) {
                        foreach ( $obj as $k => $v ) {
                            $this->attributeToAggregateKeyMap[$k] = $v;
                        }
                    }
                    break;
                case 'attribute_description_query':
                    $this->attributeDescriptionSql = trim($value);
                    break;
                case 'available_for_drilldown':
                    $this->availableForDrilldown = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'category':
                    $this->category = trim($value);
                    break;
                case 'data_sort_order':
                    // The sort order is specified in the JSON config file as the string
                    // representation of a PHP constant so convert it to an integer in order to
                    // properly use it. See https://php.net/manual/en/function.array-multisort.php
                    $sortOrder = null;
                    eval('$sortOrder = $value');
                    $this->setSortOrder($sortOrder);
                    break;
                case 'description_html':
                    $this->description = trim($value);
                    break;
                case 'disabled':
                    $this->disabled = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'module':
                    $this->moduleName = trim($value);
                    break;
                case 'name':
                    $this->name = trim($value);
                    break;
                case 'order':
                    $this->order = filter_var($value, FILTER_VALIDATE_INT);
                    break;
                default:
                    $this->logger->notice(
                        sprintf("Unknown key in definition for realm '%s' group by '%s': '%s'", $this->realm->getName(), $this->id, $key)
                    );
                    break;
            }
        }

        // Ensure the attribute values query has the required columns as we will rely on these later
        // on.

        if (
            false === $this->attributeValuesQuery->getRecord('id') ||
            false === $this->attributeValuesQuery->getRecord('short_name') ||
            false === $this->attributeValuesQuery->getRecord('name') // For historical reasons, the long name is simply "name"
        ) {
            $this->logAndThrowException('The attribute_values_query key must specify id, short_name, and long_name columns');
        }

        // Note that we are using the table name itself as an alias. If needed, we can add an
        // alias to the group by configuration specification.

        $this->attributeTableObj = new Table(
            new Schema($this->attributeTableSchema),
            $this->attributeTableName,
            $this->attributeTableName
        );

        $this->logger->debug(sprintf('Created %s', $this));
    }

    /**
     * @see iGroupBy::getRealm()
     */

    public function getRealm()
    {
        return $this->realm;
    }

    /**
     * Note: Was getName()
     *
     * @see iGroupBy::getId()
     */

    public function getId()
    {
        return $this->id;
    }

    /**
     * Note: Was getLabel()
     *
     * @see iGroupBy::getName()
     */

    public function getName()
    {
        return $this->name;
    }

    /**
     * Note: Was getInfo()
     *
     * @see iGroupBy::getHtmlDescription()
     */

    public function getHtmlDescription()
    {
        return $this->description;
    }

    /**
     * Note: Was getDescription()
     *
     * @see iGroupBy::getHtmlNameAndDescription()
     */

    public function getHtmlNameAndDescription()
    {
        return sprintf("<b>%s</b>: %s", $this->name, $this->description);
    }

    /**
     * @see iGroupBy::getAttributeTable()
     */

    public function getAttributeTable($includeSchema = true)
    {
        return ( $includeSchema ? sprintf("%s.", $this->attributeTableSchema) : "" ) . $this->attributeTableName;
    }

    /**
     * @see iGroupBy::getAttributeKeys()
     */

    public function getAttributeKeys()
    {
        return array_keys($this->attributeToAggregateKeyMap);
    }

    /**
     * @see iGroupBy::getAggregateKeys()
     */

    public function getAggregateKeys()
    {
        return array_values($this->attributeToAggregateKeyMap);
    }

    /**
     * @see iGroupBy::getModuleName()
     */

    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * @see iGroupBy::getOrder()
     */

    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @see iGroupBy::setSortOrder()
     */

    public function setSortOrder($sortOrder = SORT_DESC)
    {
        $validSortOrders = array(
            SORT_ASC,
            SORT_DESC,
            SORT_REGULAR,
            SORT_NUMERIC,
            SORT_STRING,
            SORT_LOCALE_STRING,
            SORT_NATURAL
        );

        if ( null !== $sortOrder && ! in_array($sortOrder, $validSortOrders) ) {
            $this->logAndThrowException(sprintf("Invalid sort option: %d", $sortOrder));
        }

        $this->sortOrder = $sortOrder;
    }

    /**
     * @see iGroupBy::getSortOrder()
     */

    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @see iGroupBy::isAvailableForDrilldown()
     */

    public function isAvailableForDrilldown()
    {
        return $this->availableForDrilldown;
    }

    /*
     * DEVNOTE: Is there really any reason why we should not always treat these methods as $multi =
     * true and prefix them by the group by id? It would likely make the query more readable.
     */

    /**
     * Qualify the given column name by prefixing it with the id of this group by so it is unique
     * when used in a Query.
     *
     * @param string $name The name of the column to prefix
     *
     * @return string The qualified column name
     */

    protected function qualifyColumnName($name, $multi = false)
    {
        return ( true === $multi ? sprintf('%s_%s', $this->id, $name) : $name );
    }

    /**
     * Filters in a request are identified in 2 ways:
     * 1) By looking for a parameter with the short name of this group by and creating a filter with
     *    its (singular) value.
     * 2) By looking for a parameter with the short name appended with "_filter" and creating a
     *    filter with the (possibly multiple) values.
     *
     * @param array $request The HTTP request
     *
     * @return array A list of zero or more filters for this group by identified in the request.
     */

    protected function pullFilterValuesFromRequest(array $request)
    {
        $filterList = array();

        // Request parameters with "_filter" appended to the short name of this group by may include
        // one or more values to filter on.

        $filterKey = sprintf('%s_filter', $this->id);
        if ( isset($request[$filterKey]) && '' != $request[$filterKey] ) {
            $filterList = explode(',', $request[$filterKey]);
        }

        // Request parameters with the same short name as this group by may contain single values to
        // filter on.

        if ( isset($request[$this->id]) && '' != $request[$this->id] ) {
            $filterList[] = $request[$this->id];
        }

        return $filterList;
    }

    /**
     * @see iGroupBy::generateQueryFiltersFromRequest()
     * @see pullFilterValuesFromRequest()
     *
     * Was pullQueryParameters()
     */

    public function generateQueryFiltersFromRequest(array $request)
    {
        $filterList = array();
        $aggregateFilters = array();
        $requestFilters = $this->pullFilterValuesFromRequest($request);

        if ( 0 == count($requestFilters) ) {
            return $filterList;
        }

        // In order to support multi-column keys, each filter is a string encoding one or more
        // delimited values in the order specified in the attribute_to_aggregate_table_key_map
        // specified in the GroupBy definition. We must extract the individual values and create
        // query filters for each key column.

        // For each request filter, split it on the delimiter to expose the filter components
        // for each of the key columns and align the values with the approprite aggregate key based
        // on the order specified in the attribute_to_aggregate_table_key_map.

        foreach ( $requestFilters as $filterValues ) {
            $list = explode(self::FILTER_DELIMITER, $filterValues);
            foreach ( $this->attributeToAggregateKeyMap as $aggregateKey ) {
                $aggregateFilters[$aggregateKey][] = array_shift($list);
            }
        }

        // For each aggregate column filter, check to see if there is a mapping query to translate
        // between the filter value and aggregate value. If so, replace the __filter_values__
        // placeholder in the attribute filter map sub-query with the list of filters from the
        // request. If no attribute filter query was specified simply use the list of values.

        foreach ( $aggregateFilters as $aggregateKeyColumn => $filterValues ) {
            // The original code always enclosed values in quotes but this may not be desirable,
            // although it is unclear if we can differentiate between strings and numerics stored as
            // strings.
            $substitution = sprintf("'%s'", implode("','", $filterValues));
            if ( ! isset($this->attributeFilterMapSqlList[$aggregateKeyColumn]) ) {
                $fieldIdQuery = $substitution;
            } else {
                $fieldIdQuery = str_replace('__filter_values__', $substitution, $this->attributeFilterMapSqlList[$aggregateKeyColumn]);
            }

            // Should the aggregate key column include the aggregate table name?
            $filterParameter = new Parameter($aggregateKeyColumn, 'IN', "($fieldIdQuery)");
            $this->logger->debug(
                sprintf('%s Generate parameter from request: %s', $this, $filterParameter)
            );
            $filterList[] = $filterParameter;
        }

        return $filterList;
    }

    /**
     * @see iGroupBy::generateQueryParameterLabelsFromRequest()
     * Was pullQueryParameterDescriptions()
     */

    public function generateQueryParameterLabelsFromRequest(array $request)
    {
        $labelList = array();
        $attributeKeyFilters = array();
        $query = null;

        $requestFilters = $this->pullFilterValuesFromRequest($request);
        $filterCount = count($requestFilters);

        if ( 0 == $filterCount ) {
            return $labelList;
        }

        // For each filter specification, split it on the delimiter to expose the filter components
        // for each of the key columns and align the values with the approprite aggregate key based
        // on the order specified in the attribute_to_aggregate_table_key_map.

        foreach ( $requestFilters as $filterValues ) {
            $list = explode(self::FILTER_DELIMITER, $filterValues);
            foreach ( $this->attributeToAggregateKeyMap as $attributeKey => $aggregateKey ) {
                $attributeKeyFilters[$attributeKey][] = array_shift($list);
            }
        }

        // Construct the where conditions for each key column. We will add these to the query or
        // replace the placeholder in the attribute description query.

        $whereConditions = array();
        foreach ( $attributeKeyFilters as $attributeKey => $filterValues ) {
            $whereConditions[] = sprintf("%s IN ('%s')", $attributeKey, implode("','", $filterValues));
        }

        // If the attribute description query was not specified we will use the existing
        // attribute values query.

        if ( null === $this->attributeDescriptionSql ) {

            // Clone the attribute values query so we don't modify the original object and remove
            // all columns except for the long name and add WHERE conditions from the filters.

            // Use the attribute values query as a template so we don't modify the original object
            $queryConfig = $this->attributeValuesQuery->toStdClass();

            foreach ( $queryConfig->records as $column => $formula ) {
                if ( 'name' != $column ) {
                    unset($queryConfig->records->$column);
                }
            }

            if ( ! isset($queryConfig->where) || ! is_array($queryConfig->where) ) {
                $queryConfig->where = $whereConditions;
            } else {
                $queryConfig->where = array_merge($queryConfig->where, $whereConditions);
            }

            // Execute the attribute values query, selecting only the long_name column

            $query = new DbQuery($queryConfig, '`', $this->logger);
            $query = sprintf('SELECT name FROM (%s) avq', $query->getSql());
        } else {
            // Construct the where conditions for each key column and replace the placeholder in the
            // attribute description query.
            //
            // The original code always enclosed values in quotes but this may not be desirable,
            // although it is unclear if we can differentiate between strings and numerics stored as
            // strings.

            $whereConditions = array();
            foreach ( $attributeKeyFilters as $attributeKey => $filterValues ) {
                $whereConditions[] = sprintf('%s IN (%s)', $attributeKey, implode(',', $filterValues));
            }

            $query = str_replace(
                '__filter_values__',
                sprintf("'%s'", implode(' AND ', $whereConditions)),
                $this->attributeDescriptionSql
            );
        }

        // Return the results of the first column

        $this->logger->debug(
            sprintf("%s Fetch query parameter labels with query\n%s", $this, $query)
        );

        $stmt = \DataWarehouse::connect()->query($query, array(), true);
        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

        $labels = array();
        foreach ( $result as $fieldLabel ) {
            $labels[] = $fieldLabel;
        }

        // Generate the label string using a coma to separate labels. Would it be better to use a
        // semi-colon? Some labels that already contain comas such as "Last Name, First Name".

        $labelString = sprintf(
            ( $filterCount > 1 ? '( %s )' : '%s' ),
            implode(', ', $labels)
        );

        $labelList[] = sprintf('%s = %s', $this->name, $labelString);

        $this->logger->debug(
            sprintf('%s Generated query parameter labels %s', $this, implode('; ', $labelList))
        );

        return $labelList;
    }

    // are these called with all arguments?

    /**
     * Note: It is likely that we can remove the $multi_group parameter and always assume that
     * multiple group bys are being included by always prefixing the group by's attributes with the
     * group by id.
     *
     * @see iGroupBy::applyTo()
     */

    public function applyTo(\DataWarehouse\Query\iQuery $query, $multi_group = false)
    {
        // Apply this GroupBy to the given Query. Note tha the query may have multiple GroupBys and
        // other SELECT fields, but this essentially translates the following SQL:
        //
        // SELECT
        //   statistic_1
        // FROM aggregate_table
        //
        // to
        //
        // SELECT
        //   statistic_1,
        //   attribute_1_id, attribute_1_short_name, attribute_1_name, attribute_1_order_id
        // FROM aggregate_table
        // JOIN attribute_table
        // WHERE attribute_id = aggregate_id
        // GROUP BY attribute_id
        // ORDER BY attribute_order_id

        // JOIN with the attribute table in the query

        $this->logger->debug(sprintf('Apply GroupBy %s to %s', $this, $query));

        $query->addTable($this->attributeTableObj);

        foreach ( $this->attributeToAggregateKeyMap as $attributeKey => $aggregateKey ) {
            $alias = $this->qualifyColumnName($attributeKey, $multi_group);
            $field = new TableField($this->attributeTableObj, $attributeKey, $alias);
            $where = new WhereCondition($field, '=', new TableField($query->getDataTable(), $aggregateKey));
            $query->addField($field);
            $query->addWhereCondition($where);
            $query->addGroup($field);
            $this->logger->trace(sprintf("%s Add ID field '%s AS %s' and WHERE condition '%s'", $this, $field, $alias, $where));
        }

        // We use the short and long name fields specified in the attribute_values_query. Note that
        // these fields may contain their own local schema and table aliases so we must identify
        // these aliases and change them to the configured attribute table for this group by. Given
        // the example below, we must identify the "rf" alias, ensure that the table it aliases is
        // the same as the attribute table for this group by, and then replace the alias with the
        // table name.
        //
        // "attribute_values_query": {
        //     "records": {
        //         "id": "rf.id",
        //         "short_name": "rf.code",
        //         "name": "CONCAT(rf.name, '-', rf.code)"
        //     },
        //     "joins": [
        //         {
        //             "name": "resourcefact",
        //             "alias": "rf"
        //         }
        //     ]

        // Note that start_ts is used by SimpleTimeseriesDataset and must be present for aggregation
        // unit group bys such as GroupByDay, GroupByMonth, etc.

        $fieldList = array('short_name', 'name', 'order_id', 'start_ts');

        // If we find that there is an aliased column name (alias.column) in the formula, ensure
        // that the aliased table is the attribute table for this group by. Fully qualified
        // (schema.table.column) column names will be unchanged.

        foreach ($fieldList as $fieldName) {

            // Note that the formula specified as part of the attribute values query can be as
            // simple as the name of a table column (e.g., "name"), or it could contain a table
            // alias (e.g., "r.name"), or it could be a more complex formula (e.g., "CONCAT(rf.name,
            // '-', rf.code)").

            $formula = $this->attributeValuesQuery->getRecord($fieldName);
            if ( false === $formula ) {
                continue;
            }

            // TableField differs from Field in that it provides the table alias in addition to the
            // definition where Field provides only the definition. This makes TableField suitable
            // for simple values but not general SQL formulae where "CONCAT(name, '-', code)" would
            // become "table.CONCAT(name, '-', code)".  Here we use Field and include the table
            // alias ourselves.

            // Specifically for aggregation unit group bys, add the aggregation unit to the field
            // alias.

            $alias = (
                'start_ts' != $fieldName
                ? $this->qualifyColumnName($fieldName, $multi_group)
                : sprintf('%s_%s', $query->getAggregationUnit(), $fieldName)
            );

            $field = new Field($this->verifyAndReplaceTableAlias($formula), $alias);
            $this->logger->debug(sprintf("%s Add field: %s AS %s", $this, $field, $alias));
            $query->addField($field);
        }

        // Note that there are a few GroupBys that used $prepend = true such as GroupByJobTime,
        // GroupByJobWaitTime, and GroupByNodeCount. Are these really necessary? If so we will need
        // a way to specify TRUE here.

        $this->addOrder($query, $multi_group);
    }

    /**
     * The formulae provided by the attribute values query can be simple column names (column),
     * aliased column names (alias.column), a fully qualified column names (schema.table.column), or
     * an SQL formula (such as CONCAT(rf.name, '-'. rf.code)). Since the attribute values query is
     * self contained, the alias used may or may not match that of the attribute table specified for
     * this group by. If an alias is specified, ensure that the table it refers to matches the
     * attribute table for this group by and change the alias to the table name. If the formula did
     * not contain an alias, explicitly add the table name so we do not have ambiguous columns in
     * the query.
     *
     * @param string $formula The SQL column or formula
     *
     * @return string The formula with aliases changed to the group by table alias, or with an alias
     *   added if only a column was specified.
     *
     * @throws Exception if the formula had an aliased column name but the table that it referred to
     *   did not match the group by attribute table.
     */

    protected function verifyAndReplaceTableAlias($formula)
    {
        $matches = array();
         if ( 0 === preg_match_all('/([a-zA-Z0-9$_]+\.)?([a-zA-Z0-9$_]+\.[a-zA-Z0-9$_]+)/', $formula, $matches, PREG_SET_ORDER) ) {
             // The formula did not contain an aliased column name, assume that it is only a column
             // name and add our table alias.
             return sprintf("%s.%s", $this->attributeTableName, $formula);
         }

        foreach ( $matches as $match ) {
            if ( ! empty($match[1]) ) {
                // This is a fully qualified column name including schema, table, and column. (e.g.,
                // modw.resourcefact.code)
                continue;
            }

            // We have found a column name that includes an alias. Ensure that the table referenced
            // by the alias is the same as the attribute table for this group by.
            list($tableAlias, $column) = explode('.', $match[2]);
            foreach ( $this->attributeValuesQueryAsStdClass->joins as $joinObj ) {
                if ( isset($joinObj->alias) && $joinObj->alias == $tableAlias && $joinObj->name != $this->attributeTableName ) {
                    $this->logAndThrowException(
                        sprintf(
                            "Table for alias '%s' in attribute_value_query field '%s' refers to '%s' != group by attribute_table '%s'",
                            $tableAlias,
                            'short_name',
                            $joinObj->name,
                            $this->attributeTableName
                        )
                    );
                }
            }
            // Replace the table alias with the actual table name
            $formula = str_replace($match[2], sprintf("%s.%s", $this->attributeTableName, $column), $formula);
        }

        return $formula;
    }

    /**
     * @see iGroupBy::addWhereJoin()
     * @see Query::addWhereAndJoin()
     */

    public function addWhereJoin(\DataWarehouse\Query\iQuery $query, $aggregateTableName, $operation, $whereConstraint)
    {
        $attributeKeyConstraints = array();

        // JOIN with the attribute table in the query
        $query->addTable($this->attributeTableObj);

        // To support multi-column attribute keys, add JOIN conditions for each attribute and
        // aggregate column in the key map.

        foreach ( $this->attributeToAggregateKeyMap as $attributeKey => $aggregateKey ) {
            $attributeTableField = new TableField($this->attributeTableObj, $attributeKey);
            $aggregateTableField = new TableField($aggregateTableName, $aggregateKey);
            $where = new WhereCondition($attributeTableField, '=', $aggregateTableField);
            $this->logger->debug(sprintf('%s Add join condition: %s', $this, $where));
            $query->addWhereCondition($where);
        }

        // Normalize the WHERE constraint. We may be able to set this as an array in the parameter
        // list.

        $whereConstraint = ( ! is_array($whereConstraint) ? array($whereConstraint) : $whereConstraint );

        // Add the specified WHERE constraint. Note that to support multi-column keys the individual
        // values of the constraint may be encoded with a delimiter

        foreach ( $whereConstraint as $constraint ) {
            $list = explode(self::FILTER_DELIMITER, $constraint);
            foreach ( $this->attributeToAggregateKeyMap as $attributeKey => $aggregateKey ) {
                $attributeKeyConstraints[$attributeKey][] = array_shift($list);
            }
        }

        foreach ( $attributeKeyConstraints as $attributeKey => $valueList ) {
            $where = new WhereCondition(
                sprintf('%s.%s', $this->attributeTableName, $attributeKey),
                $operation,
                sprintf('(%s)', implode(',', $valueList))
            );
            $this->logger->debug(sprintf('%s Add where condition: %s', $this, $where));
            $query->addWhereCondition($where);
        }
    }

    /**
     * @see iGroupBy::addOrder()
     */

    public function addOrder(\DataWarehouse\Query\iQuery $query, $multi_group = false, $direction = 'ASC', $prepend = false)
    {
        // There can be zero or more order by fields specified in the attribute values query. Add an
        // order by clause for each of them.

        $queryConfig = $this->attributeValuesQueryAsStdClass;

        if ( ! isset($queryConfig->orderby) ) {
            return;
        }

        foreach ( $queryConfig->orderby as $orderByField ) {
            $orderBy = new OrderBy(
                new Field($this->verifyAndReplaceTableAlias($orderByField)),
                $direction,
                $this->id
            );

            if ( $prepend ) {
                $this->logger->debug(sprintf('%s Prepending order by to query: %s', $this, $orderBy));
                $query->prependOrder($orderBy);
            } else {
                $this->logger->debug(sprintf('%s Appending order by to query: %s', $this, $orderBy));
                $query->addOrder($orderBy);
            }
        }
    }

    /**
     * @see iGroupBy::getAttributeValues()
     */

    public function getAttributeValues(array $restrictions = null)
    {
        $whereConditions = array();
        $queryParameters = array();

        if ( isset($restrictions['id']) ) {
            $whereConditions[] = sprintf('(%s = :id)', $this->attributeValuesQuery->getRecord('id'));
            $queryParameters[':id'] = $restrictions['id'];
        }

        if ( isset($restrictions['name']) ) {
            $whereConditions[] = sprintf(
                '(%s LIKE :name OR %s LIKE :name)',
                $this->attributeValuesQuery->getRecord('short_name'),
                $this->attributeValuesQuery->getRecord('name')
            );
            $queryParameters[':name'] = $restrictions['name'];
        }

        // Use the attribute values query as a template so we don't modify the original object
        $queryConfig = $this->attributeValuesQuery->toStdClass();

        if ( ! isset($queryConfig->where) || ! is_array($queryConfig->where) ) {
            $queryConfig->where = $whereConditions;
        } else {
            $queryConfig->where = array_merge($queryConfig->where, $whereConditions);
        }

        $queryObj = new DbQuery($queryConfig, '`', $this->logger);
        $sql = $queryObj->getSql();

        $this->logger->debug(sprintf("%s: Fetch attribute values with query\n%s", $this, $sql));

        return \DataWarehouse::connect()->query($sql, $queryParameters);
    }

    /**
     * @see iGroupBy::getAttributeValuesQuery()
     */

    public function getAttributeValuesQuery()
    {
        return $this->attributeValuesQuery;
    }

    /**
     * Static accessors used to create usage chart settings in DataWarehouse/Access/Usage.php and
     * DataWarehouse/Query/GroupBy.php
     */

    /**
     * @see iGroupBy::getChartSettings()
     */

    public function getChartSettings($isMultiChartPage = false)
    {
        return json_encode(
            array(
                'dataset_type' => $this->getDefaultDatasetType(),
                'display_type' => $this->getDefaultDisplayType($this->getDefaultDatasetType()),
                'combine_type' => $this->getDefaultCombineMethod(),
                'limit' => $this->getDefaultLimit($isMultiChartPage),
                'offset' => $this->getDefaultOffset(),
                'log_scale' => $this->getDefaultLogScale(),
                'show_legend' => $this->getDefaultShowLegend(),
                'show_trend_line' => $this->getDefaultShowTrendLine(),
                'show_error_bars' => $this->getDefaultShowErrorBars(),
                'show_guide_lines' => $this->getDefaultShowGuideLines(),
                'show_aggregate_labels' => $this->getDefaultShowAggregateLabels(),
                'show_error_labels' => $this->getDefaultShowErrorLabels(),
                'enable_errors' => $this->getDefaultEnableErrors(),
                'enable_trend_line' => $this->getDefaultEnableTrendLine(),
            )
        );
    }

    /**
     * @see iGroupBy::getDefaultDatasetType()
     */

    public function getDefaultDatasetType()
    {
        return 'aggregate';
    }

    /**
     * @see iGroupBy::getDefaultDisplayType()
     */

    public function getDefaultDisplayType($dataset_type = null)
    {
        return ( 'aggregate' == $dataset_type ? 'h_bar' : 'line' );
    }

    /**
     * @see iGroupBy::getDefaultCombineMethod()
     */

    public function getDefaultCombineMethod()
    {
        return 'stack';
    }

    /**
     * @see iGroupBy::getDefaultShowLegend()
     */

    public function getDefaultShowLegend()
    {
        return 'y';
    }

    /**
     * @see iGroupBy::getDefaultLimit()
     */

    public function getDefaultLimit($isMultiChartPage = false)
    {
        return ( $isMultiChartPage ? 3 : 10 );
    }

    /**
     * @see iGroupBy::getDefaultOffset()
     */

    public function getDefaultOffset()
    {
        return 0;
    }

    /**
     * @see iGroupBy::getDefaultLogScale()
     */

    public function getDefaultLogScale()
    {
        return 'n';
    }

    /**
     * @see iGroupBy::getDefaultShowTrendLine()
     */

    public function getDefaultShowTrendLine()
    {
        return 'n';
    }

    /**
     * @see iGroupBy::getDefaultShowErrorBars()
     */

    public function getDefaultShowErrorBars()
    {
        return 'n';
    }

    /**
     * @see iGroupBy::getDefaultShowGuideLines()
     */

    public function getDefaultShowGuideLines()
    {
        return 'y';
    }

    /**
     * @see iGroupBy::getDefaultShowAggregateLabels()
     */

    public function getDefaultShowAggregateLabels()
    {
        return 'n';
    }

    /**
     * @see iGroupBy::getDefaultShowErrorLabels()
     */

    public function getDefaultShowErrorLabels()
    {
        return 'n';
    }

    /**
     * @see iGroupBy::getDefaultEnableErrors()
     */

    public function getDefaultEnableErrors()
    {
        return 'y';
    }

    /**
     * @see iGroupBy::getDefaultEnableTrendLine()
     */

    public function getDefaultEnableTrendLine()
    {
        return 'y';
    }

    /**
     * @see iGroupBy::getCategory()
     */

    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @see iGroupBy::__toString()
     */

    public function __toString()
    {
        return sprintf('Realm(%s)->GroupBy(id=%s, table=%s)', $this->realm->getId(), $this->id, $this->getAttributeTable());
    }
}
