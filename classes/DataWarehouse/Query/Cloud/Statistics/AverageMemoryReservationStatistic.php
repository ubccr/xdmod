<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 04-17-2018
*
* The total core reservation time of virtual machines, in hours
*/
class AverageMemoryReservationStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        $vm_count_formula = $query_instance->getQueryType() == 'aggregate' ? 'num_vms_ended' : 'num_vms_running';
        parent::__construct(
            'COALESCE((SUM(jf.memory_reserved) / SUM(jf.wallduration)) / SUM(jf.' . $vm_count_formula . '),0)',
            'avg_memory_reserved',
            'Memory Reserved: Average',
            'Bytes',
            0
        );
    }

    public function getInfo()
    {
        return 'The amount of memory in bytes reserved by virtual machines.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
