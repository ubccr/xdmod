<?php
namespace DataWarehouse\Query\Cloud;

class Statistic extends \DataWarehouse\Query\Statistic
{
    public function getWeightStatName()
    {
        return 'cloud_num_sessions_running';
    }
}
