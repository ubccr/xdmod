<?php

namespace DataWarehouse\Query\Jobs\Statistics;

class JobSizeWeightedByCPUHours extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(
                SUM(jf.processor_count * jf.cpu_time) / SUM(jf.cpu_time),
                0
            )',
            'avg_job_size_weighted_by_cpu_hours',
            'Job Size: Weighted By CPU Hours',
            'Core Count',
            1
        );
    }

    public function getInfo()
    {
        return 'The average ' . ORGANIZATION_NAME . ' job size weighted by'
            . ' CPU Hours. Defined as <br><i>Average Job Size Weighted By'
            . ' CPU Hours: </i> sum(i = 0 to n){job i core count*job i cpu'
            . ' hours}/sum(i =  0 to n){job i cpu hours}';
    }
}
