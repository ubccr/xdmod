<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\Statistics;

use DataWarehouse\Query\Storage\SemStatistic;

/**
 * Statistic for physical usage standard error of the mean.
 */
class SemPhysicalUsageStatistic extends SemStatistic
{
    public function __construct($query)
    {
        parent::__construct(
            'physical_usage',
            'Physical Usage: Standard Error of the Mean',
            'Bytes',
            2,
            'The standard error of the average physical usage.'
        );
    }
}
