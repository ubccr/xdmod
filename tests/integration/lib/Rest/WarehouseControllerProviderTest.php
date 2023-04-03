<?php

namespace IntegrationTests\Rest;

use IntegrationTests\BaseTest;
use TestHarness\TokenHelper;
use TestHarness\XdmodTestHelper;

class WarehouseControllerProviderTest extends BaseTest
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
     * @dataProvider provideGetRawDataRoles
     */
    public function testGetRawData($role, $tokenTests)
    {
        $tokenHelper = new TokenHelper(
            $this,
            self::$helper,
            $role,
            'rest/warehouse/raw-data',
            'get',
            null,
            null,
            'rest',
            'token_required'
        );
        $tokenHelper->runEndpointTests(
            function ($token) use ($tokenTests, $tokenHelper) {
                foreach ($tokenTests as $values) {
                    list(
                        $params,
                        $httpCode,
                        $fileName,
                        $validationType
                    ) = $values;
                    $tokenHelper->setParams($params);
                    $tokenHelper->runEndpointTest(
                        $token,
                        $fileName,
                        $httpCode,
                        'integration/rest/warehouse',
                        $validationType
                    );
                }
            }
        );
    }

    /**
     * @dataProvider provideBaseRoles
     */
    public function testGetRawDataLimit($role)
    {
        $tokenHelper = new TokenHelper(
            $this,
            self::$helper,
            $role,
            'rest/warehouse/raw-data/limit',
            'get',
            null,
            null,
            'rest',
            'token_required'
        );
        $tokenHelper->runEndpointTests(
            function ($token) use ($tokenHelper) {
                $tokenHelper->runEndpointTest(
                    $token,
                    'get_raw_data/limit_success',
                    200,
                    'integration/rest/warehouse',
                    'exact'
                );
            }
        );
    }

    public function provideGetRawDataRoles()
    {
        $tokenTests = array(
            array(null, 400, 'get_raw_data/no_start_date', 'exact'),
            array(
                array('start_date' => '2017-01-01'),
                400,
                'get_raw_data/no_end_date',
                'exact'
            ),
            array(
                array('start_date' => '2017'),
                400,
                'get_raw_data/start_date_malformed',
                'exact'
            ),
            array(
                array(
                    'start_date' => '2017-01-01',
                    'end_date' => '2017-01-01'
                ),
                400,
                'get_raw_data/no_realm',
                'exact'
            ),
            array(
                array(
                    'start_date' => '2017-01-01',
                    'end_date' => '2017'
                ),
                400,
                'get_raw_data/end_date_malformed',
                'exact'
            ),
            array(
                array(
                    'start_date' => '2017-01-01',
                    'end_date' => '2016-01-01'
                ),
                400,
                'get_raw_data/end_before_start',
                'exact'
            ),
            array(
                array(
                    'start_date' => '2017-01-01',
                    'end_date' => '2017-01-01',
                    'realm' => 'asdf'
                ),
                400,
                'get_raw_data/invalid_realm',
                'exact'
            ),
            array(
                array(
                    'start_date' => '2017-01-01',
                    'end_date' => '2017-01-01',
                    'realm' => 'Jobs',
                    'fields' => 'asdf'
                ),
                400,
                'get_raw_data/invalid_fields',
                'exact'
            ),
            array(
                array(
                    'start_date' => '2017-01-01',
                    'end_date' => '2017-01-01',
                    'realm' => 'Jobs',
                    'fields' => 'Nodes',
                    'filters[asdf]' => 177
                ),
                400,
                'get_raw_data/invalid_filter_key',
                'exact'
            ),
            array(
                array(
                    'start_date' => '2017-01-01',
                    'end_date' => '2017-01-01',
                    'realm' => 'Jobs',
                    'offset' => '-1'
                ),
                400,
                'get_raw_data/negative_offset',
                'exact'
            ),
            array(
                array(
                    'start_date' => '2017-01-01',
                    'end_date' => '2017-01-01',
                    'realm' => 'Jobs'
                ),
                200,
                'get_raw_data/success_0.spec',
                'schema'
            ),
            array(
                array(
                    'start_date' => '2017-01-01',
                    'end_date' => '2017-01-01',
                    'realm' => 'Jobs',
                    'offset' => '16500'
                ),
                200,
                'get_raw_data/success_16500.spec',
                'schema'
            ),
            array(
                array(
                    'start_date' => '2017-01-01',
                    'end_date' => '2017-01-01',
                    'realm' => 'Jobs',
                    'fields' => 'Nodes,Wall Time',
                    'filters[resource]' => '1,2',
                    'filters[fieldofscience]' => '10,91'
                ),
                200,
                'get_raw_data/success_fields_and_filters.spec',
                'schema'
            )
        );
        return array(
            array('pub', $tokenTests),
            array('usr', $tokenTests)
        );
    }
}
