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
            'Cores Reserved: Weighted by Core Time',
            'Cores',
            0
        );
    }

    public function getInfo()
    {
        return 'An average of the number of cores reserved by running virtual machines weighted by core time.<br/>
                <b>Core Time</b>: The product of the number of cores allocated to a VM and its wall time.';
    }
}
