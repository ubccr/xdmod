<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 04-17-2018
*
* The total core reservation time of virtual machines, in hours
*/
class CoreHoursStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.core_time) / 3600.0 ,0)',
            'core_time',
            'CPU Hours: Total',
            'Hours',
            0
        );
    }

    public function getInfo()
    {
        return 'The total number of CPU Hours consumed by running virtual machines.<br/>
        <b>CPU Hours</b>: The product of the number of CPUs assigned to a VM and its wall time, in hours.';
    }
}
