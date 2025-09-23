<?php

namespace User\Elements;

use \DataWarehouse\Query\AggregateQuery;
use \DataWarehouse\Query\TimeseriesQuery;
use Realm\GroupBy;

class QueryDescripter
{

    /**
     * The GroupBy for this particular QueryDescripter.
     *
     * @var ?GroupBy
     */
    protected $groupByInstance;

    /**
     * @var \Realm\iRealm The Realm that we are querying.
     */
    private $realm = null;

    private $_realm_name;

    /**
     * @var string
     */
    private $_group_by_name;

    /**
     * The name of the default statistic or "all".
     *
     * @var string
     */
    private $_default_statisticname;

    /**
     * @var string
     */
    private $_default_aggregation_unit_name;

    /**
     * Either "aggregate" or "timeseries".
     *
     * @var string
     */
    private $_default_query_type;

    /**
     * @var int
     */
    private $_order_id;

    /**
     * @var bool
     */
    private $_show_menu;

    /**
     * @var bool
     */
    private $_disable_menu;

    public function __construct(
        $realm_name,
        $group_by_name,
        array $drill_target_group_bys = array(),
        $default_statisticname = 'all',
        $default_aggregation_unit_name = 'auto',
        $default_query_type = 'aggregate',
        $order_id = 0
    ) {

        $this->realm = \Realm\Realm::factory($realm_name);
        $this->_realm_name = $this->realm->getId();
        $this->_group_by_name   = $group_by_name;
        $this->_default_statisticname         = $default_statisticname;
        $this->_default_aggregation_unit_name = $default_aggregation_unit_name;
        $this->_default_query_type            = $default_query_type;
        $this->_order_id = $order_id;
        $this->_show_menu    = true;
        $this->_disable_menu = false;
    }

    public function getDrillTargets($hiddenGroupBys = array())
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
        array $parameters = array()
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
        array $parameters = array()
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
        array $parameters = array(),
        $query_type = 'aggregate'
    ) {
        $queries    = array();
        $statistics = array();

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
        $results = array();

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
        $parameters = array();
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
        $labels = array();
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

    public function setDefaultStatisticName($stat)
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
