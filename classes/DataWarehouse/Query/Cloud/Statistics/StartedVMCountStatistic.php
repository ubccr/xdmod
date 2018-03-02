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
        parent::__construct('coalesce(sum(jf.started_vm_count),0)', 'started_vm_count', 'Number of VMs Started', 'Number of VMs', 0);
    }

    public function getInfo()
    {
        return  "The total number of " . ORGANIZATION_NAME . " VM instances started on a cloud resource.<br/>
        <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.";
    }

    public function isVisible()
    {
        return true;
    }
}
