<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 03-27-2018
*
* Least number of cores reserved on active virtual machines
*/
class MinCoresReservedStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(MIN(jf.num_cores),0)',
            'min_num_cores',
            'Minimum Cores Reserved',
            'Core Count',
            0
        );
    }

    public function getInfo()
    {
        return 'The minimum number of cores reserved by active VM instances.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
