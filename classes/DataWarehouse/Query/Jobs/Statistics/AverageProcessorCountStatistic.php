<?php
namespace DataWarehouse\Query\Jobs\Statistics;

use DataWarehouse\Query\Model\TableField;

class AverageProcessorCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
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

        parent::__construct(
            "COALESCE(SUM(jf.processor_count * $job_count) / SUM($job_count), 0)",
            'avg_processors',
            'Job Size: Per Job',
            'Core Count',
            1
        );
    }

    public function getInfo()
    {
        return  'The average job size per  ' . ORGANIZATION_NAME . ' job.<br><i>Job Size: </i>The number of processor cores used by a (parallel) job.';
    }
}
