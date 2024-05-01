<?php
namespace DataWarehouse\Query\ResourceSpecifications;

class Statistic extends \DataWarehouse\Query\Statistic
{
    public function getWeightStatName()
    {
        return 'cpu_hours_available';
    }
}
