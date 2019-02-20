<?php

namespace IntegrationTests\Controllers;
use Traits\UtilityFunctions;
class MetricExplorerTest extends \PHPUnit_Framework_TestCase
{
    use UtilityFunctions;
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

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);


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

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);
        $this->assertEquals(1, $response[0]['totalCount']);

        $this->helper->logout();

        $this->helper->authenticate('cd');

        $response = $this->helper->post('/controllers/metric_explorer.php', null, $params);

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);
        $this->assertGreaterThan(1, $response[0]['totalCount']);
        $totalUsers = $response[0]['totalCount'];

        $this->helper->logout();

        $this->helper->authenticate('pi');

        $response = $this->helper->post('/controllers/metric_explorer.php', null, $params);

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);
        $this->assertGreaterThan(1, $response[0]['totalCount']);
        $this->assertLessThan($totalUsers, $response[0]['totalCount']);

        $this->helper->logout();

        $response = $this->helper->post('/controllers/metric_explorer.php', null, $params);

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(401, $response[1]['http_code']);
    }

    /**
     * @dataProvider chartDataProvider
     */
    public function testChartQueryEndpoint($chartSettings)
    {
        $settings = array(
            'name' => 'Test &lt; <img src="test.gif" onerror="alert()" />',
            'ts' => microtime(true),
            'config' => $chartSettings
        );
        $this->helper->authenticate('cd');
        $response = $this->helper->post('rest/v1/metrics/explorer/queries', null, array('data' => json_encode($settings)));

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);

        $querydata = $response[0];
        $this->assertArrayHasKey('data', $querydata);
        $this->assertArrayHasKey('recordid', $querydata['data']);
        $this->assertArrayHasKey('name', $querydata['data']);
        $this->assertArrayHasKey('ts', $querydata['data']);
        $this->assertArrayHasKey('config', $querydata['data']);

        $recordid = $querydata['data']['recordid'];

        $allcharts = $this->helper->get('rest/v1/metrics/explorer/queries');
        $this->assertTrue($allcharts[0]['success']);

        $seenchart = false;
        foreach($allcharts[0]['data'] as $chart)
        {
            if ($chart['recordid'] == $recordid) {
                $this->assertEquals("Test &lt; &lt;img src=&quot;test.gif&quot; onerror=&quot;alert()&quot; /&gt;", $chart['name']);
                $seenchart = true;
            }
        }
        $this->assertTrue($seenchart);

        $justthischart = $this->helper->get('rest/v1/metrics/explorer/queries/' . $recordid);
        $this->assertTrue($justthischart[0]['success']);
        $this->assertEquals("Test &lt; &lt;img src=&quot;test.gif&quot; onerror=&quot;alert()&quot; /&gt;", $justthischart[0]['data']['name']);

        $cleanup = $this->helper->delete('rest/v1/metrics/explorer/queries/' . $recordid);
        $this->assertTrue($cleanup[0]['success']);
    }

}
