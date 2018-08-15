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

    public function testValidTransformation()
    {
        $this->fsm->transformHelper($this->valid_event);
        $event = $this->fsm->transformHelper($this->valid_end_event);
        
        $this->assertEquals($this->valid_transform, $event[0]);
    }

    public function testInvalidTransformation()
    {
        $this->assertEquals(array(), $this->fsm->transformHelper($this->invalid_event));
    }

    public function testZeroTransformation()
    {
        $this->assertEquals(null, $this->fsm->transformHelper($this->zero_event)[0]);
    }
}
