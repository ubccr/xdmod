<?php

namespace RegressionTests\Controllers;

use RegressionTests\TestHarness\RegressionTestHelper;

/**
 * Test the usage explorer for storage realm regressions.
 */
class UsageExplorerStorageTest extends aUsageExplorerTest
{
    public function csvExportProvider()
    {
        $statistics = [
            'avg_file_count',
            'avg_logical_usage',
            'avg_physical_usage',
            'avg_logical_utilization',
            'avg_hard_threshold',
            'avg_soft_threshold',
            'user_count'
        ];

        $groupBys = [
            'none',
            'nsfdirectorate',
            'parentscience',
            'fieldofscience',
            'mountpoint',
            'pi',
            'resource',
            'resource_type',
            'username',
            'person'
        ];

        $settings = [
            'realm' => ['Storage'],
            'dataset_type' => ['aggregate', 'timeseries'],
            'statistic' => $statistics,
            'group_by' => $groupBys,
            'aggregation_unit' => ['Day', 'Month', 'Quarter', 'Year']
        ];

        return RegressionTestHelper::generateTests($settings, '2018-12-27', '2019-01-05');
    }
}
