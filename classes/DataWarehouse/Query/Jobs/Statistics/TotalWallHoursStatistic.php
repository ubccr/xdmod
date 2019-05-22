<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class TotalWallHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('COALESCE(SUM(jf.wallduration),0)/3600.0', 'total_wallduration_hours', 'Wall Hours: Total', 'Hour');
    }

    public function getInfo()
    {
        return 'The total time, in hours, ' . ORGANIZATION_NAME . ' jobs took to execute.<br/><i>Wall Time:</i> Wall time is defined as the linear time between start and end time of execution for a particular job.';
    }
}
