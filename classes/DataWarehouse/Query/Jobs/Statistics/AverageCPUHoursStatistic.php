<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class AverageCPUHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('COALESCE(SUM(jf.cpu_time)/SUM(jf.running_job_count),0)/3600.0', 'avg_cpu_hours', 'CPU Hours: Per Job', 'CPU Hour', 2);
    }

    public function getInfo()
    {
        return 'The average CPU hours (number of CPU cores x wall time hours) per ' . ORGANIZATION_NAME . ' job.<br/>For each job, the CPU usage is aggregated. For example, if a job used 1000 CPUs for one minute, it would be aggregated as 1000 CPU minutes or 16.67 CPU hours.';
    }

    /**
     * @see DataWarehouse\Query\Statistic
     */
    public function usesTimePeriodTablesForAggregate()
    {
        return false;
    }
}
