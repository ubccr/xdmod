<?php

namespace IntegrationTests\Rest;

use IntegrationTests\TokenAuthTest;
use TestHarness\XdmodTestHelper;

class WarehouseControllerProviderTest extends TokenAuthTest
{
    /**
     * Directory containing test artifact files.
     */
    const TEST_GROUP = 'integration/rest/warehouse';

    private static $helper;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$helper = new XdmodTestHelper();
    }

    /**
     * The warehouse/aggregate data endpoint uses a json encoded configuration
     * settings. This helper function generates a valid set of parameters for the
     * endpoint.
     */
    protected function getAggDataParameterGenerator($configOverrides = array())
    {
        $config = array(
            'realm' => 'Jobs',
            'group_by' => 'person',
            'statistics' => array('job_count', 'total_cpu_hours'),
            'aggregation_unit' => 'day',
            'start_date' => '2016-12-01',
            'end_date' => '2017-01-01',
            'order_by' => array(
                'field' => 'job_count',
                'dirn' => 'asc'
            )
        );

        $config = array_merge($config, $configOverrides);

        return array(
            'start' => 0,
            'limit' => 10,
            'config' => json_encode($config)
        );
    }


    public function aggregateDataMalformedRequestProvider()
    {
        $inputs = array();

        $inputs[] = array('cd', array('start' => 0, 'limit' => 10));
        $inputs[] = array('cd', array('start' => 0, 'limit' => 10, 'config' => ''));
        $inputs[] = array('cd', array('start' => 0, 'limit' => 10, 'config' => '{"realm": "Jobs"}'));
        $inputs[] = array('cd', array('start' => 0, 'limit' => 10, 'config' => 'not json data'));
        $inputs[] = array('cd', array('start' => 'smart', 'limit' => 'asdf', 'config' => '{"realm": "Jobs"}'));

        return $inputs;
    }

    public function aggregateDataAccessControlsProvider()
    {
        $inputs = array();

        $inputs[] = array('pub', 401, $this->getAggDataParameterGenerator());
        $inputs[] = array('cd', 403, $this->getAggDataParameterGenerator(array('statistics' => array('unavaiablestat'))));
        $inputs[] = array('cd', 403, $this->getAggDataParameterGenerator(array('group_by' => 'turnips')));
        $inputs[] = array('cd', 403, $this->getAggDataParameterGenerator(array('realm' => 'Agricultural')));

        return $inputs;
    }

    /**
     * @dataProvider aggregateDataMalformedRequestProvider
     */
    public function testGetAggregateDataMalformedRequests($user, $params)
    {
        self::$helper->authenticate($user);
        $response = self::$helper->get('rest/warehouse/aggregatedata', $params);

        $this->assertEquals(400, $response[1]['http_code']);
        $this->assertFalse($response[0]['success']);
        self::$helper->logout();
    }

    /**
     *  @dataProvider aggregateDataAccessControlsProvider
     */
    public function testGetAggregateDataAccessControls($user, $http_code, $params)
    {
        if ('pub' !== $user) {
            self::$helper->authenticate($user);
        }
        $response = self::$helper->get('rest/warehouse/aggregatedata', $params);

        $this->assertEquals($http_code, $response[1]['http_code']);
        $this->assertFalse($response[0]['success']);
        self::$helper->logout();
    }

    public function testGetAggregateData()
    {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", parent::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $params = $this->getAggDataParameterGenerator();

        self::$helper->authenticate('cd');
        $response = self::$helper->get('rest/warehouse/aggregatedata', $params);

        $this->assertEquals(200, $response[1]['http_code']);
        $this->assertTrue($response[0]['success']);
        $this->assertCount($params['limit'], $response[0]['results']);
        $this->assertEquals(66, $response[0]['total']);
        self::$helper->logout();
    }

    /**
     * Confirm that a filter can be applied to the aggregatedata endpoint
     */
    public function testGetAggregateWithFilters()
    {
        if (!in_array("jobs", parent::$XDMOD_REALMS)) {
            $this->markTestSkipped('This test requires the Jobs realm');
        }

        $params = $this->getAggDataParameterGenerator(array('filters' => array('jobsize' => 1)));

        self::$helper->authenticate('cd');
        $response = self::$helper->get('rest/warehouse/aggregatedata', $params);

        $this->assertEquals(200, $response[1]['http_code']);
        $this->assertTrue($response[0]['success']);
        $this->assertCount($params['limit'], $response[0]['results']);
        $this->assertEquals(23, $response[0]['total']);
        self::$helper->logout();
    }

    /**
     * @dataProvider provideGetRawData
     */
    public function testGetRawData($id, $role, $tokenType, $params, $output)
    {
        $input = [
            'path' => 'rest/warehouse/raw-data',
            'method' => 'get',
            'params' => $params,
            'data' => null,
            'endpoint_type' => 'rest',
            'authentication_type' => 'token_required'
        ];
        parent::runTokenAuthTest(
            $role,
            $tokenType,
            $input,
            $output
        );
    }

    /**
     * dataProvider for @see testGetRawData().
     */
    public function provideGetRawData()
    {
        $validStartDate = '2017-01-01';
        $validEndDate = '2017-01-01';
        $endDateBeforeStart = '2016-01-01';
        $validRealm = 'Jobs';
        $validFields = 'Nodes,Wall Time';
        $generateSuccessBodyValidator = function ($count, $fields) {
            return parent::validateSuccessResponse(
                function ($body, $assertMessage) use ($count, $fields) {
                    $this->assertCount($count, $body['data'], $assertMessage);
                    if (!is_null($fields)) {
                        $this->assertEquals(
                            $fields,
                            $body['fields'],
                            $assertMessage
                        );
                    }
                    foreach ($body['fields'] as $field) {
                        $this->assertInternalType(
                            'string',
                            $field,
                            $assertMessage
                        );
                    }
                    foreach ($body['data'] as $dataRow) {
                        foreach ($dataRow as $field) {
                            $this->assertInternalType(
                                'string',
                                $field,
                                $assertMessage
                            );
                        }
                        if (!is_null($fields)) {
                            $this->assertSame(
                                count($fields),
                                count($dataRow),
                                $assertMessage
                            );
                        }
                    }
                }
            );
        };
        $tests = [];
        foreach (parent::provideTokenAuthTestData() as $testData) {
            list($role, $tokenType) = $testData;
            array_push(
                $tests,
                [
                    'default',
                    $role,
                    $tokenType,
                    null,
                    parent::validateMissingRequiredParameterResponse(
                        'start_date'
                    )
                ]
            );
            // Only run the non-default valid token tests for one non-public
            // user to make the tests take less time overall.
            if ('usr' === $role && 'valid_token' === $tokenType) {
                array_push(
                    $tests,
                    [
                        'start_date_malformed',
                        $role,
                        $tokenType,
                        ['start_date' => '2017'],
                        parent::validateInvalidDateParameterResponse(
                            'start_date'
                        )
                    ],
                    [
                        'no_end_date',
                        $role,
                        $tokenType,
                        ['start_date' => $validStartDate],
                        parent::validateMissingRequiredParameterResponse(
                            'end_date'
                        )
                    ],
                    [
                        'end_date_malformed',
                        $role,
                        $tokenType,
                        [
                            'start_date' => $validStartDate,
                            'end_date' => '2017'
                        ],
                        parent::validateInvalidDateParameterResponse(
                            'end_date'
                        )
                    ],
                    [
                        'end_before_start',
                        $role,
                        $tokenType,
                        [
                            'start_date' => $validStartDate,
                            'end_date' => $endDateBeforeStart
                        ],
                        parent::validateBadRequestResponse(
                            'End date cannot be less than start date.',
                            104
                        )
                    ],
                    [
                        'no_realm',
                        $role,
                        $tokenType,
                        [
                            'start_date' => $validStartDate,
                            'end_date' => $validEndDate
                        ],
                        parent::validateMissingRequiredParameterResponse(
                            'realm'
                        )
                    ],
                    [
                        'invalid_realm',
                        $role,
                        $tokenType,
                        [
                            'start_date' => $validStartDate,
                            'end_date' => $validEndDate,
                            'realm' => 'foo'
                        ],
                        parent::validateBadRequestResponse(
                            'Invalid realm.',
                            104
                        )
                    ],
                    [
                        'invalid_fields',
                        $role,
                        $tokenType,
                        [
                            'start_date' => $validStartDate,
                            'end_date' => $validEndDate,
                            'realm' => $validRealm,
                            'fields' => 'foo,bar;'
                        ],
                        parent::validateBadRequestResponse(
                            "Invalid fields specified: 'foo', 'bar;'.",
                            104
                        )
                    ],
                    [
                        'invalid_filter_key',
                        $role,
                        $tokenType,
                        [
                            'start_date' => $validStartDate,
                            'end_date' => $validEndDate,
                            'realm' => $validRealm,
                            'fields' => $validFields,
                            'filters[foo]' => '177'
                        ],
                        parent::validateBadRequestResponse(
                            "Invalid filter key 'foo'.",
                            104
                        )
                    ],
                    [
                        'negative_offset',
                        $role,
                        $tokenType,
                        [
                            'start_date' => $validStartDate,
                            'end_date' => $validEndDate,
                            'realm' => $validRealm,
                            'offset' => -1
                        ],
                        parent::validateBadRequestResponse(
                            "Offset must be non-negative.",
                            104
                        )
                    ],
                    [
                        'success_0',
                        $role,
                        $tokenType,
                        [
                            'start_date' => $validStartDate,
                            'end_date' => $validEndDate,
                            'realm' => $validRealm
                        ],
                        $generateSuccessBodyValidator(10000, null)
                    ],
                    [
                        'success_16500',
                        $role,
                        $tokenType,
                        [
                            'start_date' => $validStartDate,
                            'end_date' => $validEndDate,
                            'realm' => $validRealm,
                            'offset' => 16500
                        ],
                        $generateSuccessBodyValidator(66, null)
                    ],
                    [
                        'success_fields_and_filters',
                        $role,
                        $tokenType,
                        [
                            'start_date' => $validStartDate,
                            'end_date' => $validEndDate,
                            'realm' => $validRealm,
                            'fields' => $validFields,
                            'filters[resource]' => '1,2',
                            'filters[fieldofscience]' => '10,91'
                        ],
                        $generateSuccessBodyValidator(
                            29,
                            ['Nodes', 'Wall Time']
                        )
                    ]
                );
            }
        }
        return $tests;
    }

    /**
     * @dataProvider provideTokenAuthTestData
     */
    public function testGetRawDataLimit($role, $tokenType)
    {
        parent::runTokenAuthTest(
            $role,
            $tokenType,
            [
                'path' => 'rest/warehouse/raw-data/limit',
                'method' => 'get',
                'params' => null,
                'data' => null,
                'endpoint_type' => 'rest',
                'authentication_type' => 'token_required'
            ],
            parent::validateSuccessResponse(function ($body, $assertMessage) {
                $this->assertGreaterThan(
                    0,
                    $body['data'],
                    $assertMessage
                );
            })
        );
    }
}
