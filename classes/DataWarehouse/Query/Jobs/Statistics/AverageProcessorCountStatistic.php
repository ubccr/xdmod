<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/*
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* class for calculating the average processor count
*/

class AverageProcessorCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        $job_count_formula = $query_instance->getQueryType() == 'aggregate'?'job_count':'running_job_count';
        parent::__construct('coalesce(sum(jf.processors*jf.'.$job_count_formula.')/sum(jf.'.$job_count_formula.'),0)', 'avg_processors', 'Job Size: Per Job', 'Core Count', 1);
    }

    public function getInfo()
    {
        return  "The average job size per  ".ORGANIZATION_NAME." job.<br>
        <i>Job Size: </i>The number of processor cores used by a (parallel) job.";
    }
}
