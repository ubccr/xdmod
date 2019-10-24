<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class SEMAverageCPUHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        $job_count_formula = $query_instance->getQueryType() == 'aggregate' ? 'ended_job_count' : 'running_job_count';
        parent::__construct(
            'SQRT(
                COALESCE(
                    (
                        (
                            SUM(jf.sum_cpu_time_squared)
                            /
                            SUM(jf.' . $job_count_formula . ')
                        )
                        -
                        POW(
                            SUM(jf.cpu_time)
                            /
                            SUM(jf.' . $job_count_formula . ')
                            , 2
                        )
                    )
                    /
                    SUM(jf.' . $job_count_formula . ')
                , 0
                )
            )
            /
            3600.0',
            'sem_avg_cpu_hours',
            'Std Dev: CPU Hours: Per Job',
            'CPU Hour',
            2
        );
    }

    public function getInfo()
    {
        return ' The standard error of the average CPU hours by each ' . ORGANIZATION_NAME . ' job.<br/><i>Std Err of the Avg: </i> The standard deviation of the sample mean, estimated by the sample estimate of the population standard deviation (sample standard deviation) divided by the square root of the sample size (assuming statistical independence of the values in the sample).';
    }
    public function isVisible()
    {
        return false;
    }
}
