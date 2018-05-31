<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 05-03-2018
*
* Sum of cores reserved on active virtual machines
*/
class AverageCoresAllocatedStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.num_cores) / SUM(jf.num_vms_running),0)',
            'avg_num_cores',
            'Average Cores Allocated Per VM',
            'Cores',
            2
        );
    }

    public function getInfo()
    {
        return 'The average number of cores allocated to running virtual machines.<br/>.';
    }
}
