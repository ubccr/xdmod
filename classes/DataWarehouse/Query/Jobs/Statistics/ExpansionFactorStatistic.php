<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* class for calculating the  expansion factor
*/

class ExpansionFactorStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'coalesce(sum(jf.sum_weighted_expansion_factor)/sum(jf.sum_job_weights),0)',
            'expansion_factor',
            'User Expansion Factor',
            'User Expansion Factor',
            1
        );
    }

    public function getInfo()
    {
        return  "Gauging ".ORGANIZATION_NAME." job-turnaround time, it measures the ratio of wait time and the total time from submission to end of execution.<br/>
		<i>User Expansion Factor = ((wait duration + wall duration) / wall duration). </i>";
    }
}
