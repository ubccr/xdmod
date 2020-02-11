<?php

namespace DataWarehouse\Query\Jobs\Statistics;

use DataWarehouse\Query\Model\TableField;

class AverageGPUHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
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
            "COALESCE(SUM(jf.gpu_time) / SUM($job_count), 0) / 3600.0",
            'avg_gpu_hours',
            'GPU Hours: Per Job',
            'GPU Hour',
            2
        );
    }

    public function getInfo()
    {
        return 'The average GPU hours (number of GPUs x wall time hours) per ' . ORGANIZATION_NAME . ' job.<br/>For each job, the GPU usage is aggregated. For example, if a job used 1000 GPUs for one minute, it would be aggregated as 1000 GPU minutes or 16.67 GPU hours.';
    }
}
