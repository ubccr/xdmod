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
    public function __construct($query_instance = null)
    {
        $job_count_formula = $query_instance->getQueryType() == 'aggregate'?'job_count':'running_job_count';
        parent::__construct('coalesce(sum(jf.wallduration/3600.0)/sum(jf.'.$job_count_formula.'),0)', 'avg_wallduration_hours', 'Wall Hours: Per Job', 'Hour', 2);
    }

    public function getInfo()
    {
        return  "The average time, in hours, a ".ORGANIZATION_NAME." job takes to execute.<br/>
        <i>Wall Time:</i> Wall time is defined as the linear time between start and end time of execution for a particular job.";
    }
}
