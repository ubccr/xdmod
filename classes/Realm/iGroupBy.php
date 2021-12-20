<?php
/**
 * XDMoD implements descriptive attributes using GroupBy classes. This definition encapsulates the
 * information necessary to partition Realm data by a descriptive attribute, provide a list of
 * available filters, and also to connect the internals as needed. As such, the information needed
 * to define a GroupBy is inherently specific to a particular Realm.
 */

namespace Realm;

use Psr\Log\LoggerInterface;

interface iGroupBy
{
    /**
     * Instantiate a GroupBy class using the specified options or realm name.
     *
     * @param string $shortName The short internal identifier for the group by that will be
     *   instantiated.
     * @param stdClass $config An object containing the configuration for this GroupBy
     * @param Realm $realm Realm object that this GroupBy will belong to.
     * @param LoggerInterface|null $logger A Monolog Logger that will be utilized during processing.
     *
     * @return GroupBy A GroupBy class.
     *
     * @throws Exception if there was an error creating the object.
     */

    public static function factory($shortName, \stdClass $config, Realm $realm, LoggerInterface $logger = null);

    /**
     * @return Realm The realm that this GroupBy is associated with.
     */

    public function getRealm();

    /**
     * @return string The short internal identifier.
     */

    public function getId();

    /**
     * @return string The human-readable display name.
     */

    public function getName();

    /**
     * @return string The description of the statistic formatted for display in a web browser.
     */

    public function getHtmlDescription();

    /**
     * @return string The name and description together formatted for display in a web browser.
     */

    public function getHtmlNameAndDescription();

    /**
     * @param boolean $includeSchema TRUE to include the schema in the table name.
     *
     * @return string The table where attributes are defined (i.e., the dimension table)
     */

    public function getAttributeTable($includeSchema = true);

    /**
     * @return array An array of strings containing the key column name(s) in the attribute table.
     * This is also used to map data between the attribute and aggregate tables.
     */

    public function getAttributeKeys();

    /**
     * @return array An array containing the names of the key columns in the aggregate table. This
     *   is also used to map data between the attribute and aggregate tables.
     */

    public function getAggregateKeys();

    /**
     * @return string The name of the module that defined this GroupBy. The default is the module
     *   from the parent Realm.
     */

    public function getModuleName();

    /**
     * @return int The order to advise how elements should be displayed visually in reference to one
     *   another.
     */

    public function getOrder();

    /**
     * Set the desired sort order as defined by the PHP array_multisort() function.
     * @see https://php.net/manual/en/function.array-multisort.php
     *
     * @param int|null $sortOrder The desired sort order or NULL for no sorting
     */

    public function setSortOrder($sortOrder = SORT_DESC);

    /**
     * @return int|null The current sort order where NULL means no sorting.
     */

    public function getSortOrder();

    /**
     * @return boolean TRUE if the attribute represented by this GroupBy should be available in the
     *   metric catalog and as a drill-down for a statistic in the user interface.
     */

    public function showInMetricCatalog();

    /**
     * @return boolean TRUE if the attribute represented by this GroupBy is an aggregation unit such
     *   as day, month, quarter, or year.
     */

    public function isAggregationUnit();

    /**
     * Check the request for filters associated with attributes supported by this group by and
     * construct an array of Parameter objects that can be added to a query to restrict the results
     * to those matching these attributes. This uses information from the "attribute_filter"
     * configuration option. If the "mapping_sql" option is specified a subquery will be used to
     * apply the parameter, otherwise the list of filter values will be used directly.
     *
     * Filters are identified in 2 ways:
     * 1) By looking for a parameter with the short name of this group by and creating a filter with
     *    its (singular) value.
     * 2) By looking for a parameter with the short name appended with "_filter" and creating a
     *    filter with the (possibly multiple) values.
     *
     * @param array $request The HTTP request
     *
     * @return array An array of \DataWarehouse\Query\Model\Parameter objects
     */

    public function generateQueryFiltersFromRequest(array $request);

    /**
     * Check the request for filters associated with attributes supported by this group by and
     * construct an array of human-readable strings that can be used to display the filters on a
     * chart. This is tyically "<label> = <filter>" or "<label> = (<filter_1>, ..., <filter_n>)".
     * This uses information from the "filter_description_query" configuration option. If not
     * specified, the list of filter values will be used directly.
     *
     * Filters are identified in 2 ways:
     * 1) By looking for a parameter with the short name of this group by and creating a filter with
     *    its (singular) value.
     * 2) By looking for a parameter with the short name appended with "_filter" and creating a
     *    filter with the (possibly multiple) values.
     *
     * @param array $request The HTTP request
     *
     * @return array An array of label strings, typically of the form "label = string"
     */

    public function generateQueryParameterLabelsFromRequest(array $request);

    /**
     * Apply the current GroupBy to the specified Query.
     *
     * @param \DataWarehouse\Query\iQuery $query The query that this GroupBy will be added to.
     * @param bool $multi_group TRUE if this query can have multiple group bys. This is only set to
     *   TRUE in Query::addGroupBy() and set to FALSE if a group by/dimension is specified in the
     *   Query class constructor.
     */

    public function applyTo(\DataWarehouse\Query\iQuery $query, $multi_group = false);

    /**
     * Add a WHERE condition to the specified query. This will perform the following operations:
     *  1. Add the descriptive attributes table to the query
     *  2. Add a WHERE condition to the query that will serve as the JOIN specificaiton
     *  3. Add a WHERE condition to the query ensuring that the descriptive attributes are
     *     constrained according to the value supplied.
     *
     * @param \DataWarehouse\Query\iQuery $query The query that this GroupBy will be added to.
     * @param string $aggregateTableName (The full name of the aggregate table including aggregation unit (e.g.,
     *   jobfact_by_day)
     * @param string $operation The comparison operation used by the WHERE condition (e.g., "IN",
     *   "=", etc.)
     * @param string $value The acceptable values of the WHERE condition
     */

    public function addWhereJoin(\DataWarehouse\Query\iQuery $query, $aggregateTableName, $operation, $whereConstraint);

    /**
     * Add an ORDER BY clause to the specified query.
     *
     * @param \DataWarehouse\Query\iQuery $query The query that this GroupBy will be added to.
     * @param bool $multi_group TRUE if this query can have multiple group bys. This is only set to
     *   TRUE in Query::addGroupBy() and set to FALSE if a group by/dimension is specified in the
     *   Query class constructor.
     * @param string $direction The sort order (ASC or DESC)
     * @param boolean $prepend TRUE to insert this ORDER BY at the start of the list
     */

    public function addOrder(\DataWarehouse\Query\iQuery $query, $multi_group = false, $direction = 'ASC', $prepend = false);

    /**
     * Execute the query to retrieve the list of possible values for this descriptive attribute
     * (e.g., dimension).
     *
     * @param array $restrictions An associative array containing restricitons to be placed on the
     *   possible values query. They keys and associative values may be:
     *   id => Return only attributes matching the specified id
     *   name => Return only attributes where the short or long name contain the specified string
     *
     * @return array An associative array containing a list of values for this GroupBy with the
     *   following keys: (id, short_name, long_name)
     */

    public function getAttributeValues(array $restrictions = null);

    /**
     * @return \ETL\DbModel\Query The Query object used to generate SQL for obtaining the values of
     * the descriptive attribute that this GroupBy represents.
     */

    public function getAttributeValuesQuery();

    /**
     * Accessors used to create usage chart settings in DataWarehouse/Access/Usage.php and
     * DataWarehouse/Query/GroupBy.php
     */

    /**
     * @return stdClass A class containing all of the default values for a group by class.
     */

    public function getChartSettings($isMultiChartPage = false);

    /**
     * @return string
     */

    public function getDefaultDatasetType();

    /**
     * @param string $dataset_type The type of dataset that we are querying (e.g., timeseries or
     *   aggregate).
     *
     * @return string
     */

    public function getDefaultDisplayType($dataset_type = 'timeseries');

    /**
     * @return string
     */

    public function getDefaultCombineMethod();

    /**
     * @return string
     */

    public function getDefaultShowLegend();

    /**
     * @return int
     */

    public function getDefaultLimit($isMultiChartPage = false);

    /**
     * @return int
     */

    public function getDefaultOffset();

    /**
     * @return string "y" or "n".
     */

    public function getDefaultLogScale();

    /**
     * @return string "y" or "n".
     */

    public function getDefaultShowTrendLine();

    /**
     * @return string "y" or "n".
     */

    public function getDefaultShowErrorBars();

    /**
     * @return string "y" or "n".
     */

    public function getDefaultShowGuideLines();

    /**
     * @return string "y" or "n".
     */

    public function getDefaultShowAggregateLabels();

    /**
     * @return string "y" or "n".
     */

    public function getDefaultShowErrorLabels();

    /**
     * @return string "y" or "n".
     */

    public function getDefaultEnableErrors();

    /**
     * @return string
     */

    public function getDefaultEnableTrendLine();

    /**
     * Provide a category for this GroupBy allowing GroupBys in the same category to be grouped
     * together. For example, the Job Viewer UI does this.  Currently called by
     * QueryDescripter::getGroupByCategory().
     *
     * @return string The category for this GroupBy, defaults to "uncategorized".
     */

    public function getCategory();

     /**
     * Provides boolean for each GroupBy that determines if it is allowed to show all values to
     * every user or not. This is currently used by the Job Efficiency Tab to show all bucket values for
     * histograms on drilldown.
     *
     * @return bool Returns true if group by is allowed to show all dimension values.
     */

    public function showAllDimensionValues();

    /**
     * Generate a string representation of the object
     */

    public function __toString();
}
