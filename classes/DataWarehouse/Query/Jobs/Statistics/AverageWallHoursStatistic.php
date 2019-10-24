<?php
namespace DataWarehouse\Query\Jobs\Statistics;

use DataWarehouse\Query\Model\TableField;

class AverageWallHoursStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance)
    {
        $job_count = 'jf.running_job_count';
        if ($query_instance->getQueryType() == 'aggregate') {
            $date_table = $query_instance->getDateTable();
            if ($date_table) {
                $date_id_field = new TableField($date_table, 'id');
                $job_count = 'CASE ' . $date_id_field . ' WHEN ' . $query_instance->getMinDateId() . ' THEN jf.running_job_count ELSE jf.started_job_count END';
            }
        }
        parent::__construct('COALESCE(SUM(jf.wallduration)/SUM(' . $job_count . '),0)/3600.0', 'avg_wallduration_hours', 'Wall Hours: Per Job', 'Hour', 2);
    }

    public function getInfo()
    {
        return 'The average time, in hours, a job takes to execute.<br/>In timeseries view mode, the statistic shows the average wall time per job per time period. In aggregate view mode the statistic only includes the job wall hours between the defined time range. The wall hours outside the time range are not included in the calculation.<br /> <i>Wall Time:</i> Wall time is defined as the linear time between start and end time of execution for a particular job.';
    }
}
