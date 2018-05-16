<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 05-01-2018
*
* Average Core Time in Hours.
*/
class AverageCoreHoursStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        $vm_count_formula = $query_instance->getQueryType() == 'aggregate' ? 'num_vms_ended' : 'num_vms_running';
        parent::__construct(
            'COALESCE((SUM(jf.core_time) / 3600.0) / SUM(jf.' . $vm_count_formula . '),0)',
            'avg_core_time',
            'Average Core Hours Per VM',
            'Hours',
            0
        );
    }

    public function getInfo()
    {
        return 'The average core hours consumed by running virtual machines.<br/><i>Only approximate in aggregate view.</i><br/>
            <b>Core Time</b>: The product of the number of cores consumed by a VM and its wall time, in hours.';
    }
}
