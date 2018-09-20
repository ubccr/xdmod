<?php
namespace DataWarehouse\Query\Cloud\Statistics;

use DataWarehouse\Query\Model\TableField;

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
        $sql = 'COALESCE(SUM(jf.wallduration) / SUM(jf.num_sessions_running) / 3600.0, 0)';

        if ($query_instance->getQueryType() == 'aggregate') {
            $date_table = $query_instance->getDateTable();
            if ($date_table) {
                $date_id_field = new TableField($date_table, 'id');

                $sql = 'COALESCE(SUM(jf.wallduration) / SUM(CASE ' . $date_id_field . ' WHEN ' . $query_instance->getMinDateId() . ' THEN jf.num_sessions_running ELSE jf.num_sessions_started END) / 3600.0, 0)';
            }
        }

        parent::__construct(
            $sql,
            'cloud_avg_wallduration_hours',
            'Average Wall Hours per Session',
            'Hours',
            2
        );
    }

    public function getInfo()
    {
        return "The average wall time that a session was running, in hours.<br/>
            <b>Wall Time:</b> The duration between the start and end times of an individual session.";
    }
}
