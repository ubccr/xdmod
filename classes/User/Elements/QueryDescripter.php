<?php

namespace User\Elements;

class QueryDescripter
{

    /**
     * @var string
     */
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
        $this->_realm_name      = $realm_name;
        $this->_group_by_name   = $group_by_name;

        $this->_default_statisticname         = $default_statisticname;
        $this->_default_aggregation_unit_name = $default_aggregation_unit_name;
        $this->_default_query_type            = $default_query_type;

        $this->_order_id = $order_id;

        $this->_show_menu    = true;
        $this->_disable_menu = false;

        $classname = $this->getClassnamePrefix() . 'Aggregate';
        $classname::registerStatistics();
        $classname::registerGroupBys();
    }

    public function getDrillTargets($statistic_name)
    {
        $groupbyInstance = $this->getGroupByInstance();

        return $groupbyInstance->getDrillTargets(
            $statistic_name,
            $this->getClassnamePrefix() . 'Aggregate'
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

    public function getClassnamePrefix()
    {
        return '\\DataWarehouse\\Query\\' . $this->getRealmName() . '\\';
    }

    public function getRealmName()
    {
        return $this->_realm_name;
    }

    public function getGroupByName()
    {
        return $this->_group_by_name;
    }

    public function getGroupByLabel()
    {
        return $this->getGroupByInstance()->getLabel();
    }

    public function getGroupByCategory()
    {
        return $this->getGroupByInstance()->getCategory();
    }

    public function getGroupByDescription()
    {
        return $this->getGroupByInstance()->getDescription();
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
            $classname = $this->getClassnamePrefix() . 'Aggregate';

            $this->groupByInstance
                = $classname::getGroupBy($this->getGroupByName());
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
        $classname = $this->getClassnamePrefix() . 'Aggregate';

        return new $classname(
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
        $classname =  $this->getClassnamePrefix() . 'Timeseries';

        return new $classname(
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
        $aggclass = $this->getDefaultQuery('2014-01-01', '2014-01-02');
        foreach($statslist as $statname)
        {
            $results[$statname] = $aggclass->addStat($statname);
        }

        return $results;
    }

    public function getPermittedStatistics()
    {
        $classname = $this->getClassnamePrefix() . 'Aggregate';

        $groupByObject = $this->getGroupByInstance();

        $permittedStatistics = $groupByObject->getPermittedStatistics();

        return $permittedStatistics;
    }

    public function getStatistic($statistic_name)
    {
        $classname =  $this->getClassnamePrefix() . 'Aggregate';

        return $classname::getStatistic($statistic_name);
    }

    public function pullQueryParameters(&$request)
    {
        $classname = $this->getClassnamePrefix() . 'Aggregate';
        $classname::registerGroupBys();
        $registeredGroupBys = $classname::getRegisteredGroupBys();

        $parameters = array();

        foreach (
            $registeredGroupBys
            as $registeredGroupByName => $registeredGroupByClassname
        ) {
            $group_by_instance = new $registeredGroupByClassname();

            $parameters = array_merge(
                $parameters,
                $group_by_instance->pullQueryParameters($request)
            );
        }

        return $parameters;
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        $classname = $this->getClassnamePrefix().'Aggregate';
        $classname::registerGroupBys();
        $registeredGroupBys = $classname::getRegisteredGroupBys();

        $parameters = array();

        foreach (
            $registeredGroupBys
            as $registeredGroupByName => $registeredGroupByClassname
        ) {
            $group_by_instance = new $registeredGroupByClassname();

            $parameters = array_merge(
                $group_by_instance->pullQueryParameterDescriptions($request),
                $parameters
            );
        }

        sort($parameters);

        return $parameters;
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

    public function getDefaultQuery(
        $start_date,
        $end_date,
        array $parameters = array()
    ) {
        if ($this->getDefaultQueryType() == 'timeseries') {
            return $this->getTimeseries(
                $start_date,
                $end_date,
                $this->getDefaultStatisticName(),
                $this->getDefaultAggregationUnitName(),
                $parameters
            );
        } else {
            return $this->getAggregate(
                $start_date,
                $end_date,
                $this->getDefaultStatisticName(),
                $this->getDefaultAggregationUnitName(),
                $parameters
            );
        }
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
