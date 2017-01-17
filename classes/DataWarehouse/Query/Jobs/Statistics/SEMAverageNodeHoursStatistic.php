<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/* 
* @author Amin Ghadersohi
* @date 2013-8-29
*
* class for calculating the average  cpu hours 
*/

class SEMAverageNodeHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        $job_count_formula = $query_instance->getQueryType() == 'aggregate'?'job_count':'running_job_count';
        parent::__construct(
            'coalesce(sqrt((sum(jf.sum_node_time_squared)/sum(jf.'.$job_count_formula.'))-pow(sum(jf.node_time)/sum(jf.'.$job_count_formula.'),2))/sqrt(sum(jf.'.$job_count_formula.')),0)/3600.0',
            'sem_avg_node_hours',
            'Std Dev: Node Hours: Per Job',
            'Node Hour',
            2
        );
    }

    public function getInfo()
    {
        return " The standard error of the average node hours by each ".ORGANIZATION_NAME." job.<br/>
		<i>Std Err of the Avg: </i> The standard deviation of the sample mean, estimated by the sample estimate of the population standard deviation (sample standard deviation) divided by the square root of the sample size (assuming statistical independence of the values in the sample).";
    }
    public function isVisible()
    {
        return false;
    }
}
