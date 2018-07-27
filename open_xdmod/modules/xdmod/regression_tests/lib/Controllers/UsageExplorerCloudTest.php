<?php

namespace Controllers;

class UsageExplorerCloudTest extends UsageExplorerTest
{

    public function csvExportProvider(){
        parent::$baseDir = __DIR__ . '/../../../tests/artifacts/xdmod-test-artifacts/xdmod/regression/current/';
        parent::defaultSetup();

        $statistics = array(
            'num_vms_ended',
            'num_vms_running',
            'num_vms_started',
            'avg_wallduration_hours',
            'core_time',
            'wall_time',
            'avg_num_cores',
            'num_cores',
            'avg_cores_reserved',
            'avg_memory_reserved',
            'avg_disk_reserved'
        );

        $group_bys = array(
            'none',
            'person',
            'project',
            'configuration',
            'resource',
            'memory_buckets',
            'vm_size',
            'submission_venue'
        );

        $varSettings = array(
            'realm' => array('Cloud'),
            'dataset_type' => array('aggregate', 'timeseries'),
            'statistic' => $statistics,
            'group_by' => $group_bys,
            'aggregation_unit' => array_keys($this->aggregationUnits)
        );

        return parent::generateTests($varSettings, '2018-04-18', '2018-04-30');
    }
}
