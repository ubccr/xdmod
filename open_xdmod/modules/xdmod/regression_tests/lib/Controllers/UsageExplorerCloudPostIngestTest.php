<?php

namespace Controllers;

class UsageExplorerCloudPostIngestTest extends UsageExplorerTest
{

    public function csvExportProvider(){
        parent::$baseDir = __DIR__ . '/../../../tests/artifacts/xdmod-test-artifacts/xdmod/post_ingest/';
        parent::defaultSetup();

        $statistics = array(
            'cloud_num_sessions_ended',
            'cloud_num_sessions_running',
            'cloud_num_sessions_started',
        );

        $group_bys = array(
            'none',
        );

        $varSettings = array(
            'realm' => array('Cloud'),
            'dataset_type' => array('aggregate', 'timeseries'),
            'statistic' => $statistics,
            'group_by' => $group_bys,
            'aggregation_unit' => array_keys($this->aggregationUnits)
        );

        return parent::generateTests($varSettings, '2018-05-19', '2018-05-19');
    }
}
