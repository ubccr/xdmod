<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/**
 * @author Amin Ghadersohi
 * @date 2011-Feb-07
 *
 * class for calculating the average wait duration in hours
 */

class SEMAverageWaitHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'SQRT(
                COALESCE(
                    (
                        (
                            SUM(COALESCE(jf.sum_waitduration_squared, 0.0))
                            /
                            SUM(jf.started_job_count)
                        )
                        -
                        POW(
                            SUM(COALESCE(jf.waitduration, 0))
                            /
                            SUM(jf.started_job_count)
                            , 2
                        )
                    )
                    /
                    SUM(jf.started_job_count)
                    , 0
                )
            )
            /
            3600.0',
            'sem_avg_waitduration_hours',
            'Std Dev: Wait Hours: Per Job',
            'Hour',
            2
        );
    }
    public function getWeightStatName()
    {
        return 'started_job_count';
    }

    public function getInfo()
    {
        return 'The standard error of the average time, in hours, an ' . ORGANIZATION_NAME . ' job had to wait until it began to execute.<br/>
		<i>Std Err of the Avg: </i> The standard deviation of the sample mean, estimated by the sample estimate of the population standard deviation (sample standard deviation) divided by the square root of the sample size (assuming statistical independence of the values in the sample).';
    }
    public function isVisible()
    {
        return false;
    }
}
