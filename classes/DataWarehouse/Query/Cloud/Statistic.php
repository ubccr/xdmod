<?php
namespace DataWarehouse\Query\Cloud;

/*
* @author Rudra Chakraborty
* @date 2018-02-15
*/
class Statistic extends \DataWarehouse\Query\Statistic
{
    public function getWeightStatName()
    {
        return 'cloud_num_vms_running';
    }
}
