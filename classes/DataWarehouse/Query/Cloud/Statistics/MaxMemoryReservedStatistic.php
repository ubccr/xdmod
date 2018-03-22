<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 03-22-2018
*
* Greatest amount of memory reserved on active virtual machines
*/
class MaxMemoryReservedStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(MAX(jf.memory_mb),0)',
            'max_memory_mb',
            'Maximum Memory Reserved',
            'Memory in MBs',
            0
        );
    }

    public function getInfo()
    {
        return 'The single highest amount of memory (in megabytes) reserved by active VM instances.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
