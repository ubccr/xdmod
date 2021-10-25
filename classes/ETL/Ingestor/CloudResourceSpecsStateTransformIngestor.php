<?php
/* ==========================================================================================
 * This class simulates a Finite State Machine to reconstruct the start and end time of a specific
 * set of vcpus and memory for a cloud host. It get a set of rows that contains the vcpus and memory
 * sorted by resource, host and start time of the configuration. This list is iterated over and an end
 * time is set anytime a changed is detected in the number of vcpus or memory for a host.
 *
 * If no changes are found the current date is considered the end date of the configuration
 *
 * A -1 value for the vcpus and memory_mb means the host was not available on that day
 *
 * @author Greg Dean <gmdean@buffalo.edu>
 * @date 2021-01-27
 */

namespace ETL\Ingestor;

use ETL\aOptions;
use ETL\iAction;
use ETL\aAction;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;

use Psr\Log\LoggerInterface;

class CloudResourceSpecsStateTransformIngestor extends pdoIngestor implements iAction
{

    private $_instance_state;

    /**
     * @see ETL\Ingestor\pdoIngestor::__construct()
     */
    public function __construct(aOptions $options, EtlConfiguration $etlConfig, LoggerInterface $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);

        $this->_end_time = $etlConfig->getVariableStore()->endDate ? date('Y-m-d H:i:s', strtotime($etlConfig->getVariableStore()->endDate)) : null;

        $this->resetInstance();
    }

    private function initInstance($srcRecord)
    {
        // Since we only get information for when a configuration changes we assume a configuration has an end date
        // of today unless we have a row that tells us otherwise
        $default_end_time = isset($this->_end_time) ? $this->_end_time : date('Y-m-d') . ' 23:59:59';

        $this->_instance_state = array(
            'resource_id' => $srcRecord['resource_id'],
            'host_id' => $srcRecord['host_id'],
            'vcpus' => $srcRecord['vcpus'],
            'memory_mb' => $srcRecord['memory_mb'],
            'start_date_ts' => strtotime($srcRecord['fact_date'] . " 00:00:00"),
            'end_date_ts' => strtotime($default_end_time),
            'start_day_id' => date('Y', strtotime($srcRecord['fact_date'])) * 100000 + date('z', strtotime($srcRecord['fact_date'])) + 1,
            'end_day_id' => date('Y', strtotime($default_end_time)) * 100000 + date('z', strtotime($default_end_time)) + 1
        );
    }

    private function resetInstance()
    {
        $this->_instance_state = null;
    }

    private function updateInstance($srcRecord)
    {
        // The -1 is to make sure we use the last second of the previous day
        $end_date_timestamp = strtotime($srcRecord['fact_date'] . " 00:00:00") - 1;
        $this->_instance_state['end_date_ts'] = $end_date_timestamp;

        // date(z) is zero indexed so +1 is needed to get the correct day of the year
        $this->_instance_state['end_day_id'] = date('Y', $end_date_timestamp) * 100000 + date('z', $end_date_timestamp) + 1;
    }

    /**
     * @see ETL\Ingestor\pdoIngestor::transform()
     */
    protected function transform(array $srcRecord, &$orderId)
    {
        // We want to just flush when we hit the dummy row
        if ($srcRecord['fact_date'] === 0) {
            if (isset($this->_instance_state)) {
                return array($this->_instance_state);
            } else {
                return array();
            }
        }

        if ($this->_instance_state === null) {
            if($srcRecord['vcpus'] == -1 && $srcRecord['memory_mb'] == -1) {
                return array();
            }

            $this->initInstance($srcRecord);
        }

        $transformedRecord = array();

        if (($this->_instance_state['host_id'] != $srcRecord['host_id']) || ($this->_instance_state['resource_id'] != $srcRecord['resource_id']) || ($this->_instance_state['vcpus'] != $srcRecord['vcpus'] || $this->_instance_state['memory_mb'] != $srcRecord['memory_mb'])) {

            // Only update the instance if the only thing that is different between $srcRecord and $this->_instance_state is that either the memory or vcpus changed
            if (($this->_instance_state['vcpus'] != $srcRecord['vcpus'] || $this->_instance_state['memory_mb'] != $srcRecord['memory_mb'])
                 && ($this->_instance_state['host_id'] == $srcRecord['host_id']) && ($this->_instance_state['resource_id'] == $srcRecord['resource_id'])) {
                $this->updateInstance($srcRecord);
            }

            $transformedRecord[] = $this->_instance_state;
            $this->resetInstance();

            // Under most circumstances when we detect a change we want to start a new row with data from the row that has changed. This is not
            // the case when the change detected is a -1 value for vcpus or memory_mb. When vcpus or memory_mb is -1 it means the host has been
            // removed and we just want to end the row and not create a new row.
            if($srcRecord['vcpus'] != -1 && $srcRecord['memory_mb'] != -1) {
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
        $sql .= "\nUNION ALL\nSELECT " . implode(',', $unionValues) . "\nORDER BY 1 DESC, 2 ASC, 5 ASC";

        return $sql;
    }

    public function transformHelper(array $srcRecord)
    {
        $orderId = 0;
        return $this->transform($srcRecord, $orderId);
    }
}
