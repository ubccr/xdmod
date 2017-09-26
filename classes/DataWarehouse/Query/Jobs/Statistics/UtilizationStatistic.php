<?php
/**
 * Class for calculating the percent utilization of a resource.
 *
 * @author Amin Ghadersohi
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Jobs\Statistics;

use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;

class UtilizationStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance)
    {
        if ($query_instance->getQueryType() == 'aggregate') {
            $date_table_start_ts = $query_instance->_start_date_ts;
            $date_table_end_ts   = $query_instance->_end_date_ts;
        } else {
            $agg_unit = $query_instance->getAggregationUnit()->getUnitName();
            $date_table = $query_instance->getDateTable();
            $date_table_start_ts = new TableField(
                $date_table,
                $agg_unit . '_start_ts'
            );
            $date_table_end_ts   = new TableField(
                $date_table,
                $agg_unit . '_end_ts'
            );
        }

        parent::__construct(
            "100.0 * (
                COALESCE(
                    SUM(jf.cpu_time / 3600.0)
                    /
                    (
                        SELECT SUM( ra.percent * inner_days.hours * rs.processors / 100.0 )
                        FROM modw.resourcespecs rs,
                             modw.resource_allocated ra,
                             modw.days inner_days
                        WHERE
                            inner_days.day_middle_ts BETWEEN ra.start_date_ts AND COALESCE(ra.end_date_ts, 2147483647) AND
                            inner_days.day_middle_ts BETWEEN rs.start_date_ts AND COALESCE(rs.end_date_ts, 2147483647) AND
                            inner_days.day_middle_ts BETWEEN $date_table_start_ts AND $date_table_end_ts AND
                            ra.resource_id = rs.resource_id
                            AND FIND_IN_SET(
                                rs.resource_id,
                                GROUP_CONCAT(DISTINCT jf.task_resource_id)
                            ) <> 0
                    ),
                    0
                )
            )",
            'utilization',
            ORGANIZATION_NAME . ' Utilization',
            '%',
            2
        );
    }

    public function getInfo()
    {
        return "The percentage of the " . ORGANIZATION_NAME
            . " obligation of a resource that has been utilized by "
            . ORGANIZATION_NAME . " jobs.<br/><i>" . ORGANIZATION_NAME
            . " Utilization:</i> The ratio of the total CPU hours consumed"
            . " by " . ORGANIZATION_NAME . " jobs over a given time period"
            . " divided by the total CPU hours that the system is"
            . " contractually required to provide to " . ORGANIZATION_NAME
            . " during that period. It does not include"
            . " non-" . ORGANIZATION_NAME . " jobs.<br/>It is worth noting"
            . " that this value is a rough estimate in certain cases where"
            . " the resource providers don't provide accurate records of"
            . " their system specifications, over time.";
    }
}
