<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 02-20-2018
*
* Summation of Average Wallduration per VM
*/
class VMCountStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.num_vms_ended),0)',
            'cloud_num_vms_ended',
            'Number of VMs Ended',
            'Number of VMs',
            0
        );
    }

    public function getInfo()
    {
        return 'The total number of running virtual machines that were shelved or terminated on a cloud resource.<br/>';
    }
}
