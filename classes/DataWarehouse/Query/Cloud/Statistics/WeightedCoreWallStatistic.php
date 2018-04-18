<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 03-27-2018
*
* Reserved Core Hours
*/
class WeightedCoreWallStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.num_cores * jf.core_time) / SUM(jf.core_time),0)',
            'weighted_num_cores',
            'Weighted Number of Cores Reserved',
            'Core Count',
            0
        );
    }

    public function getInfo()
    {
        return 'Weighted average core size of VM instances, weighted by core time.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
