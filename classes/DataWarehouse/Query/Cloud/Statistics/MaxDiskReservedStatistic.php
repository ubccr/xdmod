<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 03-22-2018
*
* Greatest amount of disk space reserved on active virtual machines
*/
class MaxDiskReservedStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(MAX(jf.disk_gb),0)',
            'max_disk_gb',
            'Maximum Disk Space Reserved',
            'Disk Space in GBs',
            0
        );
    }

    public function getInfo()
    {
        return 'The single highest amount of disk space (in gigabytes) reserved by active VM instances.<br/>
            <i>VM Instance: </i>An individual virtual machine (VM) spun up within a cloud.';
    }
}
