<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 03-21-2018
*
* Sum of memory reserved on active virtual machines
*/
class MemoryReservedStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.memory_mb),0)',
            'memory_mb',
            'Amount of Memory Reserved',
            'Memory Reserved',
            0
        );
    }

    public function getInfo()
    {
        return 'The amount of memory (in megabytes) reserved by active VM instances.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
