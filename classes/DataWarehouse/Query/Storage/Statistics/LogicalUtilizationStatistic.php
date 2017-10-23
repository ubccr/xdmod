<?php
/**
 * @package OpenXdmod\Storage
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\Statistics;

use DataWarehouse\Query\Storage\Statistic;

/**
 * Statistic for measuring average logical file system usage.
 */
class LogicalUtilizationStatistic extends Statistic
{
    public function __construct($query)
    {
        parent::__construct(
            '100 * COALESCE(SUM(jf.avg_logical_usage) / SUM(jf.avg_soft_threshold), 0)',
            'avg_logical_utilization',
            'Quota Utilization: Logical',
            '%',
            2,
            'Average logical file system usage as a percentage of the quota soft threshold.'
        );
    }
}
