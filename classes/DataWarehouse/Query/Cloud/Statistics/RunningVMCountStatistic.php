<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 02-20-2018
*
* Summation of Average Wallduration per VM
*/
class RunningVMCountStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('coalesce(sum(jf.number_of_vms),0)', 'number_of_vms', 'Number of VMs Running',
            'Number of VMs', 0);
    }

    public function getInfo()
    {
        return  "The total number of running " . ORGANIZATION_NAME . " virtual machines.<br/> <i>VM Instance:
            </i>An individual virtual machine (VM) spun up within a cloud.";
    }

    public function isVisible()
    {
        return true;
    }
}
