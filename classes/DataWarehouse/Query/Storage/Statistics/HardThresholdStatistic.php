<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\Statistics;

use DataWarehouse\Query\Storage\AvgStatistic;

/**
 * Statistic for measuring average file system hard thresholds.
 */
class HardThresholdStatistic extends AvgStatistic
{
    public function __construct($query)
    {
        parent::__construct(
            $query,
            'hard_threshold',
            'Quota: Hard Threshold',
            'Bytes',
            2,
            'Average file system quota hard threshold measured in bytes.'
        );
    }
}
