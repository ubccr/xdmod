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

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 200);

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

    public function chartDataProvider()
    {
        $emptyChart = <<< EOF
{
   "featured": false,
   "trend_line": false,
   "x_axis": {},
   "y_axis": {},
   "legend": {},
   "defaultDatasetConfig": {  
      "display_type": "column"
   },
   "swap_xy": false,
   "share_y_axis": false,
   "hide_tooltip": true,
   "show_remainder": false,
   "timeseries": false,
   "title": "Test",
   "legend_type": "bottom_center",
   "font_size": 3,
   "show_filters": true,
   "show_warnings": true,
   "data_series": {  
      "data": [ ],
      "total": 0
   },
   "aggregation_unit": "Auto",
   "global_filters": {  
      "data": [ ],
      "total": 0
   },
   "timeframe_label": "Previous month",
   "start_date": "2017-08-01",
   "end_date": "2017-08-31",
   "start": 0,
   "limit": 10
}
EOF;
        return array(
            array($emptyChart)
        );
    }
}
