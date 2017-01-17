<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* class for calculating the total wait duration in hours 
*/

class TotalWaitHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('coalesce(sum(jf.waitduration/3600.0),0)', 'total_waitduration_hours', 'Wait Hours: Total', 'Hour');
        $this->setOrderByStat(SORT_DESC);
    }
    public function getWeightStatName()
    {
        return 'started_job_count';
    }

    public function getInfo()
    {
        return  "The total time, in hours, ".ORGANIZATION_NAME." jobs waited before execution on their designated resource.<br/>
		<i>Wait Time: </i>Wait time is defined as the linear time between submission of a job by a user until it begins to execute.";
    }
}
