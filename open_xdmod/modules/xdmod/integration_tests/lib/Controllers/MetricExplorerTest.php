<?php

namespace IntegrationTests\Controllers;

class MetricExplorerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->helper = new \TestHarness\XdmodTestHelper();
    }

    /**
     * Checks the structure of the DwDescripter response.
     */
    public function testGetDwDescripter()
    {
        $this->helper->authenticate('cd');

        $response = $this->helper->post('/controllers/metric_explorer.php', null, array('operation' => 'get_dw_descripter'));

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 200);


        $dwdata = $response[0];

        $this->assertArrayHasKey('totalCount', $dwdata);
        $this->assertArrayHasKey('data', $dwdata);
        $this->assertEquals($dwdata['totalCount'], count($dwdata['data']));

        foreach($dwdata['data'] as $entry)
        {
            $this->assertArrayHasKey('realms', $entry);
            foreach($entry['realms'] as $realm)
            {
                $this->assertArrayHasKey('dimensions', $realm);
                $this->assertArrayHasKey('metrics', $realm);
            }
        }
    }

    /**
     * checks that you need to be authenticated to get_dw_descripter
     */
    public function testGetDwDescripterNoAuth()
    {
        // note - not authenticated

        $response = $this->helper->post('/controllers/metric_explorer.php', null, array('operation' => 'get_dw_descripter'));

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 401);
    }

    /**
     * Checks that the filters work correctly
     */
    public function testGetDimensionFilters()
    {
        $this->helper->authenticate('usr');

        $params = array(
            'operation' => 'get_dimension',
            'dimension_id' => 'username',
            'realm' => 'Jobs',
            'public_user' => false,
            'start' => '',
            'limit' => '10',
            'selectedFilterIds' => ''
        );

        $response = $this->helper->post('/controllers/metric_explorer.php', null, $params);

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 200);
        $this->assertEquals($response[0]['totalCount'], 1);

        $this->helper->logout();

        $this->helper->authenticate('cd');

        $response = $this->helper->post('/controllers/metric_explorer.php', null, $params);

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 200);
        $this->assertGreaterThan(1, $response[0]['totalCount']);
        $totalUsers = $response[0]['totalCount'];

        $this->helper->logout();

        $this->helper->authenticate('pi');

        $response = $this->helper->post('/controllers/metric_explorer.php', null, $params);

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 200);
        $this->assertGreaterThan(1, $response[0]['totalCount']);
        $this->assertLessThan($totalUsers, $response[0]['totalCount']);

        $this->helper->logout();

        $response = $this->helper->post('/controllers/metric_explorer.php', null, $params);

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 401);
    }
}
