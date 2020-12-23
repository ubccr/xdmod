<?php
/* ==========================================================================================
* This class simulates a Finite State Machine to reconstruct discrete run cycles of virtual machines.
* It first grabs a list of raw events, which must then be sorted by instance and time to ensure
* proper sequential reconstruction. It then iterates over the sorted list and generates event pairs
* by taking the first start event it finds for a unique instance id and pairing it with the next stop event.
*
* If no stop event is found, either the start event is treated as the stop, or a default stop time may be provided
* by specifiying an "end_time" variable in the ETL Overseer at the point of ingestion.
*
* @author Rudra Chakraborty <rudracha@buffalo.edu>
* @date 2018-06-29
*/

namespace ETL\Ingestor;

use ETL\aOptions;
use ETL\iAction;
use ETL\aAction;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;

use Log;

class CloudStateReconstructorTransformIngestor extends pdoIngestor implements iAction
{
    // Discrete Event Types
    // Start events
    const REQUEST_START = 1;
    const REQUEST_RESUME = 7;
    const UNPAUSE_END = 57;
    const UNPAUSE_START = 56;
    const POWER_ON_START = 58;
    const UNSUSPEND_START = 60;
    const UNSHELVE_END = 63;
    const START = 2;
    const RESUME = 8;
    const STATE_REPORT = 16;
    const UNSHELVE = 20;
    const UNPAUSE = 57;
    const UNSUSPEND = 61;
    const POWER_ON = 59;

    //End events
    const STOP = 4;
    const REQUEST_STOP = 3;
    const REQUEST_TERMINATE = 5;
    const POWER_OFF_START = 44;
    const PAUSE_START = 54;
    const SUSPEND_START = 62;
    const SHELVE_START = 64;
    const TERMINATE = 6;
    const SUSPEND = 17;
    const SHELVE = 19;
    const POWER_OFF = 45;
    const PAUSE = 55;

    const START_ERROR = 41;

    private $_stop_event_ids;
    private $_start_event_ids;
    private $_instance_state;
    private $_end_time;

    /**
     * @see ETL\Ingestor\pdoIngestor::__construct()
     */
    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);

        $this->_start_event_ids = array(
          self::REQUEST_START,
          self::START,
          self::REQUEST_STOP,
          self::STOP,
          self::REQUEST_TERMINATE,
          self::REQUEST_RESUME,
          self::RESUME,
          self::STATE_REPORT,
          self::SUSPEND,
          self::SHELVE,
          self::UNSHELVE,
          self::POWER_OFF_START,
          self::POWER_OFF,
          self::PAUSE_START,
          self::PAUSE,
          self::UNPAUSE_START,
          self::UNPAUSE_END,
          self::POWER_ON_START,
          self::POWER_ON,
          self::UNSUSPEND_START,
          self::UNSUSPEND,
          self::SUSPEND_START,
          self::UNSHELVE_END,
          self::SHELVE_START
        );

        $this->_end_event_ids = array(
          self::REQUEST_START,
          self::START,
          self::REQUEST_STOP,
          self::STOP,
          self::TERMINATE,
          self::REQUEST_TERMINATE,
          self::REQUEST_RESUME,
          self::RESUME,
          self::SUSPEND,
          self::SHELVE,
          self::UNSHELVE,
          self::POWER_OFF_START,
          self::POWER_OFF,
          self::PAUSE_START,
          self::PAUSE,
          self::UNPAUSE_START,
          self::UNPAUSE_END,
          self::POWER_ON_START,
          self::POWER_ON,
          self::UNSUSPEND_START,
          self::UNSUSPEND,
          self::SUSPEND_START,
          self::UNSHELVE_END,
          self::SHELVE_START
        );

        $this->_inactive_end_events = array(self::STOP,self::TERMINATE,self::SUSPEND, self::SHELVE,self::POWER_OFF_START,self::POWER_OFF, self::PAUSE);
        $this->_all_event_ids = array_unique(array_merge($this->_start_event_ids, $this->_end_event_ids));
        $this->_end_time = $etlConfig->getVariableStore()->endDate ? date('Y-m-d H:i:s', strtotime($etlConfig->getVariableStore()->endDate)) : null;

        $this->resetInstance();
    }

    private function initInstance($srcRecord)
    {
        $beginOfDay = strtotime("today", $srcRecord['event_time_ts']);
        $default_end_time = isset($this->_end_time) ? $this->_end_time : strtotime("tomorrow", $beginOfDay) - 1;

        $this->_instance_state = array(
            'resource_id' => $srcRecord['resource_id'],
            'instance_id' => $srcRecord['instance_id'],
            'start_time_ts' => $srcRecord['event_time_ts'],
            'start_event_id' => $srcRecord['event_type_id'],
            'end_time_ts' => $default_end_time,
            'end_event_id' => self::STOP
        );
    }

    private function resetInstance()
    {
        $this->_instance_state = null;
    }

    private function updateInstance($srcRecord)
    {
        $this->_instance_state['end_time_ts'] = $srcRecord['event_time_ts'];
        $this->_instance_state['end_event_id'] = $srcRecord['event_type_id'];
    }

    /**
     * @see ETL\Ingestor\pdoIngestor::transform()
     */
    protected function transform(array $srcRecord, &$orderId)
    {
        // We want to just flush when we hit the dummy row
        if ($srcRecord['event_type_id'] === 0) {
            if (isset($this->_instance_state)) {
                return array($this->_instance_state);
            } else {
                return array();
            }
        }

        if ($this->_instance_state === null) {
            if (in_array($srcRecord['event_type_id'], $this->_start_event_ids)) {
                $this->initInstance($srcRecord);
            }
            return array();
        }

        $transformedRecord = array();

        if (($this->_instance_state['instance_id'] !== $srcRecord['instance_id']) || ($this->_instance_state['resource_id'] !== $srcRecord['resource_id'])) {
            $transformedRecord[] = $this->_instance_state;
            if ($srcRecord['event_type_id'] != self::TERMINATE && $srcRecord['event_type_id'] != self::START_ERROR) {
                $this->initInstance($srcRecord);
            }
        } elseif (in_array($srcRecord['event_type_id'], [1,16])) {
            // If the session is an inactive session and a heartbeat event is
            // encountered end the inactive session and start a new session.
            if(in_array($this->_instance_state['start_event_id'], $this->_inactive_end_events)) {
              $this->updateInstance($srcRecord);
              $transformedRecord[] = $this->_instance_state;
              $this->resetInstance();
              $this->initInstance($srcRecord);
            }
            else {
              $this->updateInstance($srcRecord);
            }
        } elseif (in_array($srcRecord['event_type_id'], $this->_end_event_ids)) {

            $this->updateInstance($srcRecord);
            $transformedRecord[] = $this->_instance_state;
            $this->resetInstance();

            if(in_array($srcRecord['event_type_id'], $this->_start_event_ids)){
                $this->initInstance($srcRecord);
            }
        }

        return $transformedRecord;
    }

    protected function getSourceQueryString()
    {
        $sql = parent::getSourceQueryString();

        // Due to the way the Finite State Machine handles the rows in event reconstruction, the last row
        // is lost. To work around this we add a dummy row filled with zeroes.
        $colCount = count($this->etlSourceQuery->records);
        $unionValues = array_fill(0, $colCount, 0);
        $subSelect = "(SELECT DISTINCT instance_id from modw_cloud.event WHERE last_modified > \"" . $this->getEtlOverseerOptions()->getLastModifiedStartDate() . "\")";

        $sql = "$sql WHERE instance_id IN " . $subSelect . " AND event_type_id IN (" . implode(',', $this->_all_event_ids) . ")\nUNION ALL\nSELECT " . implode(',', $unionValues) . "\nORDER BY 1 DESC, 2 DESC, 3 ASC, 4 DESC";

        return $sql;
    }

    public function transformHelper(array $srcRecord)
    {
        return $this->transform($srcRecord, $orderId);
    }
}
