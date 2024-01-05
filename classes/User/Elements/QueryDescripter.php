<?php

namespace User\Elements;

use \DataWarehouse\Query\AggregateQuery;
use \DataWarehouse\Query\TimeseriesQuery;

class QueryDescripter
{

    /**
     * @var \Realm\iRealm The Realm that we are querying.
     */
    private $realm = null;

    private $_realm_name;

    /**
     * @var bool
     */
    private $_show_menu;

    /**
     * @var bool
     */
    private $_disable_menu;

    /**
     * @param string $group_by_name
     * @param string $default_statisticname
     * @param string $default_aggregation_unit_name
     * @param string $default_query_type
     * @param int $order_id
     */
    public function __construct(
        $realm_name,
        private $_group_by_name,
        array $drill_target_group_bys = [],
        /**
         * The name of the default statistic or "all".
         */
        private $_default_statisticname = 'all',
        private $_default_aggregation_unit_name = 'auto',
        /**
         * Either "aggregate" or "timeseries".
         */
        private $_default_query_type = 'aggregate',
        private $_order_id = 0
    ) {

        $this->realm = \Realm\Realm::factory($realm_name);
        $this->_realm_name = $this->realm->getId();
        $this->_show_menu    = true;
        $this->_disable_menu = false;
    }

    public function getDrillTargets($hiddenGroupBys = [])
    {
        return $this->realm->getDrillTargets(
            $this->_group_by_name,
            $hiddenGroupBys,
            \Realm\Realm::SORT_ON_NAME
        );
    }

    public function setDisableMenu($b)
    {
        $this->_disable_menu = $b;

        return $this;
    }

    public function getDisableMenu()
    {
        return $this->_disable_menu;
    }

    public function setShowMenu($b)
    {
        $this->_show_menu = $b;

        return $this;
    }

    public function getShowMenu()
    {
        return $this->_show_menu;
    }

    public function getRealmName()
    {
        return $this->realm->getName();
    }

    public function getGroupByName()
    {
        return $this->_group_by_name;
    }

    public function getGroupByLabel()
    {
        return $this->getGroupByInstance()->getName();
    }

    public function getGroupByCategory()
    {
        return $this->getGroupByInstance()->getCategory();
    }

    public function getGroupByDescription()
    {
        return $this->getGroupByInstance()->getHtmlNameAndDescription();
    }

    public function getMenuLabel()
    {
        $groupByLabel = $this->getGroupByLabel();

        if ($this->getGroupByName() === 'none') {
            return $this->getRealmName() . ' Summary';
        } else {
            return $this->getRealmName() . ' by ' . $groupByLabel;
        }
    }

    public function getGroupByInstance()
    {
        if (!isset($this->groupByInstance)) {
            $this->groupByInstance = $this->realm->getGroupByObject($this->getGroupByName());
        }

        return $this->groupByInstance;
    }

    public function getAggregate(
        $start_date,
        $end_date,
        $statistic_name,
        $aggregation_unit_name = 'auto',
        array $parameters = []
    ) {
        return new AggregateQuery(
            $this->realm,
            $aggregation_unit_name,
            $start_date,
            $end_date,
            $this->getGroupByName(),
            $statistic_name,
            $parameters
        );
    }

    public function getTimeseries(
        $start_date,
        $end_date,
        $statistic_name,
        $aggregation_unit_name = 'auto',
        array $parameters = []
    ) {
        return new TimeseriesQuery(
            $this->realm,
            $aggregation_unit_name,
            $start_date,
            $end_date,
            $this->getGroupByName(),
            $statistic_name,
            $parameters
        );
    }

    public function getAllQueries(
        $start_date,
        $end_date,
        $aggregation_unit_name = 'auto',
        array $parameters = [],
        $query_type = 'aggregate'
    ) {
        $queries    = [];
        $statistics = [];

        if ($this->getDefaultStatisticName() == 'all') {
            $tmp_statistics = $this->getPermittedStatistics();

            foreach ($tmp_statistics as $tmp_statistic) {
                $statistics[] = $tmp_statistic;
            }
        } else {
            $statistics[] = $this->getDefaultStatisticName();
        }

        foreach ($statistics as $statistic) {
            if ($query_type == 'aggregate' || $query_type == 'Aggregate') {
                $queries[] = $this->getAggregate(
                    $start_date,
                    $end_date,
                    $statistic,
                    $aggregation_unit_name,
                    $parameters
                );
            } else {
                $queries[] = $this->getTimeseries(
                    $start_date,
                    $end_date,
                    $statistic,
                    $aggregation_unit_name,
                    $parameters
                );
            }
        }

        return $queries;
    }

    /* getStatisticsClasses
     *    Get statistics objects for use by the dw_descripter function. The statistics classes are
     *    all associated with the underlying Aggregate query.
     * @param statslist a list of requested statistics
     * @returns an array containing the statistics objects requested
     * */
    public function getStatisticsClasses(array $statslist)
    {
        $results = [];

        foreach($statslist as $statname)
        {
            $results[$statname] = $this->realm->getStatisticObject($statname);
        }

        return $results;
    }

    /**
     * @return array A list of short identifiers for statistics that are available for this realm.
     */

    public function getPermittedStatistics()
    {
        return $this->realm->getStatisticIds();
    }

    public function getStatistic($statistic_name)
    {
        return $this->realm->getStatisticObject($statistic_name);
    }

    /**
     * Generate a list of query filter objects for all group-bys associated with the current realm
     * based on the request.
     *
     * Note: This is currently only used by html/controllers/ui_data/summary3.php
     *
     * @param array $request An associative array of GET/POST request variables
     *
     * @return array An array of \DataWarehouse\Query\Model\Parameter objects
     */

    public function pullQueryParameters(&$request)
    {
        $parameters = [];
        $groupByObjects = $this->realm->getGroupByObjects();

        foreach ( $groupByObjects as $obj ) {
            $parameters = array_merge(
                $parameters,
                $obj->generateQueryFiltersFromRequest($request)
            );
        }

        return $parameters;
    }

    /**
     * Generate a list of query filter objects for all group-bys associated with the current realm
     * based on the request.
     *
     * Note: This is currently only used in classes/DataWarehouse/QueryBuilder.php
     *
     * @param array $request An associative array of GET/POST request variables
     *
     * @return array An array of label strings
     */

    public function pullQueryParameterDescriptions(&$request)
    {
        $labels = [];
        $groupByObjects = $this->realm->getGroupByObjects();

        foreach ( $groupByObjects as $obj ) {
            $labels = array_merge(
                $labels,
                $obj->generateQueryParameterLabelsFromRequest($request)
            );
        }

        sort($labels);

        return $labels;
    }

    public function getDefaultStatisticName()
    {
        return $this->_default_statisticname;
    }

    public function setDefaultStatisticName($stat): void
    {
        $this->_default_statisticname = $stat;
    }

    public function getDefaultAggregationUnitName()
    {
        return $this->_default_aggregation_unit_name;
    }

    public function getDefaultQueryType()
    {
        return $this->_default_query_type;
    }

    public function getOrderId()
    {
        return $this->_order_id;
    }

    public function getChartSettings($isMultiChartPage = false)
    {
        return $this->getGroupByInstance()->getChartSettings($isMultiChartPage);
    }
}
