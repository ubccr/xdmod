<?php

namespace DataWarehouse\Query\Jobs\Statistics;

class JobSizeWeightedByGPUHours extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.processor_count * jf.gpu_time) / SUM(jf.gpu_time), 0)',
            'avg_job_size_weighted_by_gpu_hours',
            'Job Size: Weighted By GPU Hours',
            'GPU Count',
            1
        );
    }

    public function getInfo()
    {
        return 'The average ' . ORGANIZATION_NAME . ' job size weighted by GPU Hours. Defined as <br><i>Average Job Size Weighted By GPU Hours: </i> sum(i = 0 to n){ job i core count * job i gpu hours}/sum(i =  0 to n){job i gpu hours}';
    }
}
