<?php

namespace IntegrationTests\Rest;

class WarehouseControllerTest extends \PHPUnit_Framework_TestCase
{
    protected static $helpers = array();

    public static function setUpBeforeClass()
    {
        foreach (array('pub', 'cd') as $user) {
            self::$helpers[$user] = new \TestHarness\XdmodTestHelper();
            if ($user != 'pub') {
                self::$helpers[$user]->authenticate($user);
            }
        }
    }

    public static function tearDownAfterClass()
    {
        foreach (self::$helpers as $helper) {
            $helper->logout();
        }
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
        $response = self::$helpers[$user]->get('rest/warehouse/aggregatedata', $params);

        $this->assertEquals(400, $response[1]['http_code']);
        $this->assertFalse($response[0]['success']);
    }

    /**
     *  @dataProvider aggregateDataAccessControlsProvider
     */
    public function testGetAggregateDataAccessControls($user, $http_code, $params)
    {
        $response = self::$helpers[$user]->get('rest/warehouse/aggregatedata', $params);

        $this->assertEquals($http_code, $response[1]['http_code']);
        $this->assertFalse($response[0]['success']);
    }

    public function testGetAggregateData()
    {
        $params = $this->getAggDataParameterGenerator();

        $response = self::$helpers['cd']->get('rest/warehouse/aggregatedata', $params);

        $this->assertEquals(200, $response[1]['http_code']);
        $this->assertTrue($response[0]['success']);
        $this->assertCount($params['limit'], $response[0]['results']);
        $this->assertEquals(66, $response[0]['total']);
    }
}
