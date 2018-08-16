<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\Statistics;

use DataWarehouse\Query\Storage\AvgStatistic;

/**
 * Statistic for measuring average file counts.
 */
class FileCountStatistic extends AvgStatistic
{
    public function __construct($query)
    {
        parent::__construct(
            $query,
            'file_count',
            'File Count',
            'Number of files',
            2,
            'Average number of files.'
        );
    }
}
