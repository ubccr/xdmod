<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 04-17-2018
*
* The total core reservation time of virtual machines, in hours
*/
class AverageStorageReservedStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.disk_reserved) / SUM(jf.wallduration),0)',
            'avg_disk_reserved',
            'Average Storage Reserved Weighted By Wall Hours',
            'Bytes',
            2
        );
    }

    public function getInfo()
    {
        return 'The average amount of disk space reserved by running virtual machines, weighted by wall hours.<br/>
            <b>Wall Time:</b> The linear duration between the start and end times of discrete virtual machine runs.';
    }
}
