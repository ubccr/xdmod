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
            'COALESCE(SUM(jf.num_cores * (jf.wallduration/3600.0)),0)',
            'weighted_num_cores',
            'Weighted Cores Reserved',
            'Core Hours',
            0
        );
    }

    public function getInfo()
    {
        return 'Core hours reserved by active VM instances; derived through the summation of cores by wallhours.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
