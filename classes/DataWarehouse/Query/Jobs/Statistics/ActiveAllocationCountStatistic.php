<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class ActiveAllocationCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('COUNT(DISTINCT(jf.account_id))', 'active_allocation_count', 'Number of Allocations: Active', 'Number of Allocations', 0);
    }

    public function getInfo()
    {
        return 'The total number of funded projects that used ' . ORGANIZATION_NAME . ' resources.';
    }
}
