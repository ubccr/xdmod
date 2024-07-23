<?php

namespace IntegrationTests\Rest;

use IntegrationTests\TokenAuthTest;
use IntegrationTests\TestHarness\XdmodTestHelper;

class WarehouseControllerProviderTest extends TokenAuthTest
{
    private static $helper;

    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();
        self::$helper = new XdmodTestHelper();
    }

    /**
     * @dataProvider provideGetSearchHistory
     */
    public function testGetSearchHistory($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideGetSearchHistory()
    {
        $validInput = [
            'path' => 'rest/warehouse/search/history',
            'method' => 'get',
            'params' => [],
            'data' => null
        ];
        $tests = parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'int_params' => ['nodeid', 'infoid', 'jobid', 'recordid'],
                'string_params' => ['tsid', 'realm', 'title']
            ]
        );
        $tests[] = [
            'get_history_by_title_not_found',
            'cd',
            parent::mergeParams(
                $validInput,
                'params',
                [
                    'realm' => 'Jobs',
                    'title' => 'foo'
                ]
            ),
            parent::validateNotFoundResponse('')
        ];
        $leafTest = [
            'process_job_node_time_series_request_leaf',
            'cd',
            parent::mergeParams(
                $validInput,
                'params',
                [
                    'nodeid' => '0',
                    'tsid' => 'foo',
                    'infoid' => '0',
                    'jobid' => '0',
                    'recordid' => '0',
                    'realm' => 'foo'
                ]
            ),
            parent::validateBadRequestResponse('Node 0 is a leaf')
        ];
        $tests[] = $leafTest;
        // Run the same test again with a different name and the 'nodeid'
        // parameter removed.
        $leafTest2 = $leafTest;
        $leafTest2[0] = 'process_job_time_series_request_leaf';
        unset($leafTest2[2]['params']['nodeid']);
        $tests[] = $leafTest2;
        // Run the same test again with a different name, the 'tsid'
        // parameter removed, and a different expected response body.
        $leafTest3 = $leafTest2;
        $leafTest3[0] = 'process_job_request_leaf';
        unset($leafTest3[2]['params']['tsid']);
        $leafTest3[3] = parent::validateBadRequestResponse('Node is a leaf');
        $tests[] = $leafTest3;
        return $tests;
    }

    /**
     * @dataProvider provideCreateSearchHistory
     */
    public function testCreateSearchHistory($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideCreateSearchHistory()
    {
        $validInput = [
            'path' => 'rest/warehouse/search/history',
            'method' => 'post',
            'params' => null,
            'data' => [
                'realm' => 'Jobs',
                'data' => '{"text":"foo"}'
            ]
        ];
        // Run some standard endpoint tests.
        $tests = parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'int_params' => ['recordid'],
                'string_params' => ['realm', 'data']
            ]
        );
        $tests = $this->provideSearchParamsMalformedDataTests(
            $tests,
            $validInput
        );
        return $tests;
    }

    private function provideSearchParamsMalformedDataTests($tests, $validInput)
    {
        $paramSets = [
            ['id' => 'data_invalid_json', 'data' => 'foo'],
            ['id' => 'data_missing_text', 'data' => '{}']
        ];
        foreach ($paramSets as $paramSet) {
            $tests[] = [
                $paramSet['id'],
                'usr',
                parent::mergeParams(
                    $validInput,
                    'data',
                    ['data' => $paramSet['data']]
                ),
                parent::validateBadRequestResponse(
                    'Malformed request. Expected \'data.text\' to be present.'
                )
            ];
        }
        return $tests;
    }

    /**
     * @dataProvider provideGetHistoryById
     */
    public function testGetHistoryById($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideGetHistoryById()
    {
        $validInput = [
            'path' => 'rest/warehouse/search/history/0',
            'method' => 'get',
            'params' => ['realm' => 'Jobs'],
            'data' => null
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'string_params' => ['realm']
            ]
        );
    }

    /**
     * @dataProvider provideUpdateHistory
     */
    public function testUpdateHistory($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideUpdateHistory()
    {
        $validInput = [
            'path' => 'rest/warehouse/search/history/0',
            'method' => 'post',
            'params' => null,
            'data' => [
                'realm' => 'Jobs',
                'data' => '{"text":"foo"}'
            ]
        ];
        // Run some standard endpoint tests.
        $tests = parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'string_params' => ['realm', 'data']
            ]
        );
        $tests = $this->provideSearchParamsMalformedDataTests(
            $tests,
            $validInput
        );
        return $tests;
    }

    /**
     * @dataProvider provideDeleteHistory
     */
    public function testDeleteHistory($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideDeleteHistory()
    {
        $validInput = [
            'path' => 'rest/warehouse/search/history/0',
            'method' => 'delete',
            'params' => ['realm' => 'Jobs'],
            'data' => null
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'string_params' => ['realm']
            ]
        );
    }

    /**
     * @dataProvider provideDeleteAllHistory
     */
    public function testDeleteAllHistory($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideDeleteAllHistory()
    {
        $validInput = [
            'path' => 'rest/warehouse/search/history',
            'method' => 'delete',
            'params' => ['realm' => 'Jobs'],
            'data' => null
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'string_params' => ['realm']
            ]
        );
    }

    /**
     * @dataProvider provideSearchJobs
     */
    public function testSearchJobs($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideSearchJobs()
    {
        $validInput = [
            'path' => 'rest/warehouse/search/jobs',
            'method' => 'get',
            'params' => [
                'realm' => 'Jobs',
                'params' => '{"foo":"bar"}',
                'start_date' => '2017-01-01',
                'end_date' => '2017-01-01',
                'start' => '0',
                'limit' => '0'
            ],
            'data' => null
        ];
        $tests = parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'int_params' => ['start', 'limit'],
                'string_params' => [
                    'realm',
                    'params',
                    'start_date',
                    'end_date'
                ]
            ]
        );
        $tests[] = [
            'params_invalid_json',
            'cd',
            parent::mergeParams(
                $validInput,
                'params',
                ['params' => 'foo']
            ),
            parent::validateBadRequestResponse(
                'params parameter must be valid JSON'
            )
        ];
        $tests[] = [
            'invalid_realm',
            'cd',
            parent::mergeParams(
                $validInput,
                'params',
                [
                    'realm' => 'foo',
                    'params' => '{"foo":"bar"}'
                ]
            ),
            parent::validateBadRequestResponse('Invalid realm')
        ];
        $tests[] = [
            'invalid_search_params',
            'cd',
            parent::mergeParams(
                $validInput,
                'params',
                [
                    'realm' => 'Jobs',
                    'params' => '{"foo":"bar"}'
                ]
            ),
            parent::validateBadRequestResponse(
                'Invalid search parameters specified in params object'
            )
        ];
        $tests[] = [
            'invalid_search_by_primary_key_params',
            'cd',
            parent::mergeParams(
                $validInput,
                'params',
                [
                    'realm' => 'Jobs',
                    'params' => '{"jobref":"foo"}'
                ]
            ),
            parent::validateBadRequestResponse('invalid search parameters')
        ];
        return $tests;
    }

    /**
     * @dataProvider provideSearchJobsByPeers
     */
    public function testSearchJobsByPeers($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideSearchJobsByPeers()
    {
        $validInput = [
            'path' => 'rest/warehouse/search/jobs/peers',
            'method' => 'get',
            'params' => [
                'start' => '0',
                'limit' => '0'
            ],
            'data' => null
        ];
        $tests = parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'int_params' => ['jobid', 'start', 'limit'],
                'string_params' => ['realm']
            ]
        );
        $tests[] = [
            'resource_does_not_exist',
            'cd',
            parent::mergeParams(
                $validInput,
                'params',
                [
                    'realm' => 'Jobs',
                    'jobid' => '-1'
                ]
            ),
            parent::validateNotFoundResponse(
                'The requested resource does not exist'
            )
        ];
        return $tests;
    }

    /**
     * @dataProvider provideSearchJobsByTimeseries
     */
    public function testSearchJobsByTimeseries($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideSearchJobsByTimeseries()
    {
        $validInput = [
            'path' => 'rest/warehouse/search/jobs/timeseries',
            'method' => 'get',
            'params' => ['tsid' => 'foo'],
            'data' => null
        ];
        $tests = parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'run_as' => 'cd',
                'additional_params' => [
                    'realm' => 'Cloud',
                    'jobid' => '3',
                    'format' => 'png'
                ],
                'int_params' => [
                    'jobid',
                    'nodeid',
                    'cpuid',
                    'width',
                    'height',
                    'font_size'
                ],
                'string_params' => [
                    'realm',
                    'tsid',
                    'format',
                    'scale',
                    'show_title'
                ]
            ]
        );
        $tests[] = [
            'resource_not_found',
            'cd',
            parent::mergeParams(
                $validInput,
                'params',
                ['realm' => 'Jobs']
            ),
            parent::validateNotFoundResponse(
                'The requested resource does not exist'
            )
        ];
        $tests[] = [
            'unsupported_format_type',
            'cd',
            parent::mergeParams(
                $validInput,
                'params',
                [
                    'realm' => 'Cloud',
                    'jobid' => '3',
                    'format' => 'foo'
                ]
            ),
            parent::validateBadRequestResponse('Unsupported format type.')
        ];
        return $tests;
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
                'authentication' => true,
                'int_params' => ['start', 'limit'],
                'string_params' => ['config']
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
                'syntax error in config parameter'
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
            function () {
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
                parent::validateBadRequestResponse($getMessage($param))
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
     * @dataProvider provideGetDimensions
     */
    public function testGetDimensions($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideGetDimensions()
    {
        $validInput = [
            'path' => 'rest/warehouse/dimensions',
            'method' => 'get',
            'params' => [],
            'data' => null
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'string_params' => ['realm']
            ]
        );
    }

    /**
     * @dataProvider provideGetDimensionValues
     */
    public function testGetDimensionValues($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideGetDimensionValues()
    {
        $validInput = [
            'path' => 'rest/warehouse/dimensions/resource',
            'method' => 'get',
            'params' => [],
            'data' => null
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'int_params' => ['offset', 'limit'],
                'string_params' => ['search_text', 'realm']
            ]
        );
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
                'token_auth' => true,
                'int_params' => ['offset'],
                'string_params' => ['realm', 'fields'],
                'date_params' => ['start_date', 'end_date']
            ]
        );
        $testData = [];
        // Test bad request parameters.
        array_push(
            $testData,
            [
                'end_before_start',
                ['end_date' => '2016-01-01'],
                parent::validateBadRequestResponse(
                    'End date cannot be less than start date.'
                )
            ],
            [
                'invalid_realm',
                ['realm' => 'foo'],
                parent::validateBadRequestResponse('Invalid realm.')
            ],
            [
                'invalid_fields',
                ['fields' => 'foo,bar;'],
                parent::validateBadRequestResponse(
                    "Invalid fields specified: 'foo', 'bar;'."
                )
            ],
            [
                'invalid_filter_key',
                ['filters[foo]' => '177'],
                parent::validateBadRequestResponse(
                    "Invalid filter key 'foo'."
                )
            ],
            [
                'negative_offset',
                ['offset' => -1],
                parent::validateBadRequestResponse(
                    "Offset must be non-negative."
                )
            ]
        );
        foreach ($testData as $t) {
            list($id, $params, $output) = $t;
            $tests[] = [
                $id,
                'usr',
                'valid_token',
                parent::mergeParams(
                    $validInput,
                    'params',
                    $params
                ),
                $output
            ];
        }
        return $tests;
    }


    /**
     * @dataProvider provideGetDwDescriptor
     */

    public function testGetDwDescriptor($role, $tokenType)
    {
        parent::runTokenAuthTest(
            $role,
            $tokenType,
            [
                'path' => 'rest/warehouse/search/dw_descripter',
                'method' => 'get',
                'params' => null,
                'data' => null,
                'endpoint_type' => 'rest',
                'authentication_type' => 'token_optional',
                'wantPublicUser' => true
            ],
            parent::validateSuccessResponse(function ($body, $assertMessage) {
                $this->assertSame(1, $body['totalCount'], $assertMessage);
                foreach (['Jobs', 'Cloud', 'ResourceSpecifications', 'Storage'] as $realmName) {
                    $realm = $body['data'][0]['realms'][$realmName];
                    foreach (['metrics', 'dimensions'] as $key) {
                        $this->assertArrayHasKey(
                            $key,
                            $realm,
                            $assertMessage . ": {$key} should be present in {$realmName}"
                        );
                        foreach ($realm[$key] as $elementName => $element) {
                            foreach (['text', 'info'] as $string) {
                                $this->assertIsString(
                                    $element[$string],
                                    $assertMessage . ": {$string} of {$elementName} in {$key} should be a string"
                                );
                                $this->assertNotEmpty(
                                    $element[$string],
                                    $assertMessage . ": {$string} of {$elementName} in {$key} should not be empty"
                                );
                            }
                        }
                    }
                }
            })
        );
    }

    public function provideGetDwDescriptor() {
        return [
            ['pub', 'empty_token'],
            ['pub', 'malformed_token'],
            ['usr', 'invalid_token'],
            ['usr', 'expired_token'],
            ['usr', 'revoked_token'],
            ['usr', 'valid_token']
        ];
    }

}
