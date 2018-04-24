<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 04-17-2018
*
* The total core reservation time of virtual machines, in hours
*/
class MemoryReservationStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE((SUM(jf.memory_reserved) / 3600.0) / jf.wallduration,0)',
            'memory_reserved',
            'Average Memory Consumption',
            'Megabytes',
            0
        );
    }

    public function getInfo()
    {
        return 'The average memory reserved by a VM in a given period.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
