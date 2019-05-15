<?php
namespace DataWarehouse\Query\Jobs\Statistics;

use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;

class NodeUtilizationStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance)
    {
        if ($query_instance->getQueryType() == 'aggregate') {
            $date_table_start_ts = $query_instance->_start_date_ts;
            $date_table_end_ts   = $query_instance->_end_date_ts;
        } else {
            $date_table = $query_instance->getDateTable();
            $agg_unit = $query_instance->getAggregationUnit()->getUnitName();
            $date_table_start_ts = new TableField(
                $date_table,
                $agg_unit . '_start_ts'
            );
            $date_table_end_ts = new TableField(
                $date_table,
                $agg_unit . '_end_ts'
            );
        }

        parent::__construct(
            "
                100.0 * (
                    COALESCE(
                        SUM(jf.node_time)
                        /
                        (
                            SELECT
                                SUM(ra.percent * inner_days.hours * rs.q_nodes / 100.0)
                            FROM
                                modw.resourcespecs rs,
                                modw.resource_allocated ra,
                                modw.days inner_days
                            WHERE
                                    inner_days.id BETWEEN YEAR(FROM_UNIXTIME(ra.start_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(ra.start_date_ts)) AND COALESCE(YEAR(FROM_UNIXTIME(ra.end_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(ra.end_date_ts)), 999999999)
                                AND inner_days.id BETWEEN YEAR(FROM_UNIXTIME(rs.start_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(rs.start_date_ts)) AND COALESCE(YEAR(FROM_UNIXTIME(rs.end_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(rs.end_date_ts)), 999999999)
                                AND inner_days.id BETWEEN YEAR(FROM_UNIXTIME($date_table_start_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME($date_table_start_ts)) AND YEAR(FROM_UNIXTIME($date_table_end_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME($date_table_end_ts))
                                AND ra.resource_id = rs.resource_id
                                AND FIND_IN_SET(
                                        rs.resource_id,
                                        GROUP_CONCAT(DISTINCT jf.task_resource_id)
                                    ) <> 0
                        ),
                        0
                    ) / 3600.0
                )
            ",
            'node_utilization',
            ORGANIZATION_NAME . ' Node Utilization',
            '%',
            2
        );
    }

    public function getInfo()
    {
        return 'The percentage of resource nodes utilized by ' . ORGANIZATION_NAME . ' jobs.<br/><i>' . ORGANIZATION_NAME . ' Node Utilization:</i> the ratio of the total node hours consumed by ' . ORGANIZATION_NAME . ' jobs over a given time period divided by the total node hours that the system could have potentially provided during that period. It does not include non-' . ORGANIZATION_NAME . ' jobs.<br/>This value is only accurate if node sharing is not allowed';
    }
}
