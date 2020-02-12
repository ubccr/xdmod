<?php
/* ==========================================================================================
* This class simulates a Finite State Machine to construct a start and end time for specific thing. Given a list of
* records with a time when the record is related to, this class will create records with start and end times based on
* criteria specified in the action definition file.
*
* To use this ingestor the following json object should be present in your action definition file
*
* state_reconstruction_fields: {
*      "end_time": "",
*      "new_row": [],
*      "update_row": [],
*      "reset_row": [],
*      "order_by": []
*  }
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

    /**
     * @var array
     *
     * Array that consists of same columns that are in the source_query plus a start time and end time field.
     * The end time field is updated as new records are encountered that maatch the criteria specified in the action definition
     * file
     */
    protected $_instance_state;

    /**
     * @var string End time that is up
     */
    protected $_end_time;

    /**
     * @var string Field in source_query that should have the value for the end time of each event
     */
    protected $_end_time_field;

    /**
     * @var array Array of fields from source_query that determine what columns to use to determine if
     * new row needs to be made
     */
    protected $_new_row_fields;

    /**
     * @var array Array of column names from source_query used to determine when to update only the end time of a new row
     */
    protected $_update_row_fields;

    /**
     * @var array Array of column names from source_query used to determine when to update and the end time of a new row,
     * mark that row as completed and reset the $_instance_state variable so a new row can be created
     */
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

        $messages = null;
        $actionDefinitionRequiredKeys = array( 'state_reconstruction_fields' => 'object' );
        $stateReconstructorRequiredKeys = array(
            'end_time'   => 'string',
            'new_row' => 'array',
            'update_row' => 'array',
            'reset_row' => 'array',
            'order_by' => 'array'
        );

        parent::initialize($etlOverseerOptions);

        // This action only supports 1 destination table so use the first one and log a warning if
        // there are multiple.
        reset($this->etlDestinationTableList);
        $this->etlDestinationTable = current($this->etlDestinationTableList);
        $etlTableKey = key($this->etlDestinationTableList);

        if ( count($this->etlDestinationTableList) > 1 ) {
            $this->logger->warning(
                sprintf(
                    "%s does not support multiple ETL destination tables, using only first table with key: '%s'",
                    $this,
                    $etlTableKey
                )
            );
        }

        if ( ! \xd_utilities\verify_object_property_types($this->parsedDefinitionFile, $actionDefinitionRequiredKeys, $messages) ) {
            $this->logAndThrowException(sprintf("Definition file error: %s", implode(', ', $messages)));
        }

        if ( ! \xd_utilities\verify_object_property_types($this->parsedDefinitionFile->state_reconstruction_fields, $stateReconstructorRequiredKeys, $messages) ) {
            $this->logAndThrowException(
                sprintf("Error verifying definition file 'state_reconstruction_fields' section: %s", implode(', ', $messages))
            );
        }

        $orderby_fields_array = [];
        foreach( $this->parsedDefinitionFile->state_reconstruction_fields->order_by as $orderby ) {
            $orderby_fields_array[] = explode(' ', trim($orderby))[0];
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

    /**
     * Sets $srcRecord to $this->_instance_state and adds a start and end time.
     */
    protected function initInstance($srcRecord)
    {
        $default_end_time = isset($this->_end_time) ? $this->_end_time : $srcRecord['event_date'];
        $start_day_id = date('Y', strtotime($srcRecord['event_date'])) * 100000 + date('z', strtotime($srcRecord['event_date']));
        $end_day_id =  date('Y', strtotime($default_end_time)) * 100000 + date('z', strtotime($default_end_time));

        $this->_instance_state = array_merge($srcRecord, ['start_date' => date('Y-m-d', strtotime($srcRecord['event_date'])), $this->_end_time_field => $default_end_time, 'start_day_id' => $start_day_id, 'end_day_id' => $end_day_id]);
    }

    /**
     * Resets the $this->_instance_state to null so that a new record with a start and end time can be created
     */
    protected function resetInstance()
    {
        $this->_instance_state = null;
    }

    /**
     * Updates the end time of the a reconstructed record
     * @param array $srcRecord Database record that provides the end time for a new record
     */
    protected function updateInstance($srcRecord)
    {
        $this->_instance_state[$this->_end_time_field] = date('Y-m-d', strtotime($srcRecord['event_date']));
        $this->_instance_state['end_day_id'] = date('Y', strtotime($srcRecord['event_date'])) * 100000 + date('z', strtotime($srcRecord['event_date']));
    }

    /**
     * @see ETL\Ingestor\pdoIngestor::transform()
     */
    protected function transform(array $srcRecord, &$orderId)
    {
        // We want to just flush when we hit the dummy row
        if ($srcRecord[array_keys($srcRecord)[0]] == 0) {
            return (isset($this->_instance_state)) ? array($this->_instance_state) : array();
        }

        if ($this->_instance_state === null) {
            $this->initInstance($srcRecord);
            return array();
        }

        $transformedRecord = array();

        // Takes the fields listed in the new_row array an action definition field and compares those fields
        // in $this->_instance_state and $srcRecord. If the values in the specified fields are the same between arrays
        // then the number of rows return is 0.
        $fieldComparison = array_filter($this->_new_row_fields, function ($field) use ($srcRecord) {
            return $this->_instance_state[$field] !== $srcRecord[$field];
        });

        // If any of the fields specified in the new_row array in the action definition file have different values then
        // it indicates no more records for this unique set values exists and a new row needs to be created and the old
        // one returned.
        if (count($fieldComparison) > 0) {
            $transformedRecord[] = $this->_instance_state;
            $this->initInstance($srcRecord);
        }
        elseif (array_intersect_key($this->_instance_state, array_flip($this->_update_row_fields)) === array_intersect_key($srcRecord, array_flip($this->_update_row_fields))) {
            // Uses the columns specified in the update_row array in the action definition file to find where only the end time of a unique set of values should be changed.
            $this->updateInstance($srcRecord);
        }
        elseif (array_intersect_key($this->_instance_state, array_flip($this->_reset_row_fields)) !== array_intersect_key($srcRecord, array_flip($this->_reset_row_fields))) {
            // Uses the columns specified in the reset_row array in the action definition file to find times where the end time of a unique set of values should be changed
            // and it is also the last record for that set of unique values and is reset.
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
        $orderby = implode(',', $this->parsedDefinitionFile->state_reconstruction_fields->order_by);

        foreach($this->parsedDefinitionFile->destination_record_map->$destination_tables[0] as $destination_table_column){
            $orderby = preg_replace("/\b$destination_table_column\b/", $i, $orderby);
            $i++;
        }

        // Due to the way the Finite State Machine handles the rows in event reconstruction, the last row
        // is lost. To work around this we add a dummy row filled with zeroes.
        $colCount = count($this->etlSourceQuery->records);
        $unionValues = array_fill(0, $colCount, 0);

        return "$sql UNION ALL\nSELECT " . implode(',', $unionValues) . "\nORDER BY ".$orderby;
    }

    public function transformHelper(array $srcRecord, $orderId = 0)
    {
        return $this->transform($srcRecord, $orderId);
    }
}
