<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 04-17-2018
*
* Memory reserved, weighted by wall hours
*/
class AverageMemoryReservedStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.memory_reserved) / SUM(jf.wallduration), 0)',
            'cloud_avg_memory_reserved',
            'Average Memory Reserved Weighted By Wall Hours',
            'Bytes',
            2
        );
    }

    public function getInfo()
    {
        return 'The average amount of memory (in bytes) reserved by running sessions, weighted by wall hours.<br/>
        <b>Wall Time:</b> The duration between the start and end times of an individual session.<br/>
        <b>Session:</b> A session is defined as a discrete run of a virtual machine (VM) on a cloud resource; i.e. any start and stop of a VM. For example, if a single VM is stopped and restarted ten times in a given day, this would be counted as ten sessions for that day.';
    }
}
