<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/*
 * @author Amin Ghadersohi
 * @date 2011-Feb-07
 *
 * class for calculating the average wall duration in hours
 */

class AverageWallHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance)
    {
        $job_count_formula = $query_instance->getQueryType() == 'aggregate' ? 'job_count' : 'running_job_count';
        parent::__construct(
            'COALESCE(
                SUM(jf.wallduration/3600.0)
                /
                SUM(jf.' . $job_count_formula . ')
            , 0)',
            'avg_wallduration_hours',
            'Wall Hours: Per Job',
            'Hour',
            2
        );
    }

    public function getInfo()
    {
        return "The average time, in hours, a job takes to execute.<br/>
            In timeseries view mode, the statistic shows the average wall time per job per
            time period. In aggregate view mode, this statistic is approximate. The
            approximation is accurate when the average job walltime is small compared to
            the aggregation period.<br />
            <i>Wall Time:</i> Wall time is defined as the linear time between start and end time of execution for a particular job.";
    }
}
