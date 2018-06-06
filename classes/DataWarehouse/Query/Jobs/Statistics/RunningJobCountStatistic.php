<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/*
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* class for calculating the statistics pertaining to a job query
*/

use DataWarehouse\Query\Model\TableField;

class RunningJobCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        $stmt = 'COALESCE(SUM(jf.running_job_count), 0)';

        if ($query_instance->getQueryType() == 'aggregate') {
            $date_table = $query_instance->getDateTable();
            if ($date_table) {
                $date_id_field = new TableField($date_table, 'id');

                $stmt = 'COALESCE(SUM(CASE ' . $date_id_field . ' WHEN ' . $query_instance->getMinDateId() . ' THEN jf.running_job_count ELSE jf.started_job_count END), 0)';
            }
        }

        parent::__construct($stmt, 'running_job_count', 'Number of Jobs Running', 'Number of Jobs', 0);
    }

    public function getInfo()
    {
        return  "The total number of running ".ORGANIZATION_NAME." jobs.<br/>
        <i>Job: </i>A scheduled process for a computer resource in a batch processing environment.";
    }
    public function isVisible()
    {
        return true;
    }
}
