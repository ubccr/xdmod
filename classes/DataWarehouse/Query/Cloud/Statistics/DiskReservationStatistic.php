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
            'disk_reserved',
            'Disk Space Reserved',
            'Bytes',
            0
        );
    }

    public function getInfo()
    {
        return 'The amount of disk space in bytes reserved by virtual machines.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
