<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 04-17-2018
*
* Wall duration in hours.
*/
class WallHoursStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.wallduration) / 3600.0, 0)',
            'cloud_wall_time',
            'Wall Hours: Total',
            'Hours',
            0
        );
    }

    public function getInfo()
    {
        return 'The total wall time in which a virtual machine was running, in hours.<br/>
        <b>Wall Time:</b> The linear duration between the start and end times of discrete sessions.';
    }
}
