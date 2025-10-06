<?php
namespace DataWarehouse\Query\Cloud;

use \DataWarehouse\Query\Model\Table;
use \DataWarehouse\Query\Model\TableField;
use \DataWarehouse\Query\Model\FormulaField;
use \DataWarehouse\Query\Model\WhereCondition;
use \DataWarehouse\Query\Model\Schema;
use \DataWarehouse\Query\Query;
use CCR\DB;

/*
 * @author Greg Dean <gmdean@buffalo.edu>
 *
 * Helper class that retreives sessions and volume events for VM's
 */

class JobTimeseries
{

    /**
     * The database connection that is used by this class to execute queries.
     *
     * @var DB\iDatabase
     */
    protected $db;

    public function __construct() {
        $this->db = DB::factory('datawarehouse');
    }

    public function get($instance_id) {
        return array('series' => [
          'events' => $this->getVolumeEvents($instance_id),
          'vmstates' => $this->getInstanceStates($instance_id)
        ], 'schema' => []);
    }

    /**
     * @param $instance_id ID of the instance we want sessions for
     *
     * Get active and inactive sessions for an instance. Sesssion that are started
     * with the following event types are retrieved:
     *  2 => Start
     *  4 => Stop
     *  6 => Terminate
     *  8 => Resume
     *  16 => State report
     *  17 => Suspend
     *  19 => Shelve
     *  20 => Unshelve
     *  45 => Power Off
     *  57 => Unpause
     *  55 => Pause
     *  59 => Power On
     *  61 => Unsuspend
     *
     * The ids come from the modw_cloud.event_type table.
     *
     * @return array
     */
    private function getInstanceStates($instance_id)
    {
        $query = "SELECT
                      i.provider_identifier,
                      sr.instance_id,
                      sr.start_time_ts,
                      sr.start_time,
                      sr.end_time_ts,
                      sr.end_time,
                      sr.start_event_type_id,
                      sr.end_event_type_id
                  FROM
                      modw_cloud.session_records AS sr
                  LEFT JOIN
                      modw_cloud.instance AS i ON i.instance_id = sr.instance_id AND i.resource_id = sr.resource_id
                  WHERE
                      i.instance_id = :instance_id
                  AND
                      sr.start_event_type_id IN (2,4,6,8,16,17,19,20,45,57,55,59,61)
                  ORDER BY
                      sr.start_time_ts ASC";

        return $this->db->query($query, [':instance_id' => $instance_id]);
    }

    /**
     * @param $instance_id ID of the instance we want to get events for
     *
     * Get volume attach and detach events for an instance to show on a timeline.
     *
     * @return array
     */
    private function getVolumeEvents($instance_id)
    {
        $query = "SELECT
                      ev.event_id as Event_ID,
                      et.display as Event,
                      FROM_UNIXTIME(ev.event_time_ts, '%Y-%m-%d %H:%i:%s') as Event_Time,
                      p.long_name as Person,
                      sa.username as Username,
                      a.provider_identifier as Volume_ID,
                      a.size as Size
                  FROM
                      modw_cloud.event as ev
                  LEFT JOIN
                      modw_cloud.event_asset as ea on ea.event_id = ev.event_id and ea.resource_id = ev.resource_id
                  LEFT JOIN
                      modw_cloud.asset as a on a.asset_id = ea.asset_id and a.resource_id = ea.resource_id AND a.asset_type_id = 1
                  LEFT JOIN
                      modw_cloud.event_type as et on et.event_type_id = ev.event_type_id
                  LEFT JOIN
                      modw.person as p on p.id = ev.person_id
                  LEFT JOIN
	                    modw.systemaccount as sa on sa.id = ev.systemaccount_id
                  WHERE
                      ev.event_type_id IN (10,12)
                  and
                      ev.instance_id = :instance_id
                  ORDER BY
                      ev.event_time_ts ASC";

        return $this->db->query($query, [':instance_id' => $instance_id]);
    }
}
