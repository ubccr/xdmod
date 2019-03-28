<?php
/**
 * @package OpenXdmod\ComponentTests
 * @author Rudra Chakraborty <rudracha@buffalo.edu>
 */

namespace ComponentTests\ETL;

use ETL\Ingestor\CloudStateReconstructorTransformIngestor;
use ETL\Ingestor\IngestorOptions;
use ETL\Configuration\EtlConfiguration;

/**
 * Test Cloud State FSM
 */

class CloudStateReconstructorTransformIngestorTest extends \PHPUnit_Framework_TestCase
{
    private $event_start_res01 = array(
        "resource_id" => 12,
        "instance_id" => 2343,
        "event_time_ts" => "1517936941.000000",
        "event_type_id" => 2,
        "start_event_id" => -1,
        "end_time_ts" => -1,
        "end_event_id" => -1
    );

    private $event_end_res01 = array(
        "resource_id" => 12,
        "instance_id" => 2343,
        "event_time_ts" => "1518026941.000000",
        "event_type_id" => 4,
        "start_event_id" => -1,
        "end_time_ts" => -1,
        "end_event_id" => -1
    );

    private $event_end_res02 = array(
        "resource_id" => 13,
        "instance_id" => 2343,
        "event_time_ts" => "1518026941.000000",
        "event_type_id" => 4,
        "start_event_id" => -1,
        "end_time_ts" => -1,
        "end_event_id" => -1
    );

    private $event_transform_res01 = array(
        "resource_id" => 12,
        "instance_id" => 2343,
        "start_time_ts" => "1517936941.000000",
        "start_event_id" => 2,
        "end_time_ts" => "1518026941.000000",
        "end_event_id" => 4
    );

    private $event_err_res01 = array(
        "resource_id" => 12,
        "instance_id" => -1,
        "event_time_ts" => "1517936941.000000",
        "event_type_id" => 29,
        "start_event_id" => -1,
        "end_time_ts" => -1,
        "end_event_id" => -1
    );

    private $event_zero = array(
        "resource_id" => 12,
        "instance_id" => 0,
        "event_time_ts" => 0,
        "event_type_id" => 0,
        "start_event_id" => 0,
        "end_time_ts" => 0,
        "end_event_id" => 0
    );

    private $event_flush = array(
        "resource_id" => 12,
        "instance_id" => 2343,
        "start_time_ts" => "1517936941.000000",
        "start_event_id" => 2,
        "end_time_ts" => "1517936941.000000",
        "end_event_id" => 4
    );

    private $options_array = array(
        "name" => "cloud-state-action",
        "class" => "CloudStateReconstructorTransformIngestor",
        "destination" => "modw_test",
        "definition_file" => ""
    );

    private $fsm;

    public function __construct()
    {
        $configFile = realpath(BASE_DIR . '/tests/artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/input/xdmod_etl_config_8.0.0.json');

        // This needs to be explicitly defined here so PHP 5.4 doesn't complain
        $this->options_array["definition_file"] = BASE_DIR . "/tests/artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/input/etl_action_defs_8.0.0.d/cloud_state.json";

        $options = new IngestorOptions($this->options_array);
        $conf = new ETLConfiguration($configFile);

        $this->fsm = new CloudStateReconstructorTransformIngestor($options, $conf);
    }

    // happy path
    public function testValidStartEnd()
    {
        $this->fsm->transformHelper($this->event_start_res01);
        $event = $this->fsm->transformHelper($this->event_end_res01);

        $this->assertEquals($this->event_transform_res01, $event[0]);
    }

    // what happens when we hit an invalid start event
    public function testInvalidStart()
    {
        $this->assertEquals(array(), $this->fsm->transformHelper($this->event_err_res01));
    }

    // what happens when we hit the dummy row
    public function testZeroEvent()
    {
        $this->assertEquals(array(), $this->fsm->transformHelper($this->event_zero));
    }

    // what happens when we hit a valid start event and then the dummy row
    public function testValidAndZeroEvent()
    {
        $this->fsm->transformHelper($this->event_start_res01);
        $event = $this->fsm->transformHelper($this->event_zero);

        $this->assertEquals($this->event_flush, $event[0]);
    }

    // what happens when you hit a valid end event from another resource
    public function testTwoResourceEvents()
    {
        $this->fsm->transformHelper($this->event_start_res01);
        $event = $this->fsm->transformHelper($this->event_end_res02);

        $this->assertEquals($this->event_flush, $event[0]);
    }
}
