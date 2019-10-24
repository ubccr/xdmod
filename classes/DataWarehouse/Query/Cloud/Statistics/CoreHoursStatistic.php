<?php
namespace DataWarehouse\Query\Cloud\Statistics;

class CoreHoursStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.core_time) / 3600.0, 0)',
            'cloud_core_time',
            'Core Hours: Total',
            'Hours',
            0
        );
    }

    public function getInfo()
    {
        return 'The total number of Core Hours consumed by running sessions.<br/><b>Core Hours</b>: The product of the number of cores assigned to a VM and its wall time, in hours.<br/><b>Session:</b> A session is defined as a discrete run of a virtual machine (VM) on a cloud resource; i.e. any start and stop of a VM. For example, if a single VM is stopped and restarted ten times in a given day, this would be counted as ten sessions for that day.';
    }
}
