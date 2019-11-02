<?php

namespace IntegrationTests\Controllers;

use IntegrationTests\BaseTest;

class MetricExplorerTest extends BaseTest
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
        //TODO: Needs further integration for other realms
        if (!in_array("Jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

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

    public function rawDataProvider()
    {
        $params = array (
            'show_title' => 'y',
            'timeseries' => 'y',
            'aggregation_unit' => 'Auto',
            'start_date' => '2016-12-28',
            'end_date' => '2017-01-01',
            'global_filters' => array(
                'data' => array(
                    array(
                        'checked' => true,
                        'value_id' => '110',
                        'dimension_id' => 'person'
                    ),
                 ),
            ),
            'title' => 'untitled query 1',
            'show_filters' => 'true',
            'show_warnings' => 'true',
            'show_remainder' => 'false',
            'start' => '0',
            'limit' => '20',
            'timeframe_label' => 'User Defined',
            'operation' => 'get_rawdata',
            'data_series' => array (
                array(
                'group_by' => 'person',
                'color' => 'auto',
                'log_scale' => false,
                'std_err' => false,
                'value_labels' => false,
                'display_type' => 'line',
                'combine_type' => 'side',
                'sort_type' => 'value_desc',
                'ignore_global' => false,
                'long_legend' => true,
                'x_axis' => false,
                'has_std_err' => false,
                'trend_line' => false,
                'line_type' => 'Solid',
                'line_width' => 2,
                'shadow' => false,
                'filters' => array (
                    'data' => array (),
                    'total' => 0,
                ),
                'z_index' => 0,
                'visibility' => null,
                'enabled' => true,
                'metric' => 'job_count',
                'realm' => 'Jobs',
                'category' => 'Jobs',
                'id' => 0.41070416068466,
                ),
            ),
            'swap_xy' => 'false',
            'share_y_axis' => 'false',
            'hide_tooltip' => 'false',
            'show_guide_lines' => 'y',
            'showContextMenu' => 'y',
            'scale' => '1',
            'format' => 'jsonstore',
            'width' => '1884',
            'height' => '700',
            'legend_type' => 'bottom_center',
            'font_size' => '3',
            'featured' => 'false',
            'trendLineEnabled' => '',
            'controller_module' => 'metric_explorer',
            'inline' => 'n',
            'datapoint' => '1483056000000',
            'datasetId' => '0.41070416068466'
        );

        $params['global_filters'] = urlencode(json_encode($params['global_filters']));
        $params['data_series'] = urlencode(json_encode($params['data_series']));

        $tests = array();

        $tests[] = array($params, 20, true);

        $params['limit'] = '1000\'; DROP TABLE Users;';
        $tests[] = array($params, 712, true);

        unset($params['limit']);
        $tests[] = array($params, 712, true);

        $params['start'] = 40;
        $params['limit'] = 40;
        $tests[] = array($params, 40, false);

        $params['start'] = -10;
        $params['limit'] = -1;
        $tests[] = array($params, 712, true);

        unset($params['start']);
        $params['limit'] = 10;
        $tests[] = array($params, 712, true);

        return $tests;
    }

    /**
     * @dataProvider rawDataProvider
     */
    public function testGetRawData($params, $limit, $shouldHaveTotalAvail)
    {
        // Jobs realm specific test
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $this->helper->authenticate('cd');

        $response = $this->helper->post('/controllers/metric_explorer.php', null, $params);

        $this->assertArrayHasKey('data', $response[0]);
        $this->assertCount($limit, $response[0]['data']);

        if ($shouldHaveTotalAvail) {
            $this->assertArrayHasKey('totalAvailable', $response[0]);
        } else {
            $this->assertArrayNotHasKey('totalAvailable', $response[0]);
        }
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
