<?php
/**
 * Statistics are functions that are applied to select metrics to generate a single value for each
 * subgroup of a partitioned data set. Typical statistics include sum, average, and count. Note that
 * statistics are able to be viewed in two modes: aggregate and time-series.  Aggregate statistics
 * are applied to all records in a subgroup while time-series statistics perform an additional
 * partitioning of a subgroup by time period (typically day, month, quarter, or year) prior to
 * applying the function to calculate the value.
 */

namespace Realm;

use Log as Logger;  // PEAR logger

interface iStatistic
{
    /**
     * Instantiate a Statistic class using the specified options or realm name.
     *
     * @param string $shortName The short internal identifier for the statistic that will be
     *   instantiated.
     * @param stdClass $config An object containing the configuration for this GroupBy
     * @param Realm $realm Realm object that this Statistic will belong to.
     * @param Log|null $logger A Log instance that will be utilized during processing.
     *
     * @return Statistic A Statistic class.
     *
     * @throws Exception if there was an error creating the object.
     */

    public static function factory($shortName, \stdClass $config, Realm $realm, Logger $logger = null);

    /**
     * @return Realm The realm that this Statistic is associated with.
     */

    public function getRealm();

    /**
     * Note: Was getName()
     *
     * @return string The short internal identifier. This is often used as an alias in SQL queries.
     * For example, "total_cpu_hours".
     */

    public function getId();

    /**
     * Note: Was getLabel()
     *
     * @param boolean $includeUnit TRUE to include the unit after the name
     *
     * @return string A human-readable name for the statistic. For example, "CPU Hours: Total".
     */

    public function getName($includeUnit = true);

    /**
     * Note: Was getInfo()
     *
     * @return string The description of the statistic formatted for display in a web browser.
     */

    public function getHtmlDescription();

    /**
     * Note: Was getDescription()
     *
     * @return string The name and description together formatted for display in a web browser.
     */

    public function getHtmlNameAndDescription();

    /**
     * Note: The corresponding setUnit() is only called from the constructor.
     *
     * @return string The unit of this statistic. For example, "Number of Jobs", "Number of PIs",
     *   "CPU Hour", etc.
     */

    public function getUnit();

    /**
     * In order to provide increased flexibility, the formulas specified by a statistic can make use
     * of variable substitution. When called, a number of variables set in the query VariableStore
     * are applied to the formula. The generic Statistic::getFormula() can be overriden as needed to
     * apply advanced logic.
     *
     * @param Query $query The target Query that this formula will be embedded in. Note that the
     *   formula will not actually be added to the query by this function.
     *
     * @return string The statistic formula and alias, which is an SQL fragment that can be embedded
     *   into an SELECT statement. For example:
     *   "COALESCE(SUM(jf.cpu_time),0)/3600.0 AS total_cpu_hours"
     */

    public function getFormula(\DataWarehouse\Query\iQuery $query = null);

    /**
     * Note: The corresponding setDecimals() is only called from the constructor.
     * @return int The number of significant digits to display
     */

    public function getPrecision();

    /**
     * Note: was setOrderbyStat()
     *
     * Set the desired sort order as defined by the PHP array_multisort() function.
     * @see https://php.net/manual/en/function.array-multisort.php
     *
     * @param int|null $sortOrder The desired sort order or NULL for no sorting
     */

    public function setSortOrder($sortOrder = SORT_DESC);

    /**
     * Note: was getOrderByStatOption()
     *
     * @return int|null The current sort order where NULL means no sorting.
     */

    public function getSortOrder();

    /**
     * @return string The name of the module that defined this Statistic. The default is the module
     *   from the parent Realm.
     */

    public function getModuleName();

    /**
     * @return int The order to advise how elements should be displayed visually in reference to one
     *   another.
     */

    public function getOrder();

    /**
     * Note: Returns null except where overriden by the JobViewer code
     *
     * @return WhereCondition|null A single additional WHERE condition to be added to the query
     *   containing this statistic or NULL if no additional WHERE condition was defined.
     */

    public function getAdditionalWhereCondition();

    /**
     * Indicates if a statistic can use tables where data is rolled up by time period to perform the
     * calculation for an aggregate chart. This has been subsumed by the "aggregate_formula" and
     * "timeseries_formula" configuration options.
     *
     * @return boolean TRUE if this statistic can use tables where data is rolled up by time period
     */

    public function usesTimePeriodTablesForAggregate();

    /**
     * @return string The name of the weighting statistic for this statistic. This is typically used
     *   when calculating values such as standard error but is likely that this is not currently
     *   used.
     *
     * Note: Was getWeightStatName()
     */

    public function getWeightStatisticId();

    /**
     * @return boolean TRUE if this statistic should be displayed in the metric catalog. In some cases,
     *   a statistic may be created to feed data to another component but not be intended to be directly
     *   accessible via the user interface. For example, the statistics that generate the data for
     *   standard error markers in the user interface (sem_avg_processors).
     */

    public function showInMetricCatalog();

    /**
     * Generate a string representation of the object
     */

    public function __toString();
}
