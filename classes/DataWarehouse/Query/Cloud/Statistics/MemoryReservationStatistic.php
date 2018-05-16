<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 04-17-2018
*
* The total core reservation time of virtual machines, in hours
*/
class MemoryReservationStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.memory_reserved) / SUM(jf.wallduration),0)',
            'memory_reserved',
            'Memory Utilized Per Period',
            'Bytes',
            0
        );
    }

    public function getInfo()
    {
        return 'The quotient of memory reserved by running virtual machines over wall duration.<br/>';
    }
}
