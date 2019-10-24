<?php
namespace DataWarehouse\Query\Jobs\Statistics;

use DataWarehouse\Query\Model\TableField;

class AverageNodeHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance)
    {
        $job_count = 'jf.running_job_count';

        if ($query_instance->getQueryType() == 'aggregate') {
            $date_table = $query_instance->getDateTable();
            if ($date_table) {
                $date_id_field = new TableField($date_table, 'id');
                $job_count = 'CASE ' . $date_id_field . ' WHEN ' . $query_instance->getMinDateId() . ' THEN jf.running_job_count ELSE jf.started_job_count END';
            }
        }
        parent::__construct(
            "COALESCE(SUM(jf.node_time) / SUM($job_count), 0) / 3600.0",
            'avg_node_hours',
            'Node Hours: Per Job',
            'Node Hour',
            2
        );
    }

    public function getInfo()
    {
        return 'The average node hours (number of nodes x wall time hours) per ' . ORGANIZATION_NAME . ' job.';
    }
}
