<?php
/**
 * XDMoD implements descriptive attributes using GroupBy classes.
 * @see iGroupBy
 */

namespace Realm;

use Log as Logger;  // PEAR logger
use ETL\DbModel\Query;
use \DataWarehouse\Query\Model\OrderBy;
use \DataWarehouse\Query\Model\Parameter;
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

    protected $attributeTable = null;

    /**
     * @var array Two dimensional array describing the mapping between attribute table keys and the
     *   corresponding aggregate table keys. Keys are the attribute table columns and values are the
     *   aggregate table columns used for constructing join clauses. Multi-key columns are supported
     *   by using multiple ordered key-value pairs.
     */

    protected $attributeToAggregateKeyMap = null;

    /**
     * @var Query The Attribute Values Query provides a mechanism for the GroupBy class to
     *   enumerate a list of the current values for the dimensional attribute that it describes. This
     *   is constructed from the same JSON configuration object used by the ETL.
     */

    protected $attributeValuesQuery = null;

    /**
     * @var array An associative array describing how to apply attribute filters to the aggregate
     *   data using a subquery in cases where there is not a direct mapping. The keys are aggregate
     *   table colum keys and must be present as values in the attribute to aggregate key map while
     *   the values are subqueries to be used to filter results.
     */

    protected $attributeFilterMapQuery = null;

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

    protected $attributeDescriptionQuery = null;

    /**
     * @var string Category used to group attributes, used in the Job Viewer filters.
     */

    protected $category = 'uncategorized';

    /**
     * @var boolean Indicates that this dimension is available for drill-down in the user interface.
     */

    protected $availableForDrilldown = true;

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
        $this->attributeTableSchema = $realm->getAggregateTableSchema();
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
            'available_for_drilldown' => 'boolean',
            'category' => 'string',
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
                    $this->attributeTable = trim($value);
                    break;
                case 'attribute_values_query':
                    $this->attributeValuesQuery = new Query($value, '`', $logger);
                    break;
                case 'attribute_filter_map_query':
                    $this->attributeFilterMapQuery = (array) $value;
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
                    $this->attributeDescriptionQuery = trim($value);
                    break;
                case 'available_for_drilldown':
                    $this->availableForDrilldown = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'category':
                    $this->category = trim($value);
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
                    break;
            }
        }

        // Ensure the attribute values query has the required columns as we will rely on these later
        // on.

        if (
            false === $this->attributeValuesQuery->getRecord('id') ||
            false === $this->attributeValuesQuery->getRecord('short_name') ||
            false === $this->attributeValuesQuery->getRecord('long_name')
        ) {
            $this->logAndThrowException('The attribute_values_query must specify id, short_name, and long_name columns');
        }

        $this->logger->debug(sprintf('Created %s', $this));
    }

    /**
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
     * @see iGroupBy::getNameAndDescription()
     */

    public function getNameAndDescription()
    {
        return sprintf("<b>%s</b>: %s", $this->name, $this->description);
    }

    /**
     * @see iGroupBy::getAttributeTable()
     */

    public function getAttributeTable($includeSchema = true)
    {
        return $this->attributeTable;
    }

    /**
     * @see iGroupBy::getAttributeKeys()
     */

    public function getAttributeKeys()
    {
        return array_keys($this->attributeToAggregateKeyMap);
    }

    /**
     * @see iGroupBy::getAggregateTablePrefix()
     */

    public function getAggregateTablePrefix($includeSchema = true)
    {
        return $this->realm->getAggregateTablePrefix($includeSchema);
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

    protected function qualifyColumnName($name)
    {
        return sprintf('%s_%s', $this->id, $name);
    }

    /**
     * @see iGroupBy::getIdColumnName()
     */

    public function getIdColumnName($multi = false)
    {
        return ( true !== $multi ? 'id' : sprintf('%s_id', $this->id) );
    }

    /**
     * @see iGroupBy::getLongNameColumnName()
     */

    public function getLongNameColumnName($multi = false)
    {
        return ( true !== $multi ? 'name' : sprintf('%s_name', $this->id) );
    }

    /**
     * @see iGroupBy::getShortNameColumnName()
     */

    public function getShortNameColumnName($multi = false)
    {
        return ( true !== $multi ? 'short_name' : sprintf('%s_short_name', $this->id) );
    }

    /**
     * @see iGroupBy::getOrderIdColumnName()
     */

    public function getOrderIdColumnName($multi = false)
    {
        return ( true !== $multi ? 'order_id' : sprintf('%s_order_id', $this->id) );
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
            if ( ! isset($this->attributeFilterMapQuery[$aggregateKeyColumn]) ) {
                $fieldIdQuery = $substitution;
            } else {
                $fieldIdQuery = str_replace('__filter_values__', $substitution, $this->attributeFilterMapQuery[$aggregateKeyColumn]);
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
     * @see iGroupBy::generateQueryParameterLabels()
     * Was pullQueryParameterDescriptions()
     */

    public function generateQueryParameterLabels(array $request)
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

        if ( null === $this->attributeDescriptionQuery ) {

            // Clone the attribute values query so we don't modify the original object and remove
            // all columns except for the long name and add WHERE conditions from the filters.

            $queryConfig = $this->attributeValuesQuery->toStdClass();
            foreach ( $queryConfig->records as $column => $formula ) {
                if ( 'long_name' != $column ) {
                    unset($queryConfig->records->$column);
                }
            }

            if ( ! isset($queryConfig->where) || ! is_array($queryConfig->where) ) {
                $queryConfig->where = $whereConditions;
            } else {
                $queryConfig->where = array_merge($queryConfig->where, $whereConditions);
            }

            // Execute the attribute values query, selecting only the long_name column

            $query = new Query($queryConfig, '`', $this->logger);
            $query = sprintf('SELECT long_name FROM (%s) avq', $query->getSql());
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
                $this->attributeDescriptionQuery
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

    public function applyTo(DataWarehouse\Query $query, $multi_group = false)
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
        //   attribute_1_id, attribute_1_short_name, attribute_1_long_name, attribute_1_order_id
        // FROM aggregate_table
        // JOIN attribute_table
        // WHERE attribute_id = aggregate_id
        // GROUP BY attribute_id
        // ORDER BY attribute_order_id

        // JOIN with the attribute table in the query

        $this->logger->debug(sprintf('%s Apply GroupBy to query', $this));

        $query->addTable($this->attributeTable);

        foreach ( $this->attributeToAggregateKeyMap as $attributeKey => $aggregateKey ) {
            $field = new TableField($this->attributeTable, $attributeKey, $this->qualifyColumnName($attributeKey));
            $where = new WhereCondition($field, '=', new TableField($query->getDataTable(), $aggregateKey));
            $query->addField($field);
            $query->addWhereCondition($where);
            $query->addGroup($field);
            $this->logger->trace(sprintf('Add ID field %s and WHERE condition %s', $field, $where));
        }

        $formula = $this->attributeValuesQuery->getRecord('short_name');
        $query->addField(new TableField($this->attributeTable, $formula, $this->qualifyColumnName($formula)));

        $formula = $this->attributeValuesQuery->getRecord('long_name');
        $query->addField(new TableField($this->attributeTable, $formula, $this->qualifyColumnName($formula)));

        // Add a field for each ORDER BY column

        $queryConfig = $this->attributeValuesQuery->toStdClass();
        if ( isset($queryConfig->orderby) ) {
            foreach ( $queryConfig->orderby as $orderByField ) {
                $query->addField(new TableField($this->attributeTable, $orderByField, $this->qualifyColumnName($orderByField)));
            }
        }

        $this->addOrder($query, $multi_group);
    }

    /**
     * @see iGroupBy::addWhereJoin()
     * @see Query::addWhereAndJoin()
     */

    public function addWhereJoin(DataWarehouse\Query $query, $aggregateTableName, $operation, $whereConstraint)
    {
        $attributeKeyConstraints = array();

        // JOIN with the attribute table in the query
        $query->addTable($this->attributeTable);

        // To support multi-column attribute keys, add JOIN conditions for each attribute and
        // aggregate column in the key map.

        foreach ( $this->attributeToAggregateKeyMap as $attributeKey => $aggregateKey ) {
            $attributeTableField = new TableField($this->attributeTable, $attributeKey);
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
            $where = new WhereCondition($attributeKey, $operation, sprintf('(%s)', implode(',', $valueList)));
            $this->logger->debug(sprintf('%s Add where condition: %s', $this, $where));
            $query->addWhereCondition($where);
        }
    }

    /**
     * @see iGroupBy::addOrder()
     */

    public function addOrder(DataWarehouse\Query $query, $multi_group = false, $direction = 'ASC', $prepend = false)
    {
        // There can be zero or more order by fields specified in the attribute values query. Add an
        // order by clause for each of them.

        $queryConfig = $this->attributeValuesQuery->toStdClass();

        if ( ! isset($queryConfig->orderby) ) {
            return;
        }

        foreach ( $queryConfig->orderby as $orderByField ) {
            $orderBy = new OrderBy(
                new TableField($this->attributeTable, $orderByField),
                $direction,
                $this->id
            );

            if ( $prepend ) {
                $this->logger->debug(sprintf('%s Prepending order by to query: %s', $this, $orderBy));
                $query->prependOrder($orderBy);
            } else {
                $this->logger->debug(sprintf('%s Adding order by to query: %s', $this, $orderBy));
                $query->addOrder($orderBy);
            }
        }
    }

    /**
     * @see iGroupBy::getAttributeValues()
     */

    public function getAttributeValues(array $restrictions = null)
    {
        // Use the attribute values query as a template so we don't modify the original object
        $queryConfig = $this->attributeValuesQuery->toStdClass();
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
                $this->attributeValuesQuery->getRecord('long_name')
            );
            $queryParameters[':name'] = $restrictions['name'];
        }

        if ( ! isset($queryConfig->where) || ! is_array($queryConfig->where) ) {
            $queryConfig->where = $whereConditions;
        } else {
            $queryConfig->where = array_merge($queryConfig->where, $whereConditions);
        }

        $queryObj = new Query($queryConfig, '`', $this->logger);
        $sql = $queryObj->getSql();

        $this->logger->debug(sprintf("%s: Fetch attribute values with query\n%s", $this, $sql));

        return \DataWarehouse::connect()->query($sql, $queryParameters);
    }

    /**
     * @see iGroupBy::__toString()
     */

    public function __toString()
    {
        return sprintf('Realm(%s)->GroupBy(id=%s, table=%s)', $this->realm->getId(), $this->id, $this->attributeTable);
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
}
