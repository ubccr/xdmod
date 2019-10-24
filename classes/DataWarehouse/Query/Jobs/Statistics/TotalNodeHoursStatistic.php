<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class TotalNodeHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('COALESCE(SUM(jf.node_time),0)/3600.0', 'total_node_hours', 'Node Hours: Total', 'Node Hour');
    }

    public function getInfo()
    {
        return 'The total node hours (number of nodes x wall time hours) used by ' . ORGANIZATION_NAME . ' jobs.';
    }
}
