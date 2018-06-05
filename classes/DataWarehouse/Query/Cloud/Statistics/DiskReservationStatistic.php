<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 04-17-2018
*
* The total core reservation time of virtual machines, in hours
*/
class DiskReservationStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.disk_reserved) / SUM(jf.wallduration),0)',
            'avg_disk_reserved',
            'Average Disk Reserved Weighted By Wall Hours',
            'Bytes',
            2
        );
    }

    public function getInfo()
    {
        return 'The amount of disk space reserved by running virtual machines over wall time.<br/>
            <i>Wall Time:</i> The linear duration between the start and end times of discrete virtual machine runs.';
    }
}
