<?php
/**
 * A Query provides a method to query the XDMoD data warehouse for data on a particular Realm. The
 * query may include data for a number of Statistics and must be grouped by at least one dimension,
 * including none.
 */

namespace DataWarehouse\Query;

use Datawarehouse\Realm\Realm;
use Log as Logger;  // CCR implementation of PEAR logger

interface iQuery
{
    /**
     * @param string $realmName The short identifier for the realm that we will be generating data for.
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

    public function __construct(
        $realmName,
        $aggregationUnitName,
        $startDate,
        $endDate,
        $groupById = null,
        Logger $logger = null,
        $statisticId = '',
        array $parameters = array()
    );

    /**
     * @return string The query type, for example 'aggreage' or 'timeseries'.
     */

    public function getQueryType();
}
