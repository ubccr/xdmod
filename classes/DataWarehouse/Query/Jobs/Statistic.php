<?php
namespace DataWarehouse\Query\Jobs;

class Statistic extends \DataWarehouse\Query\Statistic
{
    public function getWeightStatName()
    {
        return 'running_job_count';
    }
}
