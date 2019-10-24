<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class ActiveUserCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('COUNT(DISTINCT(jf.person_id))', 'active_person_count', 'Number of Users: Active', 'Number of Users', 0);
    }

    public function getInfo()
    {
        return 'The total number of users that used ' . ORGANIZATION_NAME . ' resources.';
    }
}
