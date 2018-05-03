<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 02-20-2018
*
* Summation of Average Wallduration per VM
*/

class AverageWallHoursStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance)
    {
        $vm_count_formula = $query_instance->getQueryType() == 'aggregate' ? 'num_vms_ended' : 'num_vms_running';
        parent::__construct(
            'coalesce(sum(jf.wallduration/3600.0)/sum(jf.' . $vm_count_formula . '),0)',
            'avg_wallduration_hours',
            'Average Wall Hours per VM',
            'Hours',
            0
        );
    }

    public function getInfo()
    {
        return "The average time in hours in which a virtual machine was running.<br/> <i>Only approximate in aggregate view</i>.<br />
            <i>Wall Time:</i> Wall time is defined as the linear duration between the start and end times of discrete virtual machine runs.";
    }
}
