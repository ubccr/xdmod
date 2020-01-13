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
* @author Greg Dean <gmdean@buffalo.edu>
* @date 2019-12-18
*/

namespace ETL\Ingestor;

use ETL\aOptions;
use ETL\iAction;
use ETL\aAction;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;

use Log;

class StateReconstructorTransformIngestor extends pdoIngestor implements iAction
{

    protected $_instance_state;
    protected $_end_time;
    protected $_end_time_field;
    protected $_new_row_fields;
    protected $_update_row_fields;
    protected $_reset_row_fields;

    /**
     * @see ETL\Ingestor\pdoIngestor::__construct()
     */
    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);

        $this->_end_time_field = $this->parsedDefinitionFile->state_reconstruction_fields->end_time;
        $this->_update_row_fields = $this->parsedDefinitionFile->state_reconstruction_fields->update_row;
        $this->_new_row_fields = $this->parsedDefinitionFile->state_reconstruction_fields->new_row;
        $this->_reset_row_fields = $this->parsedDefinitionFile->state_reconstruction_fields->reset_row;

        $this->_end_time = $etlConfig->getVariableStore()->endDate ? date('Y-m-d', strtotime($etlConfig->getVariableStore()->endDate)) : null;
        $this->resetInstance();
    }

    /**
     * @see ETL\Ingestor\pdoIngestor::execute()
     */
    public function execute(EtlOverseerOptions $etlOverseerOptions){
        parent::execute($etlOverseerOptions);
        $this->initialize($etlOverseerOptions);
        return true;
    }

    /**
     * @see ETL\Ingestor\pdoIngestor::initialize()
     */
    public function initialize(EtlOverseerOptions $etlOverseerOptions = null){

        if ( $this->isInitialized() ) {
            return;
        }

        $this->initialized = false;

        parent::initialize($etlOverseerOptions);

        // This action only supports 1 destination table so use the first one and log a warning if
        // there are multiple.
        reset($this->etlDestinationTableList);
        $this->etlDestinationTable = current($this->etlDestinationTableList);
        $etlTableKey = key($this->etlDestinationTableList);
        if ( count($this->etlDestinationTableList) > 1 ) {
            $this->logger->warning(
                sprintf(
                    "%s does not support multiple ETL destination tables, using first table with key: '%s'",
                    $this,
                    $etlTableKey
                )
            );
        }

        $requiredKeys = array(
            'state_reconstruction_fields' => 'object'
        );

        $messages = null;
        if ( ! \xd_utilities\verify_object_property_types($this->parsedDefinitionFile, $requiredKeys, $messages) ) {
            $this->logAndThrowException(sprintf("Definition file error: %s", implode(', ', $messages)));
        }

        $requiredKeys = array(
            'end_time'   => 'string',
            'new_row' => 'array',
            'update_row' => 'array',
            'reset_row' => 'array',
            'order_by' => 'array'
        );

        if ( ! \xd_utilities\verify_object_property_types($this->parsedDefinitionFile->state_reconstruction_fields, $requiredKeys, $messages) ) {
            $this->logAndThrowException(
                sprintf("Error verifying definition file 'state_reconstruction_fields' section: %s", implode(', ', $messages))
            );
        }

        $orderby_fields = explode(',', $this->parsedDefinitionFile->state_reconstruction_fields->order_by[0]);
        $orderby_fields_array = [];
        foreach($orderby_fields as $key => $value){
            $orderby_fields_array[] = explode(' ', trim($value))[0];
        }

        $updateColumns = array_merge(
            [$this->_end_time_field],
            $this->_update_row_fields,
            $this->_new_row_fields,
            $this->_reset_row_fields,
            $orderby_fields_array
        );

        $missingColumnNames = array_diff(
            array_unique($updateColumns),
            $this->etlDestinationTable->getColumnNames()
        );

        if ( 0 != count($missingColumnNames) ) {
            $this->logAndThrowException(
                sprintf(
                    "The following columns from the state_reconstruction_fields configuration were not found in table '%s': %s",
                    $this->etlDestinationTable->getFullName(),
                    implode(", ", $missingColumnNames)
                )
            );
        }

        $this->initialized = true;

        return true;
    }

    protected function initInstance($srcRecord)
    {
        $default_end_time = isset($this->_end_time) ? $this->_end_time : $srcRecord['event_date'];
        $this->_instance_state = array_merge($srcRecord, ['start_date' => date('Y-m-d', strtotime($srcRecord['event_date'])), 'end_date' => $default_end_time]);
    }

    protected function resetInstance()
    {
        $this->_instance_state = null;
    }

    protected function updateInstance($srcRecord)
    {
        $this->_instance_state[$this->_end_time_field] = date('Y-m-d', strtotime($srcRecord['event_date']));
    }

    /**
     * @see ETL\Ingestor\pdoIngestor::transform()
     */
    protected function transform(array $srcRecord, &$orderId)
    {
        // We want to just flush when we hit the dummy row
        if ($srcRecord[array_keys($srcRecord)[0]] == 0) {
            if (isset($this->_instance_state)) {
                return array($this->_instance_state);
            } else {
                return array();
            }
        }

        if ($this->_instance_state === null) {
            $this->initInstance($srcRecord);
            return array();
        }

        $transformedRecord = array();

        $a = array_filter($this->_new_row_fields, function($field) use($srcRecord){
            return $this->_instance_state[$field] !== $srcRecord[$field];
        });

        if (count($a) > 0) {
            $transformedRecord[] = $this->_instance_state;
            $this->initInstance($srcRecord);
        }
        elseif (array_intersect_key($this->_instance_state, array_flip($this->_update_row_fields)) === array_intersect_key($srcRecord, array_flip($this->_update_row_fields))) {
            $this->updateInstance($srcRecord);
        }
        elseif (array_intersect_key($this->_instance_state, array_flip($this->_reset_row_fields)) !== array_intersect_key($srcRecord, array_flip($this->_reset_row_fields))) {
            $this->updateInstance($srcRecord);
            $transformedRecord[] = $this->_instance_state;
            $this->resetInstance();
        }

        return $transformedRecord;
    }

    protected function getSourceQueryString()
    {
        $sql = parent::getSourceQueryString();
        $destination_tables = array_keys(get_object_vars($this->parsedDefinitionFile->destination_record_map));

        $i = 1;
        $orderby = $this->parsedDefinitionFile->state_reconstruction_fields->order_by[0];

        foreach($this->parsedDefinitionFile->destination_record_map->$destination_tables[0] as $key => $value){
            $orderby = preg_replace("/\b$value\b/", $i, $orderby);
            $i++;
        }

        // Due to the way the Finite State Machine handles the rows in event reconstruction, the last row
        // is lost. To work around this we add a dummy row filled with zeroes.
        $colCount = count($this->etlSourceQuery->records);
        $unionValues = array_fill(0, $colCount, 0);

        $sql = "$sql UNION ALL\nSELECT " . implode(',', $unionValues) . "\nORDER BY ".$orderby;

        return $sql;
    }

    public function transformHelper(array $srcRecord)
    {
        return $this->transform($srcRecord, $orderId);
    }
}
