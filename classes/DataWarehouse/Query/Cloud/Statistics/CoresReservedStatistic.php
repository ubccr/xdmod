<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 02-20-2018
*
* Sum of cores reserved on active virtual machines
*/
class CoresReservedStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.num_cores),0)',
            'num_cores',
            'Number of Cores Reserved',
            'Number of Cores',
            0
        );
    }

    public function getInfo()
    {
        return 'The total number of cores reserved by active VM instances.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
