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
            'cloud_core_time',
            'Core Hours: Total',
            'Hours',
            0
        );
    }

    public function getInfo()
    {
        return 'The total number of Core Hours consumed by running virtual machines.<br/>
        <b>Core Hours</b>: The product of the number of cores assigned to a VM and its wall time, in hours.';
    }
}
