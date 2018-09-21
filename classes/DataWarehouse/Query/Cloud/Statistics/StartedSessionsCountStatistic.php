<?php
namespace DataWarehouse\Query\Cloud\Statistics;

/*
* @author Rudra Chakraborty
* @date 02-21-2018
*
* Summation of Average Wallduration per VM
*/
class StartedSessionsCountStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('coalesce(SUM(jf.num_sessions_started), 0)', 'cloud_num_sessions_started', 'Number of Sessions Started', 'Number of Sessions', 0);
    }

    public function getInfo()
    {
        return  "The total number of sessions started on a cloud resource. A session begins when a VM is created, unshelved, or resumes running on a cloud resource.<br/>
        <b>Session:</b> A session is defined as a discrete run of a virtual machine (VM) on a cloud resource; i.e. any start and stop of a virtual machine. For example, if a single VM is stopped and restarted ten times in a given day, this would be counted as ten sessions for that day.<br/>
        <b>Start:</b> A session start event is defined as the initial creation, resume from pause/suspension, or unshelving of a VM. In the event that no such event has been collected, the first heartbeat event (e.g. a state report) is treated as the start of a new session.<br/>
        <b>Stop:</b> A session stop event is defined as a pause, shelving, suspension, or termination event of a VM.";
    }
}
