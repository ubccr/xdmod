<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 05-03-2018
*
* The total core reservation time of virtual machines, in hours
*/
class AverageDiskReservationStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        $vm_count_formula = $query_instance->getQueryType() == 'aggregate' ? 'num_vms_ended' : 'num_vms_running';
        parent::__construct(
            'COALESCE((SUM(jf.disk_reserved) / SUM(jf.wallduration)) / SUM(jf.' . $vm_count_formula . '),0)',
            'avg_disk_reserved',
            'Disk Space Reserved: Average',
            'Bytes',
            0
        );
    }

    public function getInfo()
    {
        return 'The average amount of disk space in bytes reserved by virtual machines.<br/>';
    }
}
