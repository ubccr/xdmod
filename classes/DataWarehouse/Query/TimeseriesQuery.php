<?php
/**
 * Query the data warehouse for timeseries data specific to a given Realm. A timeseries query
 * returns statistics grouped by aggregation unit and a specific value for a dimensional attribute.
 * This is typically used in conjunction with an aggregate query to obtain the values of the
 * dimensions followed by one query for each of the dimensional values to obtain the statistics for
 * each aggregation unit.
 *
 * For example, the following aggregate query would be executed to obtain the dimensional values
 *
 * select
 * rf.code as 'resource_order_id',
 * rf.id as 'resource_id',
 * coalesce(sum(jf.cpu_time),0)/3600.0 as total_cpu_hours
 * from modw_aggregates.jobfact_by_day jf,
 * modw.days d,
 * modw.resourcefact rf
 * where d.id = jf.day_id
 * and jf.day_id between 201600357 and 201700001
 * and rf.id = jf.task_resource_id
 * group by rf.id
 * order by total_cpu_hours desc, rf.code asc;
 *
 * +-------------------+-------------+-----------------+
 * | resource_order_id | resource_id | total_cpu_hours |
 * +-------------------+-------------+-----------------+
 * | robertson         |           5 |     461887.9808 |
 * | frearson          |           1 |     217650.7417 |
 * | mortorq           |           2 |      77264.8497 |
 * | pozidriv          |           4 |      71984.2764 |
 * | phillips          |           3 |      11767.9883 |
 * +-------------------+-------------+-----------------+
 *
 * And then the following query would be executed for each value of the id returned above
 *
 * select
 * jf.day_id as 'day_id',
 * rf.id as 'resource_id',
 * coalesce(sum(jf.cpu_time),0)/3600.0 as total_cpu_hours
 * from modw_aggregates.jobfact_by_day jf,
 * modw.days d,
 * modw.resourcefact rf
 * where d.id = jf.day_id
 *  and jf.day_id between 201600357 and 201700001
 *  and rf.id = jf.task_resource_id
 * group by jf.day_id, rf.id having resource_id = '5'
 * order by total_cpu_hours desc, jf.day_id asc, rf.code asc
 *
 * +-----------+-------------+-----------------+
 * | day_id    | resource_id | total_cpu_hours |
 * +-----------+-------------+-----------------+
 * | 201600365 |           5 |     148413.6153 |
 * | 201600364 |           5 |     110834.4219 |
 * | 201600366 |           5 |     103231.2444 |
 * | 201700001 |           5 |      45876.6008 |
 * | 201600363 |           5 |      42740.1569 |
 * | 201600362 |           5 |      10791.9414 |
 * +-----------+-------------+-----------------+
 */

namespace DataWarehouse\Query;

use CCR\DB;
use Psr\Log\LoggerInterface;

class TimeseriesQuery extends Query implements iQuery
{
    /**
     * If set, is expected to be in the form:
     * $sortInfo = [
     *    [
     *       "column_name" : "<value>",
     *       "direction": "<value>"
     *    ]
     * ];
     *
     * @var array
     */
    protected $sortInfo;

    public function getQueryType()
    {
        return 'timeseries';
    }

    public function __construct(
        $realmId,
        $aggregationUnitName,
        $startDate,
        $endDate,
        $groupById = null,
        $statisticId = null,
        array $parameters = array(),
        LoggerInterface $logger = null
    ) {
        parent::__construct(
            $realmId,
            $aggregationUnitName,
            $startDate,
            $endDate,
            $groupById,
            $statisticId,
            $parameters,
            $logger
        );

        $this->addGroupBy($aggregationUnitName);
    }

    /**
     * returns the equivalent aggregate query instance that has
     * identical statistics, groupbys and where conditions.
     *
     * @return AggregateQuery
     */
    public function getAggregateQuery()
    {
        $agg_query = new \DataWarehouse\Query\AggregateQuery(
            $this->getRealmName(),
            $this->_aggregation_unit->getUnitName(),
            $this->_start_date,
            $this->_end_date
        );

        foreach ($this->_group_bys as $groupBy) {
            if ($groupBy->getId() === $this->_aggregation_unit->getUnitName()) {
                // skip the time-based groupby
                continue;
            }
            $agg_query->addGroupBy($groupBy->getId());
        }

        foreach ($this->_stats as $stat_name => $stat) {
            $agg_query->addStat($stat_name);
        }

        if (isset($this->sortInfo)) {
            foreach ($this->sortInfo as $sort) {
                $agg_query->addOrderBy($sort['column_name'], $sort['direction']);
            }
        }

        $agg_query->cloneParameters($this);

        return $agg_query;
    }

    /**
     * This call does not change the sort order of the timeseries query
     * itself, rather it is used to change the sort order of the associated
     * aggregrate query that is returned by the getAggregateQuery() function.
     * @param stdClass $data_description Data description object that includes the sort information.
     * @return void
     */
    public function addOrderByAndSetSortInfo($data_description)
    {
        switch ($data_description->sort_type) {
            case 'value_asc':
                $this->sortInfo = array(
                    array(
                        'column_name' => $data_description->metric,
                        'direction' => 'asc'
                    )
                );
                break;

            case 'value_desc':
                $this->sortInfo = array(
                    array(
                        'column_name' => $data_description->metric,
                        'direction' => 'desc'
                    )
                );
                break;

            case 'label_asc':
                $this->sortInfo = array(
                    array(
                        'column_name' => $data_description->group_by,
                        'direction' => 'asc'
                    )
                );
                break;

            case 'label_desc':
                $this->sortInfo = array(
                    array(
                        'column_name' => $data_description->group_by,
                        'direction' => 'desc'
                    )
                );
                break;
        }
    }

    protected function setDuration(
        $start_date,
        $end_date
    ) {
        parent::setDuration($start_date, $end_date);

        $this->setDurationFormula(
            new \DataWarehouse\Query\Model\TableField(
                $this->_date_table,
                'hours'
            )
        );
    }

    public function getQueryString(
        $limit = null,
        $offset = null,
        $extraHavingClause = null
    ) {
        $wheres = $this->getWhereConditions();
        $groups = $this->getGroups();

        $select_tables = $this->getSelectTables();
        $select_fields = $this->getSelectFields();

        $select_order_by = $this->getSelectOrderBy();

        $select_group_by = array();

        foreach ($groups as $group) {
            $select_group_by[] = $group->getQualifiedName(false);
        }

        $format = <<<SQL
SELECT STRAIGHT_JOIN
  %s
FROM
  %s
WHERE
  %s
%s%s%s%s
SQL;

        $data_query = sprintf(
            $format,
            implode(",\n  ", $select_fields),
            implode(",\n  ", $select_tables),
            implode("\n  AND ", $wheres),
            ( count($select_group_by) > 0 ? "GROUP BY " . implode(",\n  ", $select_group_by) : "" ),
            ( null !== $extraHavingClause ? "\nHAVING $extraHavingClause" : "" ),
            ( count($select_order_by) > 0 ? "\nORDER BY " . implode(",\n  ", $select_order_by) : "" ),
            ( null !== $limit && null !== $offset ? "\nLIMIT $limit OFFSET $offset" : "" )
        );

        $this->logger->debug(
            sprintf("%s %s()\n%s", $this, __FUNCTION__, $data_query)
        );

        return $data_query;
    }

    public function getTimestamps()
    {
        // Obtain the attribute values \ETL\DbModel\Query object from the GroupBy and modify it to
        // add a WHERE clause restricting the values to those between the minn and max date of this
        // query. This should only be used for aggregation time period GroupBys (day, month, etc.)

        $dateIdsQuery = $this->_group_bys[$this->aggregationUnitName]->getAttributeValuesQuery();
        $queryConfig = $dateIdsQuery->toStdClass();
        $idFormula = $dateIdsQuery->getRecord('id');

        $whereConditions = array(
            sprintf('%s BETWEEN %s AND %s', $idFormula, $this->_min_date_id, $this->_max_date_id)
        );

        if ( ! isset($queryConfig->where) || ! is_array($queryConfig->where) ) {
            $queryConfig->where = $whereConditions;
        } else {
            $queryConfig->where = array_merge($queryConfig->where, $whereConditions);
        }

        $queryObj = new \ETL\DbModel\Query($queryConfig, '`', $this->logger);
        $sql = $queryObj->getSql();

        $this->logger->debug(sprintf("%s %s()\n%s", $this, __FUNCTION__, $sql));

        return DB::factory($this->_db_profile)->query($sql);

    }

    public function execute($limit = 10000000)
    {
        $dateIdsQuery = "select id,
                            {$this->aggregationUnitName}_start_ts,
                            {$this->aggregationUnitName}_middle_ts
                         from modw.{$this->aggregationUnitName}s
                            where
                              id between {$this->_min_date_id} and {$this->_max_date_id}
                              order by id asc";

        $this->logger->debug(
            sprintf("%s %s() Query date IDs\n%s", $this, __FUNCTION__, $dateIdsQuery)
        );

        $dateIdsResults = DB::factory($this->_db_profile)->query($dateIdsQuery);

        $empty_data         = array();
        $empty_data_weights = array();
        $data_labels        = array();
        $period_id_lookup   = array();

        $index = 0;

        foreach ($dateIdsResults as $dateIdResult) {
            $period_id        = $dateIdResult['id'];
            $period_start_ts  = $dateIdResult["{$this->aggregationUnitName}_start_ts"];

            $data_labels[]        = $period_start_ts;
            $empty_data[]         = 0;
            $empty_data_weights[] = 1;

            $period_id_lookup[$period_id] = $index;

            $index++;
        }

        $query_string = $this->getQueryString($limit);

        $time_start = microtime(true);
        $statement = DB::factory($this->_db_profile)->handle()->prepare($query_string);
        $statement->execute();
        $time_end = microtime(true);

        if ($this->_main_stat_field == null) {
            throw new \Exception('Timeseries: main_stat_field is null');
        }

        $stat        = $this->_main_stat_field->getId();
        $stat_weight = $this->_main_stat_field->getWeightStatName();

        $sem_name = Realm::getStandardErrorStatisticFromStatistic(
            $stat
        );

        $useWeights
            =  strpos($stat, 'avg_')             !== false
            || strpos($stat, 'count')            !== false
            || strpos($stat, 'utilization')      !== false
            || strpos($stat, 'rate')             !== false
            || strpos($stat, 'expansion_factor') !== false;

        $isMin = strpos($stat, 'min_') !== false;
        $isMax = strpos($stat, 'max_') !== false;

        $group_info           = array();
        $group_to_weight      = array();
        $group_to_value       = array();
        $group_to_id          = array();
        $group_to_short_label = array();

        while ($result = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
            if (isset($group_to_value[$result['name']])) {
                if ($isMin) {
                    $group_to_value[$result['name']] = min(
                        $group_to_value[$result['name']],
                        $result[$stat]
                    );
                } elseif ($isMax) {
                    $group_to_value[$result['name']] = max(
                        $group_to_value[$result['name']],
                        $result[$stat]
                    );
                } else {
                    $weight
                        = $result[$stat_weight] <= 0
                        ? 1
                        : $result[$stat_weight];

                    $group_to_value[$result['name']]
                        += $useWeights
                        ? $weight * $result[$stat]
                        : $result[$stat];

                    $group_to_weight[$result['name']] += $weight;
                }
            } else {
                if ($isMin) {
                    $group_to_value[$result['name']] = $result[$stat];
                } elseif ($isMax) {
                    $group_to_value[$result['name']] = $result[$stat];
                } else {
                    $weight = $result[$stat_weight]
                        <= 0
                        ? 1
                        : $result[$stat_weight];

                    $group_to_value[$result['name']]
                        = $useWeights
                        ? $weight * $result[$stat]
                        : $result[$stat];

                    $group_to_weight[$result['name']] = $weight;
                }
            }

            $group_to_short_label[$result['name']] = $result['short_name'];
            $group_to_id[$result['name']] = $result['id'];

            $group_info[$result['name']] = array(
                'id'     => $group_to_id[$result['name']],
                'name'   => $result['name'],
                'value'  => $group_to_value[$result['name']],
                'weight' => isset($group_to_weight[$result['name']])
                          ? $group_to_weight[$result['name']]
                          : 1,
            );
        }

        if ($useWeights) {
            foreach ($group_info as &$ggggg) {
                $ggggg['value'] = $ggggg['value'] / $ggggg['weight'];
            }
        }

        $sort_option = $this->_group_by->getSortOrder();

        if (isset($sort_option)) {
            $sort_option = $this->_main_stat_field->getSortOrder();

            $datanames  = array();
            $datavalues = array();

            foreach ($group_info as $key => $row) {
                $datanames[$key]  = $row['name'];
                $datavalues[$key] = $row['value'];
            }

            array_multisort(
                $datavalues,
                $sort_option,
                $datanames,
                SORT_ASC,
                $group_info
            );
        }

        // groups sorted by value => id
        $sorted_group_to_id = array();
        $sorted_group_to_short_label = array();

        $data_by_group = array();

        foreach ($group_info as $gi) {
            $data_by_group[$gi['name']] = $empty_data;

            $data_by_group[$gi['name'] . '-weights'] = $empty_data_weights;
            $data_by_group[$gi['name'] . '-sem']     = $empty_data;

            $sorted_group_to_id[$gi['name']]          = $group_to_id[$gi['name']];
            $sorted_group_to_short_label[$gi['name']] = $group_to_short_label[$gi['name']];
        }

        $data_by_group['series'] = $sorted_group_to_id ;
        $data_by_group['labels'] = $data_labels;
        $data_by_group['short_series'] = $sorted_group_to_short_label;

        $statement->closeCursor();
        $statement->execute();

        while ($result = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
            $data_by_group[$result['name']][$period_id_lookup[$result["{$this->aggregationUnitName}_id"]]] = $result[$stat];

            // running_job_count will never be zero, but just in case
            $data_by_group[$result['name'] . '-weights'][$period_id_lookup[$result["{$this->aggregationUnitName}_id"]]]
                = $result[$stat_weight] <= 0
                ? 1
                : $result[$stat_weight];

            $data_by_group[$result['name'] . '-sem'][$period_id_lookup[$result["{$this->aggregationUnitName}_id"]]]
                = isset($result[$sem_name])
                ? $result[$sem_name]
                : 0;
        }

        $data_by_group['query_string'] = $query_string;
        $data_by_group['query_time']   = $time_end - $time_start;

        return $data_by_group;
    }
}
