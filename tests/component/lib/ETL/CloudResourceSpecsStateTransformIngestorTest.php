<?php
/**
 * @author Greg Dean <gmdean@buffalo.edu>
 */

namespace ComponentTests\ETL;

use ETL\Ingestor\CloudResourceSpecsStateTransformIngestor;
use ETL\Ingestor\IngestorOptions;
use ETL\Configuration\EtlConfiguration;

/**
 * Test Cloud Resource Specifications State FSM
 */

class CloudResourceSpecsStateTransformIngestorTest extends \PHPUnit\Framework\TestCase
{
    private $resource_spec_01 = ["resource_id" => 8, "host_id" => 7, "vcpus" => 56, "memory_mb" => 196514, "fact_date" => '2018-04-17', "start_date_ts" => -1, "end_date_ts" => -1, "start_day_id" => -1, "end_day_id" => 1];

    private $resource_spec_02 = ["resource_id" => 8, "host_id" => 7, "vcpus" => 100, "memory_mb" => 196514, "fact_date" => '2018-04-20', "start_date_ts" => -1, "end_date_ts" => -1, "start_day_id" => -1, "end_day_id" => 1];

    private $resource_spec_03 = ["resource_id" => 8, "host_id" => 7, "vcpus" => 100, "memory_mb" => 262030, "fact_date" => '2018-04-24', "start_date_ts" => -1, "end_date_ts" => -1, "start_day_id" => -1, "end_time_ts" => 1];

    private $resource_spec_04 = ["resource_id" => 8, "host_id" => 7, "vcpus" => 56, "memory_mb" => 196514, "fact_date" => '2018-04-30', "start_date_ts" => -1, "end_date_ts" => -1, "start_day_id" => -1, "end_day_id" => 1];

    private $resource_spec_05 = ["resource_id" => 8, "host_id" => 7, "vcpus" => 56, "memory_mb" => 262030, "fact_date" => '2018-05-02', "start_date_ts" => -1, "end_date_ts" => -1, "start_day_id" => -1, "end_day_id" => 1];

    private $resource_spec_06 = ["resource_id" => 8, "host_id" => 7, "vcpus" => -1, "memory_mb" => -1, "fact_date" => '2018-05-10', "start_date_ts" => -1, "end_date_ts" => -1, "start_day_id" => -1, "end_day_id" => 1];

    private $resource_spec_07 = ["resource_id" => 8, "host_id" => 7, "vcpus" => 56, "memory_mb" => 196514, "fact_date" => '2018-05-15', "start_date_ts" => -1, "end_date_ts" => -1, "start_day_id" => -1, "end_day_id" => 1];

    private $resource_spec_08 = ["resource_id" => 8, "host_id" => 7, "vcpus" => 64, "memory_mb" => 196514, "fact_date" => '2019-04-01', "start_date_ts" => -1, "end_date_ts" => -1, "start_day_id" => -1, "end_day_id" => 1];

    private $resource_spec_zero = ["resource_id" => 0, "host_id" => 0, "vcpus" => 0, "memory_mb" => 0, "start_date_ts" => 0, "end_date_ts" => 0, "start_day_id" => 0, "end_day_id" => 0];

    private $resource_spec_vcpu_changed = ["resource_id" => 8, "host_id" => 7, "vcpus" => 56, "memory_mb" => 196514, "start_date_ts" => 1_523_923_200, "end_date_ts" => 1_524_182_399, "start_day_id" => 201_800_107, "end_day_id" => 201_800_109];

    private $resource_spec_memory_changed = ["resource_id" => 8, "host_id" => 7, "vcpus" => 100, "memory_mb" => 196514, "start_date_ts" => 1_524_182_400, "end_date_ts" => 1_524_527_999, "start_day_id" => 201_800_110, "end_day_id" => 201_800_113];

    private $resource_spec_vcpu_changed_original_value = ["resource_id" => 8, "host_id" => 7, "vcpus" => 56, "memory_mb" => 196514, "start_date_ts" => 1_525_046_400, "end_date_ts" => 1_525_219_199, "start_day_id" => 201_800_120, "end_day_id" => 201_800_121];

    private $resource_spec_host_removed = ["resource_id" => 8, "host_id" => 7, "vcpus" => 56, "memory_mb" => 196514, "start_date_ts" => 1_523_923_200, "end_date_ts" => 1_525_910_399, "start_day_id" => 201_800_107, "end_day_id" => 201_800_129];

    private $resource_spec_host_added_back = ["resource_id" => 8, "host_id" => 7, "vcpus" => 56, "memory_mb" => 196514, "start_date_ts" => 1_526_342_400, "end_date_ts" => 1_554_076_799, "start_day_id" => 201_800_135, "end_day_id" => 201_900_090];

    private $options_array = ["name" => "ingest-cloud-resource-specs", "class" => "CloudResourceSpecsStateTransformIngestor", "destination" => "modw_test", "definition_file" => ""];

    private $fsm;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $configFile = realpath(BASE_DIR . '/tests/artifacts/xdmod/etlv2/configuration/input/xdmod_etl_config_8.0.0.json');

        // This needs to be explicitly defined here so PHP 5.4 doesn't complain
        $this->options_array["definition_file"] = BASE_DIR . "/tests/artifacts/xdmod/etlv2/configuration/input/etl_action_defs_8.0.0.d/cloud_state.json";

        $options = new IngestorOptions($this->options_array);
        $conf = EtlConfiguration::factory($configFile);

        $this->fsm = new CloudResourceSpecsStateTransformIngestor($options, $conf);
        parent::__construct($name, $data, $dataName);
    }

    // Test for when the VCpus change for a host
    public function testChangeVcpus(): void
    {
        $this->fsm->transformHelper($this->resource_spec_01);
        $resource_spec = $this->fsm->transformHelper($this->resource_spec_02);

        $this->assertEquals($this->resource_spec_vcpu_changed, $resource_spec[0]);
    }

    // Test for when the memory_mb changes for a host
    public function testChangeMemory(): void
    {
        $this->fsm->transformHelper($this->resource_spec_02);
        $resource_spec = $this->fsm->transformHelper($this->resource_spec_03);

        $this->assertEquals($this->resource_spec_memory_changed, $resource_spec[0]);
    }

    // Test for when vcpus change to a value and the back to a previous value
    // eg. from 10 to 5 and back to 10 on different days
    public function testVcpusChangeToPreviousValue(): void
    {
        $this->fsm->transformHelper($this->resource_spec_01);
        $resource_specs = $this->fsm->transformHelper($this->resource_spec_02);
        $this->fsm->transformHelper($this->resource_spec_04);
        $resource_spec_2 = $this->fsm->transformHelper($this->resource_spec_05);

        $this->assertEquals($this->resource_spec_vcpu_changed, $resource_specs[0]);
        $this->assertEquals($this->resource_spec_vcpu_changed_original_value, $resource_spec_2[0]);
    }

    // Test for when a host is marked as removed
    public function testHostRemoved(): void
    {
        $this->fsm->transformHelper($this->resource_spec_01);
        $resource_specs = $this->fsm->transformHelper($this->resource_spec_06);

        $this->assertEquals($this->resource_spec_host_removed, $resource_specs[0]);
    }

    // Test for when a host is removed on one day and added back on another
    public function testHostRemovedAndAddedBack(): void
    {
        $this->fsm->transformHelper($this->resource_spec_01);
        $resource_specs = $this->fsm->transformHelper($this->resource_spec_06);
        $this->fsm->transformHelper($this->resource_spec_07);
        $resource_specs_2 = $this->fsm->transformHelper($this->resource_spec_08);

        $this->assertEquals($this->resource_spec_host_removed, $resource_specs[0]);
        $this->assertEquals($this->resource_spec_host_added_back, $resource_specs_2[0]);
    }
}
