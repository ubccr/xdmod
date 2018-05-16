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
            'Disk Space Utilized Per Period',
            'Bytes',
            0
        );
    }

    public function getInfo()
    {
        return 'The amount of disk space consumed by running virtual machines over wall duration.<br/>';
    }
}
