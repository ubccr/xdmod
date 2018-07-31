<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\Statistics;

use DataWarehouse\Query\Storage\Statistic;

/**
 * Statistic for measuring the number of users with file system usage data.
 */
class UserCountStatistic extends Statistic
{
    public function __construct($query)
    {
        parent::__construct(
            'COUNT(DISTINCT jf.person_id)',
            'user_count',
            'User Count',
            'Number of Users',
            0,
            'Number of users with file system usage data.'
        );
    }
}
