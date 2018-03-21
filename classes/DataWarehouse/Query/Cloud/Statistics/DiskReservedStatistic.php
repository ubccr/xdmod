<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 03-21-2018
*
* Sum of disk space reserved on active virtual machines
*/
class DiskReservedStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.disk_gb),0)',
            'disk_gb',
            'Amount of Disk Space Reserved',
            'Disk Space',
            0
        );
    }

    public function getInfo()
    {
        return 'The amount of disk space (in gigabytes) reserved by active VM instances.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
