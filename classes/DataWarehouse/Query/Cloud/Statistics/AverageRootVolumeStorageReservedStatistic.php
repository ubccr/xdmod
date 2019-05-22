<?php
namespace DataWarehouse\Query\Cloud\Statistics;

class AverageRootVolumeStorageReservedStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.rv_storage_reserved) / SUM(jf.wallduration), 0)',
            'cloud_avg_rv_storage_reserved',
            'Average Root Volume Storage Reserved Weighted By Wall Hours',
            'Bytes',
            2
        );
    }

    public function getInfo()
    {
        return 'The average amount of root volume storage space (in bytes) reserved by running sessions, weighted by wall hours.<br/><b>Wall Time:</b> The duration between the start and end times of an individual session.<br/><b>Session:</b> A session is defined as a discrete run of a virtual machine (VM) on a cloud resource; i.e. any start and stop of a VM. For example, if a single VM is stopped and restarted ten times in a given day, this would be counted as ten sessions for that day.';
    }
}
