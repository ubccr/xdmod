<?php

namespace DataWarehouse\Query;

use CCR\DB;

/**
 * Base class for a timeseries query.
 *
 * @author Amin Ghadersohi
 */
class Timeseries extends \DataWarehouse\Query\Query
{
    public function getQueryType()
    {
        return 'timeseries';
    }

    public function __construct(
        $realm_name,
        $datatable_schema,
        $datatable_name,
        array $control_stats,
        $aggregation_unit_name,
        $start_date,
        $end_date,
        $group_by,
        $stat = 'total_cpu_hours',
        array $parameters = array(),
        $query_groupname = 'query_groupname',
        array $parameter_description = array(),
        $single_stat = false
    ) {
        parent::__construct(
            $realm_name,
            $datatable_schema,
            $datatable_name,
            $control_stats,
            $aggregation_unit_name,
            $start_date,
            $end_date,
            $group_by,
            $stat,
            $parameters,
            $query_groupname,
            $parameter_description,
            $single_stat
        );

        $this->addGroupBy($this->_aggregation_unit->getUnitName());
    }

    protected function setDuration(
        $start_date,
        $end_date,
        $aggregation_unit_name
    ) {
        parent::setDuration($start_date, $end_date, $aggregation_unit_name);

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

        foreach ($groups as $group_key => $group) {
            $select_group_by[] = $group->getQualifiedName(false);
        }

        $data_query = "select \n" . implode(",\n", $select_fields) . "
                         from \n" . implode(",\n", $select_tables) . "
                        where \n" . implode("\n and ", $wheres);

        if (count($select_group_by) > 0) {
            $data_query .= " group by \n" . implode(",\n", $select_group_by);
        }

        if ($extraHavingClause != null) {
            $data_query .= " having " . $extraHavingClause . "\n";
        }

        if (count($select_order_by) > 0) {
            $data_query .= " order by \n" . implode(",\n", $select_order_by);
        }

        if ($limit !== null && $offset !== null) {
            $data_query .= " limit $limit offset $offset";
        }

        return $data_query;
    }

    public function getTimestamps()
    {
        $dateIdsQuery = $this->_group_bys[$this->_aggregation_unit->getUnitName()]->getPossibleValuesQuery();

        $dateIdsQuery = str_ireplace(
            'where ',
            "where gt.id >= {$this->_min_date_id} and gt.id <= {$this->_max_date_id} and ",
            $dateIdsQuery
        );

        return DB::factory($this->_db_profile)->query($dateIdsQuery);
    }

    public function execute($limit = 10000000)
    {
        $dateIdsQuery = "select id,
                            {$this->_aggregation_unit}_start_ts,
                            {$this->_aggregation_unit}_middle_ts
                         from modw.{$this->_aggregation_unit}s
                            where
                              id between {$this->_min_date_id} and {$this->_max_date_id}
                              order by id asc";

        $dateIdsResults = DB::factory($this->_db_profile)->query($dateIdsQuery);

        $empty_data         = array();
        $empty_data_weights = array();
        $data_labels        = array();
        $period_id_lookup   = array();

        $index = 0;

        foreach ($dateIdsResults as $dateIdResult) {
            $period_id        = $dateIdResult['id'];
            $period_start_ts  = $dateIdResult["{$this->_aggregation_unit}_start_ts"];
            $period_middle_ts = $dateIdResult["{$this->_aggregation_unit}_middle_ts"];

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

        $stat        = $this->_main_stat_field->getAlias()->getName();
        $stat_weight = $this->_main_stat_field->getWeightStatName();

        $sem_name = 'sem_' . $stat;

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

        $sort_option = $this->_group_by->getOrderByStatOption();

        if (isset($sort_option)) {
            $sort_option = $this->_main_stat_field->getOrderByStatOption();

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
            $data_by_group[$result['name']][$period_id_lookup[$result["{$this->_aggregation_unit}_id"]]] = $result[$stat];

            // running_job_count will never be zero, but just in case
            $data_by_group[$result['name'] . '-weights'][$period_id_lookup[$result["{$this->_aggregation_unit}_id"]]]
                = $result[$stat_weight] <= 0
                ? 1
                : $result[$stat_weight];

            $data_by_group[$result['name'] . '-sem'][$period_id_lookup[$result["{$this->_aggregation_unit}_id"]]]
                = isset($result[$sem_name])
                ? $result[$sem_name]
                : 0;
        }

        $data_by_group['query_string'] = $query_string;
        $data_by_group['query_time']   = $time_end - $time_start;

        return $data_by_group;
    }
}
