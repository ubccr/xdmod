<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/*
* @author Amin Ghadersohi
* @date 2013-8-29
*
* class for calculating the average node hours
*/

class AverageNodeHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        $job_count_formula = $query_instance->getQueryType() == 'aggregate'?'job_count':'running_job_count';
        parent::__construct(
            'coalesce(sum(jf.node_time/3600.0)/sum(jf.'.$job_count_formula.'),0)',
            'avg_node_hours',
            'Node Hours: Per Job',
            'Node Hour',
            2
        );
    }

    public function getInfo()
    {
        return 'The average node hours (number of nodes x wall time hours) per '.ORGANIZATION_NAME.' job.';
    }
}
