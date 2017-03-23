<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/*
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* class for calculating the statistics pertaining to a job query
*/
class RunningJobCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('coalesce(sum(jf.running_job_count),0)', 'running_job_count', 'Number of Jobs Running', 'Number of Jobs', 0);
    }

    public function getInfo()
    {
        return  "The total number of running ".ORGANIZATION_NAME." jobs.<br/>
        <i>Job: </i>A scheduled process for a computer resource in a batch processing environment.";
    }
    public function isVisible()
    {
        return true;
    }
}
