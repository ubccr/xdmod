<?php
/**
 * @author Greg Dean <gmdean@buffalo.edu>
 */

namespace ComponentTests\ETL;

use ETL\Ingestor\CloudInstanceTypeStateIngestor;
use ETL\Ingestor\IngestorOptions;
use ETL\Configuration\EtlConfiguration;

/**
 * Test Cloud Resource Specifications State FSM
 */

class CloudInstanceTypeStateIngestorTest extends \PHPUnit\Framework\TestCase
{
    private $instance_type_state_first_record = array(
        "resource_id" => 8,
        "instance_type_id" => 0,
        "instance_type" => 'c1.m4',
        "display" => 'c1.m4',
        "description" => '',
        "num_cores" => 1,
        "memory_mb" => 4096,
        "disk_gb" => 20,
        "start_time" => 1524063518
    );

    private $instance_type_state_existing_record = array(
        "resource_id" => 8,
        "instance_type_id" => 2,
        "instance_type" => 'c1.m4',
        "display" => 'c1.m4',
        "description" => '',
        "num_cores" => 1,
        "memory_mb" => 4096,
        "disk_gb" => 20,
        "start_time" => 1524063601
    );

    private $instance_type_state_change_num_cores = array(
        "resource_id" => 8,
        "instance_type_id" => 0,
        "instance_type" => 'c1.m4',
        "display" => 'c1.m4',
        "description" => '',
        "num_cores" => 2,
        "memory_mb" => 4096,
        "disk_gb" => 20,
        "start_time" => 1524063549
    );

    private $instance_type_state_change_memory = array(
        "resource_id" => 8,
        "instance_type_id" => 0,
        "instance_type" => 'c1.m4',
        "display" => 'c1.m4',
        "description" => '',
        "num_cores" => 1,
        "memory_mb" => 8192,
        "disk_gb" => 20,
        "start_time" => 1524063549
    );

    private $instance_type_state_change_disk_gb = array(
        "resource_id" => 8,
        "instance_type_id" => 0,
        "instance_type" => 'c1.m4',
        "display" => 'c1.m4',
        "description" => '',
        "num_cores" => 1,
        "memory_mb" => 4096,
        "disk_gb" => 40,
        "start_time" => 1524063549
    );

    private $instance_type_state_change_resource_id = array(
        "resource_id" => 9,
        "instance_type_id" => 0,
        "instance_type" => 'c1.m4',
        "display" => 'c1.m4',
        "description" => '',
        "num_cores" => 1,
        "memory_mb" => 4096,
        "disk_gb" => 20,
        "start_time" => 1524063549
    );

    private $instance_type_state_change_instance_type = array(
        "resource_id" => 8,
        "instance_type_id" => 0,
        "instance_type" => 'c2.m4',
        "display" => 'c2.m4',
        "description" => '',
        "num_cores" => 2,
        "memory_mb" => 4096,
        "disk_gb" => 20,
        "start_time" => 1524063549
    );

    private $instance_type_state_original = array(
        "resource_id" => 8,
        "instance_type_id" => 0,
        "instance_type" => 'c1.m4',
        "display" => 'c1.m4',
        "description" => '',
        "num_cores" => 1,
        "memory_mb" => 4096,
        "disk_gb" => 20,
        "start_time" => 1524243485
    );

    private $instance_type_state_original2 = array(
        "resource_id" => 8,
        "instance_type_id" => 0,
        "instance_type" => 'c1.m4',
        "display" => 'c1.m4',
        "description" => '',
        "num_cores" => 2,
        "memory_mb" => 4096,
        "disk_gb" => 20,
        "start_time" => 1524243500
    );

    private $instance_type_state_zero = array(
        "resource_id" => 0,
        "instance_type_id" => 0,
        "instance_type" => 0,
        "display" => 0,
        "description" => 0,
        "num_cores" => 0,
        "memory_mb" => 0,
        "disk_gb" => 0,
        "start_time" => 0
    );

    private $instance_state_no_change_result = array(
        "resource_id" => 8,
        "instance_type_id" => 0,
        "instance_type" => 'c1.m4',
        "display" => 'c1.m4',
        "description" => '',
        "num_cores" => 1,
        "memory_mb" => 4096,
        "disk_gb" => 20,
        "start_time" => 1524063518,
        "end_time" => 1524063548
    );

    private $instance_state_change_cores_result = array(
        "resource_id" => 8,
        "instance_type_id" => 0,
        "instance_type" => 'c1.m4',
        "display" => 'c1.m4',
        "description" => '',
        "num_cores" => 2,
        "memory_mb" => 4096,
        "disk_gb" => 20,
        "start_time" => 1524063549,
        "end_time" => 1524243484
    );

    private $instance_state_change_mem_result = array(
        "resource_id" => 8,
        "instance_type_id" => 0,
        "instance_type" => 'c1.m4',
        "display" => 'c1.m4',
        "description" => '',
        "num_cores" => 1,
        "memory_mb" => 8192,
        "disk_gb" => 20,
        "start_time" => 1524063549,
        "end_time" => 1524243484
    );

    private $instance_state_change_disk_gb_result = array(
        "resource_id" => 8,
        "instance_type_id" => 0,
        "instance_type" => 'c1.m4',
        "display" => 'c1.m4',
        "description" => '',
        "num_cores" => 1,
        "memory_mb" => 4096,
        "disk_gb" => 40,
        "start_time" => 1524063549,
        "end_time" => 1524243484
    );

    private $instance_state_change_to_previous_result = array(
        "resource_id" => 8,
        "instance_type_id" => 0,
        "instance_type" => 'c1.m4',
        "display" => 'c1.m4',
        "description" => '',
        "num_cores" => 1,
        "memory_mb" => 4096,
        "disk_gb" => 20,
        "start_time" => 1524243485,
        "end_time" => 1524243499
    );

    private $instance_state_update_existing = array(
        "resource_id" => 8,
        "instance_type_id" => 2,
        "instance_type" => 'c1.m4',
        "display" => 'c1.m4',
        "description" => '',
        "num_cores" => 1,
        "memory_mb" => 4096,
        "disk_gb" => 20,
        "start_time" => 1524063518,
        "end_time" => 1524063548
    );

    private $options_array = array(
        "name" => "jobs-cloud-extract-openstack",
        "class" => "CloudInstanceTypeStateIngestorTest",
        "destination" => "modw_test",
        "definition_file" => ""
    );

    private $fsm;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $configFile = realpath(BASE_DIR . '/tests/artifacts/xdmod/etlv2/configuration/input/xdmod_etl_config_8.0.0.json');

        // This needs to be explicitly defined here so PHP 5.4 doesn't complain
        $this->options_array["definition_file"] = BASE_DIR . "/tests/artifacts/xdmod/etlv2/configuration/input/etl_action_defs_8.0.0.d/cloud_state.json";

        $options = new IngestorOptions($this->options_array);
        $conf = EtlConfiguration::factory($configFile);

        $this->fsm = new CloudInstanceTypeStateIngestor($options, $conf);
        parent::__construct($name, $data, $dataName);
    }

    // Test for when the number of cores for an instance changes
    public function testChangeNumCores()
    {
        $this->fsm->transformHelper($this->instance_type_state_first_record);
        $instance_state = $this->fsm->transformHelper($this->instance_type_state_change_num_cores);
        $instance_state2 = $this->fsm->transformHelper($this->instance_type_state_original);

        $this->assertEquals($this->instance_state_no_change_result, $instance_state[0]);
        $this->assertEquals($this->instance_state_change_cores_result, $instance_state2[0]);
    }

    // Test for when the amount of memory for an instance changes
    public function testChangeMem()
    {
        $this->fsm->transformHelper($this->instance_type_state_first_record);
        $instance_state = $this->fsm->transformHelper($this->instance_type_state_change_memory);
        $instance_state2 = $this->fsm->transformHelper($this->instance_type_state_original);

        $this->assertEquals($this->instance_state_no_change_result, $instance_state[0]);
        $this->assertEquals($this->instance_state_change_mem_result, $instance_state2[0]);
    }

    // Test for when the disk size for an instance changes
    public function testChangeDisk()
    {
        $this->fsm->transformHelper($this->instance_type_state_first_record);
        $instance_state = $this->fsm->transformHelper($this->instance_type_state_change_disk_gb);
        $instance_state2 = $this->fsm->transformHelper($this->instance_type_state_original);

        $this->assertEquals($this->instance_state_no_change_result, $instance_state[0]);
        $this->assertEquals($this->instance_state_change_disk_gb_result, $instance_state2[0]);
    }

    // Test when a instance configuration changes back to a previous configuration
    public function testChangeInstanceToPrevious()
    {
        $this->fsm->transformHelper($this->instance_type_state_first_record);
        $instance_state = $this->fsm->transformHelper($this->instance_type_state_change_num_cores);
        $instance_state2 = $this->fsm->transformHelper($this->instance_type_state_original);
        $instance_state3 = $this->fsm->transformHelper($this->instance_type_state_original2);

        $this->assertEquals($this->instance_state_no_change_result, $instance_state[0]);
        $this->assertEquals($this->instance_state_change_cores_result, $instance_state2[0]);
        $this->assertEquals($this->instance_state_change_to_previous_result, $instance_state3[0]);
    }

    // Test updating an existing record with a new start time.
    public function testUpdateExisting()
    {
        $this->fsm->transformHelper($this->instance_type_state_first_record);
        $this->fsm->transformHelper($this->instance_type_state_existing_record);
        $instance_state2 = $this->fsm->transformHelper($this->instance_type_state_change_num_cores);

        $this->assertEquals($this->instance_state_update_existing, $instance_state2[0]);
    }
}
