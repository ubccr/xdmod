<?php
/**
 * @package OpenXdmod\ComponentTests
 * @author Greg Dean <gmdean@buffalo.edu>
 */

namespace ComponentTests\ETL;

use ETL\Ingestor\StateReconstructorTransformIngestor;
use ETL\Ingestor\IngestorOptions;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;

/**
 * Test Cloud State FSM
 */

class StateReconstructorTransformIngestorTest extends \PHPUnit_Framework_TestCase
{
    private $cloud_specification_res01 = array(
        "resource_id" => 8,
        "hostname" => "srv-p24-36.cbls.ccr.buffalo.edu",
        "memory_mb" => 196514,
        "vcpus" => 56,
        "event_date" => '2018-04-17',
        "start_date" => -1,
        "end_date" => -1
    );

    private $cloud_specification_res02 = array(
        "resource_id" => 8,
        "hostname" => "srv-p24-36.cbls.ccr.buffalo.edu",
        "memory_mb" => 196514,
        "vcpus" => 56,
        "event_date" => '2019-01-03',
        "start_date" => -1,
        "end_date" => -1
    );

    private $cloud_specification_res03 = array(
        "resource_id" => 9,
        "hostname" => "srv-p24-33.cbls.ccr.buffalo.edu",
        "memory_mb" => 196514,
        "vcpus" => 56,
        "event_date" => '2018-04-17',
        "start_date" => -1,
        "end_date" => -1
    );

    private $cloud_specification_transform_res01 = array(
        "resource_id" => 8,
        "hostname" => "srv-p24-36.cbls.ccr.buffalo.edu",
        "memory_mb" => 196514,
        "vcpus" => 56,
        "event_date" => '2018-04-17',
        "start_date" => '2018-04-17',
        "end_date" => '2019-01-03'
    );

    private $event_zero = array(
        "resource_id" => 8,
        "hostname" => 0,
        "memory_mb" => 0,
        "vcpus" => 0,
        "event_date" => 0,
        "start_date" => 0,
        "end_date" => 0
    );

    private $event_flush = array(
        "resource_id" => 8,
        "hostname" => "srv-p24-36.cbls.ccr.buffalo.edu",
        "memory_mb" => 196514,
        "vcpus" => 56,
        "event_date" => '2018-04-17',
        "start_date" => '2018-04-17',
        "end_date" => '2019-01-03'
    );

    private $options_array = array(
        "name" => "CloudResourceSpecsReconstructor",
        "class" => "StateReconstructorTransformIngestor",
        "destination" => "modw_test",
        "definition_file" => ""
    );

    private $fsm;

    public function __construct()
    {
        $configFile = realpath(BASE_DIR . '/tests/artifacts/xdmod/etlv2/configuration/input/xdmod_etl_config_8.0.0.json');

        // This needs to be explicitly defined here so PHP 5.4 doesn't complain
        $this->options_array["definition_file"] = BASE_DIR . "/tests/artifacts/xdmod/etlv2/configuration/input/etl_action_defs_8.0.0.d/ingest_resource_specs.json";

        $options = new IngestorOptions($this->options_array);
        $conf = EtlConfiguration::factory($configFile);

        $this->fsm = new StateReconstructorTransformIngestor($options, $conf);
    }

    // happy path
    public function testValidStartEnd()
    {
        $this->fsm->transformHelper($this->cloud_specification_res01);
        $this->fsm->transformHelper($this->cloud_specification_res02);
        $event = $this->fsm->transformHelper($this->cloud_specification_res03);

        $this->assertEquals($this->cloud_specification_transform_res01, $event[0]);
    }

    // what happens when we hit the dummy row
    public function testZeroEvent()
    {
        $this->assertEquals(array(), $this->fsm->transformHelper($this->event_zero));
    }

    // what happens when we hit a valid start event and then the dummy row
    public function testValidAndZeroEvent()
    {
        $this->fsm->transformHelper($this->cloud_specification_res01);
        $this->fsm->transformHelper($this->cloud_specification_res02);
        $event = $this->fsm->transformHelper($this->event_zero);

        $this->assertEquals($this->event_flush, $event[0]);
    }

    // what happens when you hit a valid end event from another resource
    public function testTwoResourceEvents()
    {
        $this->fsm->transformHelper($this->cloud_specification_res01);
        $this->fsm->transformHelper($this->cloud_specification_res02);
        $event = $this->fsm->transformHelper($this->cloud_specification_res03);

        $this->assertEquals($this->event_flush, $event[0]);
    }
}
