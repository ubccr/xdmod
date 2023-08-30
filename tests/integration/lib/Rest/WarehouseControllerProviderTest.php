<?php

namespace IntegrationTests\Rest;

use IntegrationTests\TokenAuthTest;
use TestHarness\XdmodTestHelper;

class WarehouseControllerProviderTest extends TokenAuthTest
{
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
    public function testGetAggregateDataMalformedRequests(
        $id,
        $role,
        $input,
        $output
    ) {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function aggregateDataMalformedRequestProvider()
    {
        $validInput = [
            'path' => 'rest/warehouse/aggregatedata',
            'method' => 'get',
            'params' => $this->getAggDataParameterGenerator(),
            'data' => null
        ];
        // Run some standard endpoint tests.
        $tests = parent::provideRestEndpointTests(
            $validInput,
            [
                'param_source' => 'params',
                'authentication' => true,
                'run_as' => 'cd',
                'int_params' => ['start', 'limit']
            ]
        );
        // Test bad request parameters.
        $tests[] = [
            'invalid_config',
            'cd',
            parent::mergeParams(
                $validInput,
                'params',
                ['config' => 'foo']
            ),
            parent::validateBadRequestResponse(
                'syntax error in config parameter',
                104
            )
        ];
        $config = json_decode($validInput['params']['config'], true);
        $tests = $this->getAggregateDataMalformedParamTests(
            $tests,
            $validInput,
            $config,
            null,
            'missing_config_',
            function ($param) {
                return "Missing mandatory config property $param";
            }
        );
        $tests = $this->getAggregateDataMalformedParamTests(
            $tests,
            $validInput,
            $config,
            'order_by',
            'missing_config_order_by_',
            function ($param) {
                return 'Malformed config property order_by';
            }
        );
        return $tests;
    }

    private function getAggregateDataMalformedParamTests(
        array $tests,
        array $validInput,
        array $config,
        $key,
        $idPrefix,
        $getMessage
    ) {
        if (is_null($key)) {
            $params = $config;
        } else {
            $params = $config[$key];
        }
        foreach (array_keys($params) as $param) {
            $newConfig = $config;
            if (is_null($key)) {
                unset($newConfig[$param]);
            } else {
                unset($newConfig[$key][$param]);
            }
            $tests[] = [
                $idPrefix . $param,
                'cd',
                parent::mergeParams(
                    $validInput,
                    'params',
                    ['config' => json_encode($newConfig)]
                ),
                parent::validateBadRequestResponse($getMessage($param), 104)
            ];
        }
        return $tests;
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
    public function testGetRawData($id, $role, $tokenType, $input, $output)
    {
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
        $validInput = [
            'path' => 'rest/warehouse/raw-data',
            'method' => 'get',
            'params' => [
                'start_date' => '2017-01-01',
                'end_date' => '2017-01-01',
                'realm' => 'Jobs'
            ],
            'data' => null,
            'endpoint_type' => 'rest',
            'authentication_type' => 'token_required'
        ];
        // Run some standard endpoint tests.
        $tests = parent::provideRestEndpointTests(
            $validInput,
            [
                'param_source' => 'params',
                'token_auth' => true,
                'run_as' => 'usr',
                'int_params' => ['offset'],
                'date_params' => ['start_date', 'end_date']
            ]
        );
        foreach (parent::provideTokenAuthTestData() as $testData) {
            list($role, $tokenType) = $testData;
            // Run some standard token auth tests.
            if ('valid_token' !== $tokenType) {
                $tests[] = [
                    'default',
                    $role,
                    $tokenType,
                    $validInput,
                    []
                ];
            } else {
                $testData = [];
                // Test bad request parameters.
                array_push(
                    $testData,
                    [
                        'end_before_start',
                        ['end_date' => '2016-01-01'],
                        parent::validateBadRequestResponse(
                            'End date cannot be less than start date.',
                            104
                        )
                    ],
                    [
                        'invalid_realm',
                        ['realm' => 'foo'],
                        parent::validateBadRequestResponse(
                            'Invalid realm.',
                            104
                        )
                    ],
                    [
                        'invalid_fields',
                        ['fields' => 'foo,bar;'],
                        parent::validateBadRequestResponse(
                            "Invalid fields specified: 'foo', 'bar;'.",
                            104
                        )
                    ],
                    [
                        'invalid_filter_key',
                        ['filters[foo]' => '177'],
                        parent::validateBadRequestResponse(
                            "Invalid filter key 'foo'.",
                            104
                        )
                    ],
                    [
                        'negative_offset',
                        ['offset' => -1],
                        parent::validateBadRequestResponse(
                            "Offset must be non-negative.",
                            104
                        )
                    ],
                    [
                        'success_0',
                        [],
                        $this->generateGetDataSuccessBodyValidator(10000, null)
                    ],
                    [
                        'success_16500',
                        ['offset' => 16500],
                        $this->generateGetDataSuccessBodyValidator(66, null)
                    ],
                    [
                        'success_fields_and_filters',
                        [
                            'fields' => 'Nodes,Wall Time',
                            'filters[resource]' => '1,2',
                            'filters[fieldofscience]' => '10,91'
                        ],
                        $this->generateGetDataSuccessBodyValidator(
                            29,
                            ['Nodes', 'Wall Time']
                        )
                    ]
                );
                foreach ($testData as $t) {
                    list($id, $params, $output) = $t;
                    $tests[] = [
                        $id,
                        $role,
                        $tokenType,
                        parent::mergeParams(
                            $validInput,
                            'params',
                            $params
                        ),
                        $output
                    ];
                }
            }
        }
        return $tests;
    }

    private function generateGetDataSuccessBodyValidator(
        $count,
        $expectedFields
    ) {
        return parent::validateSuccessResponse(
            function ($body, $assertMessage) use ($count, $expectedFields) {
                $this->assertCount($count, $body['data'], $assertMessage);
                if (!is_null($expectedFields)) {
                    $this->assertEquals(
                        $expectedFields,
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
                    if (!is_null($expectedFields)) {
                        $this->assertSame(
                            count($expectedFields),
                            count($dataRow),
                            $assertMessage
                        );
                    }
                }
            }
        );
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
