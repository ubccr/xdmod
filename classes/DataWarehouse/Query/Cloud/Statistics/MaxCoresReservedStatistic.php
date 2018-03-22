<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 03-22-2018
*
* Greatest number of cores reserved on active virtual machines
*/
class MaxCoresReservedStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(MAX(jf.num_cores),0)',
            'max_num_cores',
            'Maximum Cores Reserved',
            'Core Count',
            0
        );
    }

    public function getInfo()
    {
        return 'The single highest number of cores reserved by active VM instances.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
