<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class AverageNodeHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.node_time)/SUM(jf.running_job_count),0)/3600.0',
            'avg_node_hours',
            'Node Hours: Per Job',
            'Node Hour',
            2
        );
    }

    public function getInfo()
    {
        return 'The average node hours (number of nodes x wall time hours) per ' . ORGANIZATION_NAME . ' job.';
    }

    /**
     * @see DataWarehouse\Query\Statistic
     */
    public function usesTimePeriodTablesForAggregate()
    {
        return false;
    }
}
