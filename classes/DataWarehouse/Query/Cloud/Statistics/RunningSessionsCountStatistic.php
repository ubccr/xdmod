<?php
namespace DataWarehouse\Query\Cloud\Statistics;

use DataWarehouse\Query\Model\TableField;

/*
* @author Rudra Chakraborty
* @date 05-31-2018
*
* Total of Running Sessions
*/
class RunningSessionsCountStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        $sql = 'COALESCE(SUM(jf.num_sessions_running), 0)';

        if ($query_instance->getQueryType() == 'aggregate') {
            $date_table = $query_instance->getDateTable();
            if ($date_table) {
                $date_id_field = new TableField($date_table, 'id');

                $sql = 'COALESCE(SUM(CASE ' . $date_id_field . ' WHEN ' . $query_instance->getMinDateId() . ' THEN jf.num_sessions_running ELSE jf.num_sessions_started END), 0)';
            }
        }

        parent::__construct(
            $sql,
            'cloud_num_sessions_running',
            'Number of Active Sessions',
            'Number of Sessions',
            0
        );
    }

    public function getLabel() 
    {
        return parent::getLabel(false);
    }

    public function getInfo()
    {
        return  "The total number of sessions on a cloud resource.<br/>
            <b>Session:</b> A session is defined as a discrete run of a virtual machine (VM) on a cloud resource; i.e. any start and stop of a VM. For example, if a single VM is stopped and restarted ten times in a given day, this would be counted as ten sessions for that day.<br/>
            <b>Start:</b> A session start event is defined as the initial creation, resume from pause/suspension, or unshelving of a VM. In the event that no such event has been collected, the first heartbeat event (e.g. a state report) is treated as the start of a new session.<br/>
            <b>Stop:</b> A session stop event is defined as a pause, shelving, suspension, or termination event of a VM. ";
    }
}
