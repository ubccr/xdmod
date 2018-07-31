<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\Statistics;

use DataWarehouse\Query\Storage\SemStatistic;

/**
 * Statistic for logical usage standard error of the mean.
 */
class SemLogicalUsageStatistic extends SemStatistic
{
    public function __construct($query)
    {
        parent::__construct(
            'logical_usage',
            'Logical Usage: Standard Error of the Mean',
            'Bytes',
            2,
            'The standard error of the average logical usage.'
        );
    }
}
