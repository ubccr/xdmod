<?php

namespace Controllers;

class UsageExplorerCloudTest extends UsageExplorerTest
{

    public function csvExportProvider(){
        parent::$baseDir = __DIR__ . '/../../../tests/artifacts/xdmod-test-artifacts/xdmod/regression/current/';
        parent::defaultSetup();

        $statistics = array(
            'cloud_num_sessions_ended',
            'cloud_num_sessions_running',
            'cloud_num_sessions_started',
            'cloud_avg_wallduration_hours',
            'cloud_core_time',
            'cloud_wall_time',
            'cloud_avg_cores_reserved',
            'cloud_avg_memory_reserved',
            'cloud_avg_rv_storage_reserved'
        );

        $group_bys = array(
            'none',
            'project',
            'configuration',
            'resource',
            'vm_size_memory',
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
