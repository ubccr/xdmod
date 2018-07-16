<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 02-21-2018
*
* Summation of Average Wallduration per VM
*/
class StartedVMCountStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('coalesce(sum(jf.num_vms_started),0)', 'cloud_num_vms_started', 'Number of VMs Started', 'Number of VMs', 0);
    }

    public function getInfo()
    {
        return  "The total number of virtual machines that were started or resumed running on a cloud resource.<br/>";
    }

    public function isVisible()
    {
        return true;
    }
}
