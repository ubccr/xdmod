<?php

namespace UnitTesting\OpenXdmod\Tests\Migration\Version800To810;

use TestHelpers\mock\MockXDUserProfile;

class DatabasesMigrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider migrationDataProvider
     */
    public function testMetricExplorerQueryMigrations($input, $expected) {

        // Note - the profile may be modifed by the call to migrateMetricExplorerQueries
        // so create a fresh one for each test case
        $profile = new MockXDUserProfile;
        
        foreach($input as $key => $value) {
            $profile->setValue($key, $value);
        }

        $mockuser = $this->getMockBuilder('\XDUser')
                    ->disableOriginalConstructor()
                    ->getMock();

        $mockuser->method('getProfile')->willReturn($profile);

        $migration = new \OpenXdmod\Migration\Version800To810\DatabasesMigration('800', '810');
        $pubmeth = \TestHelpers\TestHelper::unlockMethod($migration, 'migrateMetricExplorerQueries');

        $pubmeth->invokeArgs($migration, array($mockuser));

        $this->assertEquals($expected, $profile);
    }

    /**
     * Generate some test blobs that will be used in the various test
     * scenarios
     */
    private static function generateChartBlobs()
    {
        $pseudoChartData = array(
            array(
                'ts' => 123123433,
                'name' => 'chart1',
                'config' => 'Config for Chart 1'
            ),
            array(
                'ts' => 15675546,
                'name' => 'chart2',
                'config' => 'Config for Chart 2'
            )
        );

        $legacyData = array();

        $newData = array();
        $oldData = array(
            array(
                'recordid' => 0,
                'ts' => 156755846,
                'name' => 'Existing New Style Chart',
                'config' => 'Config for Existing New Style Chart'
            )
        );

        $newStyleSingleChart = array('maxid' => 0, 'data' => $oldData);

        $chartCount = 0;
        foreach ($pseudoChartData as $chartData) {
            $legacyData[$chartData['name']] = $chartData;
            $newData[] = array_merge(array('recordid' => $chartCount), $chartData);
            $oldData[] = array_merge(array('recordid' => $chartCount + 1), $chartData);
                
            $chartCount += 1;
        }

        $newStyleThreeCharts = array('maxid' => $chartCount, 'data' => $oldData);
        $newStyleTwoCharts = array('maxid' => $chartCount - 1, 'data' => $newData);
        $oldStyleTwoCharts = json_encode($legacyData);

        return array(
            $oldStyleTwoCharts,
            $newStyleSingleChart,
            $newStyleTwoCharts,
            $newStyleThreeCharts
        );
    }

    /**
     * Data sources for the tests
     */
    public function migrationDataProvider()
    {
        list($oldStyleTwoCharts, $newStyleSingleChart, $newStyleTwoCharts, $newStyleThreeCharts) = self::generateChartBlobs();

        $tests = array();

        $emptyProfile = new MockXDUserProfile();

        $singleChartProfile = new MockXDUserProfile();
        $singleChartProfile->setValue('queries_store', $newStyleSingleChart);

        $twoChartProfile = new MockXDUserProfile();
        $twoChartProfile->setValue('queries_store', $newStyleTwoCharts);

        $threeChartProfile = new MockXDUserProfile();
        $threeChartProfile->setValue('queries_store', $newStyleThreeCharts);

        $migrationMetadata = array(
            'maxid' => 0,
            'data' => array(
                array('queries_migrated' => true, 'recordid' => 0)
            )
        );
            
        // Check that the straight migration works
        $tests[] = array(
            array('queries' => $oldStyleTwoCharts),
            $twoChartProfile
        );

        // Check that migration metadata is removed correctly
        $tests[] = array(
            array('query_metadata' => $migrationMetadata),
            $emptyProfile
        );

        // Check that migration metadata is checked and no migration occurs
        $tests[] = array(
            array(
                'query_metadata' => $migrationMetadata,
                'queries' => $oldStyleTwoCharts
            ),
            $emptyProfile
        );

        // Check that no migration occurs when metadata is set even if
        // there is old data in the system.

        $tests[] = array(
            array(
                'query_metadata' => $migrationMetadata,
                'queries' => $oldStyleTwoCharts,
                'queries_store' => $newStyleSingleChart
            ),
            $singleChartProfile
        );

        // Check old and new data merge works ok
        $tests[] = array(
            array(
                'queries' => $oldStyleTwoCharts,
                'queries_store' => $newStyleSingleChart
            ),
            $threeChartProfile
        );
        return $tests;
    }
}
