<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 03-22-2018
*
* Sum of cores reserved on active virtual machines, weighted
*/
class WeightedCoreWallStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        $vm_count_formula = $query_instance->getQueryType() == 'aggregate' ? 'num_vms_ended' : 'num_vms_running';

        parent::__construct(
            'COALESCE(SUM(jf.num_cores * (jf.wallduration/3600.0)/sum(jf.' . $vm_count_formula . ')) / SUM(jf.num_cores),0)',
            'weighted_num_cores',
            'Weighted Number of Cores Reserved',
            'Core Count',
            0
        );
    }

    public function getInfo()
    {
        return 'The total number of cores reserved by active VM instances, weighted by wall duration.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
