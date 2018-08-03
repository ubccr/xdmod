<?php
/**
 * @package OpenXdmod\ComponentTests
 * @author Rudra Chakraborty <rudracha@buffalo.edu>
 */

namespace ComponentTests\ETL;

use ETL\Ingestor\CloudStateReconstructorTransformIngestor;

/**
 * Test Cloud State FSM
 */

class CloudStateReconstructorTransformIngestorTest extends \PHPUnit_Framework_TestCase
{
    private $valid_event = array(
        "instance_id" => 2343,
        "event_time_utc" => "2018-02-06 17:09:01",
        "event_type_id" => 2,
        "start_event_id" => -1,
        "end_time" => -1,
        "end_event_id" => -1
    );

    private $valid_end_event = array(
        "instance_id" => 2343,
        "event_time_utc" => "2018-02-07 17:09:01",
        "event_type_id" => 4,
        "start_event_id" => -1,
        "end_time" => -1,
        "end_event_id" => -1
    );

    private $valid_transform = array(
        "instance_id" => 2343,
        "start_time" => "2018-02-06 17:09:01",
        "start_event_id" => 2,
        "end_time" => "2018-02-07 17:09:01",
        "end_event_id" => 4
    );

    private $invalid_event = array(
        "instance_id" => -1,
        "event_time_utc" => "2018-02-06 17:09:01",
        "event_type_id" => 29,
        "start_event_id" => -1,
        "end_time" => -1,
        "end_event_id" => -1
    );

    private $zero_event = array(
        "instance_id" => 0,
        "event_time_utc" => 0,
        "event_type_id" => 0,
        "start_event_id" => 0,
        "end_time" => 0,
        "end_event_id" => 0
    );

    private $fsm;

    public function __construct()
    {
        $this->fsm = new CloudStateReconstructorTransformIngestor();
    }

    protected function testValidTransformation() 
    {
        $this->fsm::transformHelper($this->valid_event, 1);
        $event = $this->fsm::transformHelper($this->valid_end_event, 1);
        
        $this->assertEquals($this->valid_transform, $event);
    }

    protected function testInvalidTransformation() 
    {
        $this->assertEquals(array(), $this->fsm::transformHelper($this->invalid_event, 1));
    }

    protected function testZeroTransformation() 
    {
        $this->assertEquals(null, $this->fsm::transformHelper($this->zero_event, 1));
    }
}
