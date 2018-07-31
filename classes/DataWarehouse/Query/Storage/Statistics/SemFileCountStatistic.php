<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\Statistics;

use DataWarehouse\Query\Storage\SemStatistic;

/**
 * Statistic for file count standard error of the mean.
 */
class SemFileCountStatistic extends SemStatistic
{
    public function __construct($query)
    {
        parent::__construct(
            'file_count',
            'File Count: Standard Error of the Mean',
            'Number of files',
            2,
            'The standard error of the average number of files.'
        );
    }
}
