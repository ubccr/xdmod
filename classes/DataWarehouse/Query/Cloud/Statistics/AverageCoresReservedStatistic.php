<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 05-03-2018
*
* Sum of cores reserved on active virtual machines
*/
class AverageCoresReservedStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        $vm_count_formula = $query_instance->getQueryType() == 'aggregate' ? 'num_vms_ended' : 'num_vms_running';
        parent::__construct(
            'COALESCE(SUM(jf.num_cores) / SUM(jf.' . $vm_count_formula . '),0)',
            'num_cores',
            'Cores Reserved: Average',
            'Cores',
            0
        );
    }

    public function getInfo()
    {
        return 'The average number of cores reserved by virtual machines.<br/>.';
    }
}
