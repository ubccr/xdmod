<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/*
* @author Rudra Chakraborty
* @date 02-20-2018
*
* Summation of Average Wallduration per VM
*/
class RunningJobCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('coalesce(sum(jf.number_of_vms),0)', 'number_of_vms', 'Number of VMs Running', 'Number of VMs', 0);
    }

    public function getInfo()
    {
        return  "The total number of running " . ORGANIZATION_NAME . " virtual machines.<br/>
        <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.";
    }
    public function isVisible()
    {
        return true;
    }

    /**
     * @see DataWarehouse\Query\Statistic
     */
    public function usesTimePeriodTablesForAggregate()
    {
        return false;
    }
}
