<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/**
 * @author Amin Ghadersohi
 * @date 2011-Jul-25
 *
 * class for calculating the statistics pertaining to a job query
 */
class ActiveResourceCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COUNT(DISTINCT(jf.task_resource_id))',
            'active_resource_count',
            'Number of Resources: Active',
            'Number of Resources',
            0
        );
    }

    public function getInfo()
    {
        return 'The total number of active ' . ORGANIZATION_NAME . ' resources.';
    }
}
