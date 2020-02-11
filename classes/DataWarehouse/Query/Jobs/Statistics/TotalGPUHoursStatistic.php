<?php

namespace DataWarehouse\Query\Jobs\Statistics;

class TotalGPUHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.gpu_time), 0) / 3600.0',
            'total_gpu_hours',
            'GPU Hours: Total',
            'GPU Hour'
        );
    }

    public function getInfo()
    {
        return 'The total GPU hours (number of GPUs x wall time hours) used by ' . ORGANIZATION_NAME . ' jobs.<br/>For each job, the GPU usage is aggregated. For example, if a job used 1000 GPUs for one minute, it would be aggregated as 1000 GPU minutes or 16.67 GPU hours.';
    }
}
