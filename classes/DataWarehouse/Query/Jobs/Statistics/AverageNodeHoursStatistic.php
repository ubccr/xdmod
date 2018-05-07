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
        parent::__construct(
            'coalesce(sum(jf.node_time)/sum(jf.running_job_count),0)/3600.0',
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

    /**
     * @see DataWarehouse\Query\Statistic
     */
    public function usesTimePeriodTablesForAggregate()
    {
        return false;
    }
}
