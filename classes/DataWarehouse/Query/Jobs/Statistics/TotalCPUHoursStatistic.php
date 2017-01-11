<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* class for calculating the total cpu hours 
*/

class TotalCPUHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('coalesce(sum(jf.cpu_time/3600.0),0)', 'total_cpu_hours', 'CPU Hours: Total', 'CPU Hour');
    }

    public function getInfo()
    {
        return 'The total CPU hours (number of CPU cores x wall time hours) used by '.ORGANIZATION_NAME.' jobs.<br/>For each job, the CPU usage is aggregated. For example, if a job used 1000 CPUs for one minute, it would be aggregated as 1000 CPU minutes or 16.67 CPU hours.';
    }
}
