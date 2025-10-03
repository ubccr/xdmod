<?php
/* ==========================================================================================
 * This class simulates a Finite State Machine to determine the end times for a VM instance type.
 * It gets a set of rows that contains the vcpus, memory and disk size of a instance type and it is
 * sorted by resource, instance_type and start time for that instance type configuration. This list
 * is iterated over and an end time is set anytime a change is detected in vcpus, memory or
 * disk size for an instance type configuraion.
 *
 * If no changes are found the current date is considered the end date of the configuration
 *
 * @author Greg Dean <gmdean@buffalo.edu>
 * @date 2022-06-15
 */

namespace ETL\Ingestor;

use ETL\aOptions;
use ETL\iAction;
use ETL\aAction;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;

use Psr\Log\LoggerInterface;

class CloudInstanceTypeStateIngestor extends pdoIngestor implements iAction
{
    protected $_end_time;

    /**
     * This property is used in the sense that there is code that is called that references this property, but it is not
     * ever set.
     *
     * @var null
     */
    protected $_instance_state;

    /**
     * Array meant to track the state a Cloud Instance Ingestion.
     *
     * @var array
     */
    protected $_instance_type_state;

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

        $this->_instance_type_state = array(
            'resource_id' => $srcRecord['resource_id'],
            'instance_type_id' => $srcRecord['instance_type_id'],
            'instance_type' => $srcRecord['instance_type'],
            'display' => $srcRecord['display'],
            'description' => $srcRecord['description'],
            'memory_mb' => $srcRecord['memory_mb'],
            'num_cores' => $srcRecord['num_cores'],
            'disk_gb' => $srcRecord['disk_gb'],
            'start_time' => $srcRecord['start_time'],
            'end_time' => strtotime($default_end_time)
        );
    }

    private function resetInstance()
    {
        $this->_instance_state = null;
    }

    private function setInstanceTypeEndTime($srcRecord)
    {
        $end_date_timestamp = $srcRecord['start_time'] - 1;
        $this->_instance_type_state['end_time'] = $end_date_timestamp;
    }

    private function setInstanceTypeId($srcRecord)
    {
        $this->_instance_type_state['instance_type_id'] = $srcRecord['instance_type_id'];
    }

    /**
     * @see ETL\Ingestor\pdoIngestor::transform()
     */
    protected function transform(array $srcRecord, &$orderId)
    {
        // We want to just flush when we hit the dummy row
        if ($srcRecord['start_time'] === 0) {
            if (isset($this->_instance_type_state)) {
                return array($this->_instance_type_state);
            } else {
                return array();
            }
        }

        if ($this->_instance_type_state === null) {
            $this->initInstance($srcRecord);
        }

        $transformedRecord = array();

        if (($this->_instance_type_state['instance_type'] != $srcRecord['instance_type']) || ($this->_instance_type_state['resource_id'] != $srcRecord['resource_id'])) {
            // When the instance type or resource ID changes the existing data in $this->_instance_type_state is saved in a
            // multidimensional array and a $this->_instance_type_state is reset to null and data for a new record is added to it.
            $transformedRecord[] = $this->_instance_type_state;
            $this->resetInstance();
            $this->initInstance($srcRecord);
        }
        elseif (
            (($this->_instance_type_state['instance_type'] == $srcRecord['instance_type']) &&
            ($this->_instance_type_state['resource_id'] == $srcRecord['resource_id'])) &&
            (($this->_instance_type_state['num_cores'] != $srcRecord['num_cores']) ||
            ($this->_instance_type_state['memory_mb'] != $srcRecord['memory_mb']) ||
            ($this->_instance_type_state['disk_gb'] != $srcRecord['disk_gb']))
        ) {
            // When the details of a specific instance change, such as the number of cores, memory or disk size changes
            // set the correct end time, save the existing record, and start a new one.
            $this->setInstanceTypeEndTime($srcRecord);
            $transformedRecord[] = $this->_instance_type_state;
            $this->resetInstance();
            $this->initInstance($srcRecord);

        }
        elseif (
            ($this->_instance_type_state['instance_type'] == $srcRecord['instance_type']) &&
            ($this->_instance_type_state['resource_id'] == $srcRecord['resource_id']) &&
            ($this->_instance_type_state['num_cores'] == $srcRecord['num_cores']) &&
            ($this->_instance_type_state['memory_mb'] == $srcRecord['memory_mb']) &&
            ($this->_instance_type_state['disk_gb'] == $srcRecord['disk_gb']) &&
            ($this->_instance_type_state['start_time'] < $srcRecord['start_time']) &&
            ($this->_instance_type_state['instance_type_id'] == '0' && $srcRecord['instance_type_id'] != '0')
        ) {
            // When a record with earlier start time for an existing instance type is found update
            // the record with the instance_type_id of the existing record. As the instance_type_id is
            // the primary key this will update the existing row in the database with the new start time.
            $this->setInstanceTypeId($srcRecord);
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
        $sql .= "\nUNION ALL\nSELECT " . implode(',', $unionValues) . "\nORDER BY 1 DESC, 3 ASC, 9 ASC, 8 ASC, 7 ASC, 2 DESC";

        return $sql;
    }

    public function transformHelper(array $srcRecord)
    {
        $orderId = 0;
        return $this->transform($srcRecord, $orderId);
    }
}
