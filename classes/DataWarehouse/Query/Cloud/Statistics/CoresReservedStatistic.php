<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 03-21-2018
*
* Sum of cores reserved on active virtual machines
*/
class CoresReservedStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.num_cores),0)',
            'num_cores',
            'Cores: Total',
            'Cores',
            0
        );
    }

    public function getInfo()
    {
        return 'The total number of cores assigned to running virtual machines.<br/>';
    }
}
