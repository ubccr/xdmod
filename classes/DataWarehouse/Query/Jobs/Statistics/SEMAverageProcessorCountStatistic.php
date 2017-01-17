<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* class for calculating the average processor count 
*/

class SEMAverageProcessorCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        $job_count_formula = $query_instance->getQueryType() == 'aggregate'?'job_count':'running_job_count';
        parent::__construct('coalesce(
	sqrt( (sum(pow(jf.processors,2)*jf.job_count)/sum(jf.'.$job_count_formula.')) -  pow(sum(jf.processors*jf.'.$job_count_formula.')/sum(jf.'.$job_count_formula.'),2) )
									/sqrt(sum(jf.'.$job_count_formula.'))
								,0)', 'sem_avg_processors', 'Std Dev: Job Size: Per Job', 'Core Count', 2);
    }

    public function getInfo()
    {
        return "The standard error of the average size ".ORGANIZATION_NAME." job in number of cores. <br/>
		<i>Std Err of the Avg: </i> The standard deviation of the sample mean, estimated by the sample estimate of the population standard deviation (sample standard deviation) divided by the square root of the sample size (assuming statistical independence of the values in the sample).";
    }
    public function isVisible()
    {
        return false;
    }
}
