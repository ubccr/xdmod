<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 05-31-2018
*
* Total of Running VMs
*/
class RunningVMCountStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.num_vms_running) ,0)',
            'num_vms_running',
            'Number of VMs Running',
            'Number of VMs',
            0
        );
    }

    public function getInfo()
    {
        return  "The total number of running virtual machines on a cloud resource.<br/>";
    }

    public function isVisible()
    {
        return true;
    }

    public function usesTimePeriodTablesForAggregate()
    {
        return false;
    }
}
