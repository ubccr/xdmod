<?php

namespace IntegrationTests\Controllers;

use IntegrationTests\TokenAuthTest;
use TestHarness\XdmodTestHelper;

class MetricExplorerTest extends TokenAuthTest
{
    /**
     * Directory containing test artifact files.
     */
    const TEST_GROUP = 'integration/controllers/metric_explorer';

    protected function setUp()
    {
        $this->helper = new XdmodTestHelper();
    }

    /**
     * @dataProvider provideTokenAuthTestData
     */
    public function testGetDwDescripterTokenAuth($role, $tokenType) {
        parent::runTokenAuthTest(
            $role,
            $tokenType,
            self::TEST_GROUP,
            'get_dw_descripter'
        );
    }

    /**
     * @dataProvider getDimensionFiltersProvider
     */
    public function testGetDimensionFilters($role, $tokenType, $expectedCount)
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }
        $responseBody = parent::runTokenAuthTest(
            $role,
            $tokenType,
            self::TEST_GROUP,
            'get_dimensions'
        );
        if ('valid_token' === $tokenType && !is_null($expectedCount)) {
            $this->assertSame(
                $expectedCount,
                $responseBody->totalCount,
                parent::getJsonStringForExceptionMessage(
                    json_decode(json_encode($responseBody), true)
                )
            );
        }
    }

    /**
     * dataProvider for testGetDimensionFilters
     */
    public function getDimensionFiltersProvider()
    {
        $expectedCounts = [
            'pub' => null,
            'cd' => 66,
            'cs' => 66,
            'usr' => 1,
            'pi' => 6,
            'mgr' => 0
        ];
        return array_map(
            function ($testData) use ($expectedCounts) {
                list($role, $tokenType) = $testData;
                return [$role, $tokenType, $expectedCounts[$role]];
            },
            parent::provideTokenAuthTestData()
        );
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
