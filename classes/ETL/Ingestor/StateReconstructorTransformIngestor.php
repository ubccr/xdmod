<?php
/* ==========================================================================================
 */
namespace ETL\Ingestor;

use ETL\aOptions;
use ETL\iAction;
use ETL\aAction;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;

use Log;

class StateReconstuctorTransformIngestor extends pdoIngestor implements iAction
{
    private $stop_event_ids;

    private $start_event_ids;

    private $instance_state;

    /**
     * @see ETL\Ingestor\pdoIngestor::__construct()
     */
    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);

        $this->stop_event_ids = array(4, 6);
        $this->start_event_ids = array(2, 8, 9, 10, 11, 16);
        $this->all_event_ids = array_merge($this->start_event_ids, $this->stop_event_ids);

        $this->resetInstance();
    }

    private function initInstance($srcRecord) {
        $this->instance_state = array(
            'instance_id' => $srcRecord['instance_id'],
            'start_time' => $srcRecord['event_time_utc'],
            'start_event_id' => $srcRecord['event_type_id'],
            'end_time' => $srcRecord['event_time_utc'],
            'end_event_id' => $srcRecord['event_type_id']
        );
    }

    private function resetInstance() {
        $this->instance_state = null;
    }

    private function updateInstance($srcRecord) {
        $this->instance_state['end_time'] = $srcRecord['event_time_utc'];
        $this->instance_state['end_event_id'] = $srcRecord['event_type_id'];
    }

    /**
     * @see ETL\Ingestor\pdoIngestor::transform()
     */
    protected function transform(array $srcRecord, $orderId)
    {
        if (!in_array($srcRecord['event_type_id'], $this->all_event_ids)) {
            return array();
        }

        if ($srcRecord['instance_id'] === -1) {
            // End of src data
            if ($this->instance_state !== null) {
                return array($this->instance_state);
            }
            return array();
        }

        if ($this->instance_state === null) {
            $this->initInstance($srcRecord);
            return array();
        }

        $transformedRecord = array();

        if ($this->instance_state['instance_id'] !== $srcRecord['instance_id']) {
            $transformedRecord[] = $this->instance_state;
            $this->initInstance($srcRecord);
        }
        else if  (in_array($srcRecord['event_type_id'], $this->start_event_ids)) {
            $this->updateInstance($srcRecord);
        }
        else if (in_array($srcRecord['event_type_id'], $this->stop_event_ids)) {

            $this->updateInstance($srcRecord);
            $transformedRecord[] = $this->instance_state;
            $this->resetInstance();
        }

        return $transformedRecord;
    }
}
