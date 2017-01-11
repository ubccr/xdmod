<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* class for calculating the average  cpu hours 
*/

class AverageCPUHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        $job_count_formula = $query_instance->getQueryType() == 'aggregate'?'job_count':'running_job_count';
        parent::__construct('coalesce(sum(jf.cpu_time/3600.0)/sum(jf.'.$job_count_formula.'),0)', 'avg_cpu_hours', 'CPU Hours: Per Job', 'CPU Hour', 2);
    }
    
    public function getInfo()
    {
        return 'The average CPU hours (number of CPU cores x wall time hours) per '.ORGANIZATION_NAME.' job.<br/>For each job, the CPU usage is aggregated. For example, if a job used 1000 CPUs for one minute, it would be aggregated as 1000 CPU minutes or 16.67 CPU hours.';
    }
}
