<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\Statistics;

use DataWarehouse\Query\Storage\AvgStatistic;

/**
 * Statistic for measuring average logical file system usage.
 */
class LogicalUsageStatistic extends AvgStatistic
{
    public function __construct($query)
    {
        parent::__construct(
            $query,
            'logical_usage',
            'Logical Usage',
            'Bytes',
            2,
            'Average logical file system usage measured in bytes.'
        );
    }
}
