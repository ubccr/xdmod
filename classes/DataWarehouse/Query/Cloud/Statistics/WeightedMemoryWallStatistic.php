<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 03-21-2018
*
* Memory Reserved weighted by wall duration
*/
class WeightedMemoryWallStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.memory_mb * (jf.wallduration/3600.0)),0)',
            'weighted_memory_mb',
            'Weighted Amount of Memory Reserved',
            'Memory in MBs',
            0
        );
    }

    public function getInfo()
    {
        return 'The total amount of memory (in megabytes) reserved by active VM instances, weighted by wallhours.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
