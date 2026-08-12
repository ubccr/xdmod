<?php

namespace RegressionTests\Controllers;

use RegressionTests\TestHarness\RegressionTestHelper;

/**
 * Test the usage explorer for jobs realm regressions.
 */
class UsageExplorerResourcespecificationsTest extends aUsageExplorerTest
{
    public function csvExportProvider()
    {
        $statistics = [
            'total_cpu_core_hours',
            'allocated_cpu_core_hours',
            'total_gpu_hours',
            'allocated_gpu_hours',
            'total_cpu_node_hours',
            'allocated_cpu_node_hours',
            'total_gpu_node_hours',
            'allocated_gpu_node_hours',
            'total_avg_number_of_cpu_nodes',
            'allocated_avg_number_of_cpu_nodes',
            'total_avg_number_of_gpu_nodes',
            'allocated_avg_number_of_gpu_nodes',
            'total_avg_number_of_cpu_cores',
            'allocated_avg_number_of_cpu_cores',
            'total_avg_number_of_gpus',
            'allocated_avg_number_of_gpus'
        ];

        $groupBys = [
            'none',
            'resource',
            'resource_type',
            'provider',
            'resource_allocation_type'
        ];

        $settings = [
            'realm' => ['ResourceSpecifications'],
            'dataset_type' => ['aggregate', 'timeseries'],
            'statistic' => $statistics,
            'group_by' => $groupBys,
            'aggregation_unit' => ['Day', 'Month', 'Quarter', 'Year']
        ];

        return RegressionTestHelper::generateTests($settings, '2016-12-22', '2017-01-07');
    }
}
