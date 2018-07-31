<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\Statistics;

use DataWarehouse\Query\Storage\AvgStatistic;

/**
 * Statistic for measuring average physical file system usage.
 */
class PhysicalUsageStatistic extends AvgStatistic
{
    public function __construct($query)
    {
        parent::__construct(
            $query,
            'physical_usage',
            'Physical Usage',
            'Bytes',
            2,
            'Average physical file system usage measured in bytes.'
        );
    }
}
