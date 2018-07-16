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
        $sql = 'COALESCE(SUM(jf.wallduration/3600.0) / SUM(jf.num_vms_running), 0)';

        if ($query_instance->getQueryType() == 'aggregate') {
            $date_table = $query_instance->getDateTable();
            if ($date_table) {
                $date_id_field = new TableField($date_table, 'id');

                $sql = 'COALESCE(SUM(jf.wallduration/3600.0) / SUM(CASE ' . $date_id_field . ' WHEN ' . $query_instance->getMinDateId() . ' THEN jf.num_vms_running ELSE jf.num_vms_started END), 0)';
            }
        }

        $vm_count_formula = $query_instance->getQueryType() == 'aggregate' ? 'num_vms_started' : 'num_vms_running';
        parent::__construct(
            $sql,
            'cloud_avg_wallduration_hours',
            'Average Wall Hours per VM',
            'Hours',
            2
        );
    }

    public function getInfo()
    {
        return "The average time a virtual machine was running, in hours.<br/> 
            <b>Wall Time:</b> The linear duration between the start and end times of discrete virtual machine runs.";
    }
}
