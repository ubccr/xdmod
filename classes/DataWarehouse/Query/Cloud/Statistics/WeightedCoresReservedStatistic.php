<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 05-31-2018
*
* Average Cores Reserved Weighted By Wall Hours
*/
class WeightedCoresReservedStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.core_time) / SUM(jf.wallduration),0)',
            'cloud_avg_cores_reserved',
            'Average Cores Reserved Weighted By Wall Hours',
            'Cores',
            2
        );
    }

    public function getInfo()
    {
        return 'The average number of cores assigned to running virtual machines, weighted by wall hours.<br/>
            <b>Core Hours</b>: The product of the number of cores assigned to a VM and its wall time, in hours.<br/>
            <b>Wall Time:</b> The linear duration between the start and end times of discrete virtual machine runs.';
    }
}
