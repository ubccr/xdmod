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
     * @param mixed $specificaiton A stdClass contaning the realm definition or a string specifying
     *   a realm name.
     * @param Realm $realm The realm object that this GroupBy is associated with.
     * @param Log|null $logger A Log instance that will be utilized during processing.
     *
     * @return Statistic A Statistic class.
     *
     * @throws Exception if there was an error creating the object.
     */

    public static function factory($specification, Realm $realm = null, Logger $log = null);

    /**
     * List the statistics that have been defined in the database.
     *
     * @param string $realmName The realm that we are examining for statistics
     * @param Log|null $logger A Log instance that will be utilized during processing.
     *
     * @return array An associative array of Statistic names where the keys are realm ids and the
     *   values are realm names.
     */

    public static function enumerate($realmName, Logger $log = null);

    /**
     * Save the Realm into a data store.
     *
     * @throws Exception if there was an error while saving.
     */

    public function save();

    /**
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

    public function getNameAndDescription();

    /**
     * Note: The corresponding setUnit() is only called from the constructor.
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
     * @return string The statistic formula, which is an SQL fragment that can be embedded into an
     *   SELECT statement.
     */

    public function getFormula(Query $query = null);

    /**
     * Note: The corresponding setDecimals() is only called from the constructor.
     * @return int The number of significant digits to display
     */

    public function getDecimals();

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

    public function getSortOder();

    /**
     * Note: Replaces isVisible() but returns the opposite value (isVisible() == ! isDisabled())
     *
     * @return boolean TRUE if this statistic has been disabled.
     */

    public function isDisabled();


    /**
     * Note: Returns null except where overriden by the JobViewer code
     *
     * @return WhereCondition Any additional WhereConditions defined for this statistic
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
     * @return string The name of the weighting statistic.
     */

    public function getWeightStatName();

    /**
     * Generate a string representation of the object
     */

    public function __toString();
}
