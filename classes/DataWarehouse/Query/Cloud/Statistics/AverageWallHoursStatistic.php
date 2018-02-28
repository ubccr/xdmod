<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 02-20-2018
*
* Summation of Average Wallduration per VM
*/

class AverageWallHoursStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance)
    {
        $job_count_formula = 'number_of_vms';
        parent::__construct('coalesce(sum(jf.wallduration/3600.0)/sum(jf.' . $job_count_formula . '),0)', 'avg_wallduration_hours', 'Wall Hours: Per Job', 'Hour', 2);
    }

    public function getInfo()
    {
        return "The average time, in hours, in which a virtual machine was running.<br/>
            In timeseries view mode, the statistic shows the average wall time per job per
            time period. In aggregate view mode, this statistic is approximate. The
            approximation is accurate when the average job walltime is small compared to
            the aggregation period.<br />
            <i>Wall Time:</i> Wall time is defined as the linear duration between the start and end times of discrete virtual machine runs.";
    }
}
