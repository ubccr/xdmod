<?php
namespace DataWarehouse\Query\Jobs\Statistics;

use DataWarehouse\Query\Model\TableField;

class AverageCPUHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
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
            "COALESCE(SUM(jf.cpu_time) / SUM($job_count), 0) / 3600.0",
            'avg_cpu_hours',
            'CPU Hours: Per Job',
            'CPU Hour',
            2
        );
    }

    public function getInfo()
    {
        return 'The average CPU hours (number of CPU cores x wall time hours) per ' . ORGANIZATION_NAME . ' job.<br/>For each job, the CPU usage is aggregated. For example, if a job used 1000 CPUs for one minute, it would be aggregated as 1000 CPU minutes or 16.67 CPU hours.';
    }
}
