<?php
/**
 * A Query provides a method to query the XDMoD data warehouse for data on a particular Realm. The
 * query may include data for a number of Statistics and must be grouped by at least one dimension,
 * including none.
 */

namespace DataWarehouse\Query;

use Log as Logger;  // CCR implementation of PEAR logger

interface iQuery
{
    /**
     * @param string $realmId The short identifier for the realm that we will be generating data for.
     * @param string $aggregationUnitName The aggregation unit to use for this query (e.g., day,
     *   month, year, etc.)
     * @param string $startDate The start date for this query (e.g., '2019-01-01').
     * @param string $endDate The end date for this query (e.g., '2019-01-31').
     * @param string $groupById The short identifier for the GroupBy that this query will use.
     * @param Log|null $logger A Log instance that will be utilized during processing.
     * @param string $statisticId A short identifier for an initial Statistic that will be returned
     *   by this query. Note that 'all' can be used to include all available statistics in the
     *   specified Realm.
     * @param array $parameters An array of \DataWarehouse\Query\Model\Parameter objects that will
     *   be used to add WHERE conditions to the Query.
     */

     // If there are statistics that should always be added for a specific Realm, specify them in
    // the Realm configuration and add them in the Query constructor. These don't appear to be
    // needed so lets wait and see.
    //
    // Jobs:
    // $statisticId = 'job_count'
    // $controlStats = array('started_job_count', 'running_job_count')
    //
    // Cloud Aggregate:
    // $statisticId = 'cloud_num_sessions_ended'
    // $controlStats = array('cloud_num_sessions_started', 'cloud_num_sessions_running')
    // Cloud Timeseries:
    // $statisticId = 'cloud_num_sessions_running'
    // $controlStats = array('cloud_num_sessions_started', 'cloud_num_sessions_running')
    //
    // Storage: None
    //
    // Accounts:
    // $controlStats = array('weight')
    //
    // Allocations:
    // $statisticId = 'rate_of_usage'
    // $controlStats = array('weight')
    //
    // Requests:
    // $controlStats = array('request_count')
    //
    // Allocations:
    // $controlStats = array('weight')
    //
    // SUPREMM:
    // $statisticId = 'job_count'
    // $controlStats = array('started_job_count', 'running_job_count')

    public function __construct(
        $realmId,
        $aggregationUnitName,
        $startDate,
        $endDate,
        $groupById = null,
        $statisticId = null,
        array $parameters = array(),
        Logger $logger = null
    );

    /**
     * Update the values of the internal VariableStore. Since the contents of the Query class are
     * volitile and may be modified on the fly, this should be called prior to performing variable
     * substitution. Note that rather than clearing and setting variables, we are using overwrite()
     * so we do not clear variables that may be set elsewhere.
     *
     * @return VariableStore The updated variable store object
     */

    public function updateVariableStore();

    /**
     * @return VariableStore The current variable store for this query. Note that it is safer to
     *    access the variable store via updateVariableStore() so that its data is updated.
     */

    public function getVariableStore();

    /**
     * @return Realm The object that represents the realm that is query is generating data for.
     */

    public function getRealm();

    /**
     * @return string The query type, for example 'aggreage' or 'timeseries'.
     */

    public function getQueryType();

    /**
     * @return bool TRUE if this query is an aggregate query.
     */

    public function isAggregate();

    /**
     * @return bool TRUE if this query is a timeseries query.
     */

    public function isTimeseries();

    /**
     * @return string The datasource associated with the query Realm. This is a required property
     *   for a Realm.
     */

    public function getDataSource();

    /**
     * @return string A string representation of this class to be used in debugging log output.
     */

    public function getDebugInfo();

    /**
     * @return string A general string representation of this class.
     */

    public function __toString();
}
