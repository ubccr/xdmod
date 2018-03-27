<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 03-26-2018
*
* Least amount of memory reserved on active virtual machines
*/
class MinMemoryReservedStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(MIN(jf.memory_mb),0)',
            'min_memory_mb',
            'Minimum Memory Reserved',
            'Memory in MBs',
            0
        );
    }

    public function getInfo()
    {
        return 'The least amount of memory (in megabytes) reserved by active VM instances.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
