<?php

namespace RegressionTests\Controllers;

use RegressionTests\TestHarness\RegressionTestHelper;

/**
 * Test the usage explorer for jobs realm regressions.
 */
class UsageExplorerJobsTest extends aUsageExplorerTest
{
    public function csvExportProvider()
    {
        $statistics = [
            'active_person_count',
            'active_pi_count',
            'active_resource_count',
            'avg_cpu_hours',
            'avg_gpu_hours',
            'avg_job_size_weighted_by_cpu_hours',
            'avg_job_size_weighted_by_gpu_hours',
            'avg_node_hours',
            'avg_processors',
            'avg_gpus',
            'avg_waitduration_hours',
            'avg_wallduration_hours',
            'expansion_factor',
            'job_count',
            'max_processors',
            'min_processors',
            'normalized_avg_processors',
            'running_job_count',
            'started_job_count',
            'submitted_job_count',
            'total_cpu_hours',
            'total_gpu_hours',
            'total_node_hours',
            'total_waitduration_hours',
            'total_wallduration_hours',
            'utilization'
        ];

        $groupBys = [
            'fieldofscience',
            'gpucount',
            'jobsize',
            'jobwalltime',
            'jobwaittime',
            'nodecount',
            'none',
            'nsfdirectorate',
            'parentscience',
            'person',
            'pi',
            'queue',
            'resource',
            'resource_type',
            'username',
            'qos'
        ];

        $settings = [
            'realm' => ['Jobs'],
            'dataset_type' => ['aggregate', 'timeseries'],
            'statistic' => $statistics,
            'group_by' => $groupBys,
            'aggregation_unit' => ['Day', 'Month', 'Quarter', 'Year']
        ];

        return RegressionTestHelper::generateTests($settings);
    }
}
