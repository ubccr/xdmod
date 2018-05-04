<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/*
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* class for calculating the average wait duration in hours
*/

class AverageWaitHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('coalesce(sum(jf.waitduration)/sum(jf.started_job_count),0)/3600.0', 'avg_waitduration_hours', 'Wait Hours: Per Job', 'Hour', 2);
    }
    public function getWeightStatName()
    {
        return 'started_job_count';
    }

    public function getInfo()
    {
        return "The average time, in hours, a " . ORGANIZATION_NAME . " job waits before execution on the designated resource.<br/>
		<i>Wait Time: </i>Wait time is defined as the linear time between submission of a job by a user until it begins to execute.";
    }
}
