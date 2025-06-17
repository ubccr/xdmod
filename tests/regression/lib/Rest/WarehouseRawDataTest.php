<?php

namespace RegressionTests\Rest;

use IntegrationTests\BaseTest;
use RegressionTests\TestHarness\RegressionTestHelper;

/**
 * Test for regressions in getting raw data from the warehouse.
 */
class WarehouseRawDataTest extends BaseTest
{
    /**
     * @var RegressionTestHelper
     */
    private static $helper;

    /**
     * Create the helper.
     */
    public static function setupBeforeClass(): void
    {
        self::$helper = new RegressionTestHelper();
    }

    /**
     * Test getting raw data from the warehouse.
     *
     * @dataProvider getRawDataProvider
     */
    public function testGetRawData($testName, $input)
    {
        $this->assertTrue(self::$helper->checkRawData($testName, $input, True));
    }

    public function getRawDataProvider()
    {
        $realmParams = [
            'jobs' => [
                'base' => [
                    'start_date' => '2016-12-30',
                    'end_date' => '2017-01-01',
                    'realm' => 'Jobs',
                    'offset' => '70002'
                ],
                'fields_and_filters' => [
                    'fields' => 'Local Job Id,Resource,PI Group',
                    'filters[resource]' => '1,2',
                    'filters[fieldofscience]' => '10,91',
                    'offset' => '2'
                ]
            ],
            'cloud' => [
                'base' => [
                    'start_date' => '2018-04-29',
                    'end_date' => '2019-06-26',
                    'realm' => 'Cloud',
                    'offset' => '2'
                ],
                'fields_and_filters' => [
                    'fields' => 'Instance ID,PI Group,Instance Type',
                    'filters[fieldofscience]' => '59,115',
                    'filters[configuration]' => 'm1.small,m1.medium'
                ]
            ],
            'resourcespecifications' => [
                'base' => [
                    'start_date' => '2016-12-21',
                    'end_date' => '2017-01-07',
                    'realm' => 'ResourceSpecifications',
                    'offset' => '0'
                ],
                'fields_and_filters' => [
                    'fields' => 'Resource ID,Resource',
                    'filters[resource]' => '1,2',
                    'filters[resource_type]' => '1'
                ]
            ]
        ];
        return RegressionTestHelper::getRawDataTestParams($realmParams);
    }
}
