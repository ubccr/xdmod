<?php
/**
 * @package OpenXdmod
 * @subpackage Tests
 */

namespace Rest;

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
    public static function setUpBeforeClass()
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
        $this->assertTrue(self::$helper->checkRawData($testName, $input));
    }

    public function getRawDataProvider()
    {
        $realmParams = [
            'jobs' => [
                'base' => [
                    'start_date' => '2016-12-30',
                    'end_date' => '2017-01-01',
                    'realm' => 'Jobs',
                    'offset' => '2'
                ],
                'fields_and_filters' => [
                    'fields' => 'Local Job Id,Resource,PI Group',
                    'filters[resource]' => '1,2',
                    'filters[fieldofscience]' => '10,91'
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
            ]
        ];
        return RegressionTestHelper::getRawDataTestParams($realmParams);
    }
}
