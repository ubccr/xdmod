<?php

namespace DataWarehouse\Query\Jobs\Statistics;

class SEMAverageGPUHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        $job_count_formula = $query_instance->getQueryType() == 'aggregate' ? 'ended_job_count' : 'running_job_count';

        parent::__construct(
            'SQRT(
                COALESCE(
                    (
                        (
                            SUM(jf.sum_gpu_time_squared)
                            /
                            SUM(jf.' . $job_count_formula . ')
                        )
                        -
                        POW(
                            SUM(jf.gpu_time)
                            /
                            SUM(jf.' . $job_count_formula . '),
                            2
                        )
                    )
                    /
                    SUM(jf.' . $job_count_formula . '),
                    0
                )
            )
            /
            3600.0',
            'sem_avg_gpu_hours',
            'Std Dev: GPU Hours: Per Job',
            'GPU Hour',
            2
        );
    }

    public function getInfo()
    {
        return 'The standard error of the average GPU hours by each ' . ORGANIZATION_NAME . ' job.<br/><i>Std Err of the Avg: </i> The standard deviation of the sample mean, estimated by the sample estimate of the population standard deviation (sample standard deviation) divided by the square root of the sample size (assuming statistical independence of the values in the sample).';
    }

    public function isVisible()
    {
        return false;
    }
}
