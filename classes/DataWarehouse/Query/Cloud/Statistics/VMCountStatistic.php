<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/*
* @author Rudra Chakraborty
* @date 02-20-2018
*
* Summation of Average Wallduration per VM
*/
class VMCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.ended_vm_count),0)',
            'ended_vm_count',
            'Number of VMs Ended',
            'Number of VMs',
            0
        );
    }

    public function getInfo()
    {
        return 'The total number of ' . ORGANIZATION_NAME . ' VM instances that ended within the selected duration.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
