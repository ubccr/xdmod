<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\Statistics;

use DataWarehouse\Query\Storage\AvgStatistic;

/**
 * Statistic for measuring average file system soft thresholds.
 */
class SoftThresholdStatistic extends AvgStatistic
{
    public function __construct($query)
    {
        parent::__construct(
            $query,
            'soft_threshold',
            'Quota: Soft Threshold',
            'Bytes',
            2,
            'Average file system quota soft threshold measured in bytes.'
        );
    }
}
