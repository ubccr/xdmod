<?php

namespace IntegrationTests\Controllers;

use IntegrationTests\TokenAuthTest;
use IntegrationTests\TestHarness\XdmodTestHelper;

class MetricExplorerTest extends TokenAuthTest
{
    protected function setup(): void
    {
        $this->helper = new XdmodTestHelper();
    }

    /**
     * This method is used by a few methods in this class and in the
     * UserAdminTest class.
     */
    public static function getDefaultRequestInput()
    {
        return [
            'path' => 'controllers/metric_explorer.php',
            'method' => 'post',
            'params' => null,
            'endpoint_type' => 'controller',
            'authentication_type' => 'token_optional'
        ];
    }

    /**
     * This method is used by both testGetDwDescripterTokenAuth() and
     * UserAdminTest::testGetDwDescripters().
     */
    public static function getDwDescriptersBodyValidator($testInstance)
    {
        return function ($body, $assertMessage) use ($testInstance) {
            $testInstance->assertSame(
                1,
                $body['totalCount'],
                $assertMessage
            );
            $testInstance->assertCount(
                1,
                $body['data'],
                $assertMessage
            );
            foreach (['Cloud', 'Jobs', 'Storage'] as $realmName) {
                $realm = $body['data'][0]['realms'][$realmName];
                foreach (['metrics', 'dimensions'] as $property) {
                    $testInstance->assertNotCount(
                        0,
                        $realm[$property],
                        $assertMessage
                    );
                    foreach ($realm[$property] as $item) {
                        $testInstance->assertIsString(
                            $item['text'],
                            $assertMessage
                        );
                        $testInstance->assertIsString(
                            $item['info'],
                            $assertMessage
                        );
                        if ('metrics' === $property) {
                            $testInstance->assertIsBool(
                                $item['std_err'],
                                $assertMessage
                            );
                        }
                    }
                }
                $testInstance->assertSame(
                    $realmName,
                    $realm['text'],
                    $assertMessage
                );
                $testInstance->assertSame(
                    $realmName,
                    $realm['category'],
                    $assertMessage
                );
            }
        };
    }

    /**
     * Check for correct handling of invalid or missing input parameters to the
     * metric explorer charting controller
     */
    public function testInvalidChartRequests() {

        $params = array(
            "show_title" => "y",
            "timeseries" => "y",
            "aggregation_unit" => "Auto",
            "start_date" => "2016-12-29",
            "end_date" => "2017-01-01",
            "global_filters" => "%7B%22data%22%3A%5B%5D%2C%22total%22%3A0%7D",
            "title" => "untitled query 1",
            "show_filters" => "true",
            "show_warnings" => "true",
            "show_remainder" => "false",
            "start" => "0",
            "limit" => "10",
            "timeframe_label" => "User Defined",
            "operation" => "get_data",
            "data_series" => "%5B%7B%22group_by%22%3A%22none%22%2C%22color%22%3A%22auto%22%2C%22log_scale%22%3Afalse%2C%22std_err%22%3Afalse%2C%22value_labels%22%3Afalse%2C%22display_type%22%3A%22line%22%2C%22combine_type%22%3A%22side%22%2C%22sort_type%22%3A%22value_desc%22%2C%22ignore_global%22%3Afalse%2C%22long_legend%22%3Atrue%2C%22x_axis%22%3Afalse%2C%22has_std_err%22%3Afalse%2C%22trend_line%22%3Afalse%2C%22line_type%22%3A%22Solid%22%2C%22line_width%22%3A2%2C%22shadow%22%3Afalse%2C%22filters%22%3A%7B%22data%22%3A%5B%5D%2C%22total%22%3A0%7D%2C%22z_index%22%3A0%2C%22visibility%22%3Anull%2C%22enabled%22%3Atrue%2C%22metric%22%3A%22job_count%22%2C%22realm%22%3A%22Jobs%22%2C%22category%22%3A%22Jobs%22%2C%22id%22%3A2627927508440901%7D%5D",
            "swap_xy" => "false",
            "share_y_axis" => "false",
            "hide_tooltip" => "false",
            "show_guide_lines" => "y",
            "showContextMenu" => "y",
            "scale" => "1",
            "format" => "hc_jsonstore",
            "width" => "1500",
            "height" => "626",
            "legend_type" => "bottom_center",
            "font_size" => "3",
            "featured" => "false",
            "trendLineEnabled" => "",
            "x_axis" => "%7B%7D",
            "y_axis" => "%7B%7D",
            "legend" => "%7B%7D",
            "defaultDatasetConfig" => "%7B%7D",
            "controller_module" => "metric_explorer"
        );

        $this->helper->authenticate('cd');

        unset($params['font_size']);

        $response = $this->helper->post('controllers/metric_explorer.php', null, $params);

        $output = json_decode($response[0]);
        $this->assertEquals($output->data[0]->layout->annotations[0]->font->size, "19");

        $params['data_series'] = '[object Object]';
        $response = $this->helper->post('controllers/metric_explorer.php', null, $params);
        $this->assertEquals(400, $response[1]['http_code']);
    }

    /**
     * Check for correct handling of invalid or missing input parameters to the
     * metric explorer charting controller
     */
    public function testInvalidRawDataRequests() {

        $params = array(
            'show_title' => 'y',
            'timeseries' => 'y',
            'aggregation_unit' => 'Auto',
            'start_date' => '2013-06-08',
            'end_date' => '2023-06-08',
            'global_filters' => '%7B%22data%22%3A%5B%7B%22id%22%3A%22resource%3D2%22%2C%22value_id%22%3A%222%22%2C%22value_name%22%3A%22mortorq%22%2C%22dimension_id%22%3A%22resource%22%2C%22realms%22%3A%5B%22Cloud%22%2C%22Jobs%22%2C%22Storage%22%5D%2C%22checked%22%3Atrue%7D%5D%7D',
            'title' => 'untitled query 1',
            'show_filters' => 'true',
            'show_warnings' => 'true',
            'show_remainder' => 'false',
            'start' => '0',
            'limit' => '20',
            'timeframe_label' => '10 year',
            'operation' => 'get_rawdata',
            'data_series' => '%5B%7B%22group_by%22%3A%22resource%22%2C%22color%22%3A%22auto%22%2C%22log_scale%22%3Afalse%2C%22std_err%22%3Afalse%2C%22value_labels%22%3Afalse%2C%22display_type%22%3A%22line%22%2C%22combine_type%22%3A%22side%22%2C%22sort_type%22%3A%22value_desc%22%2C%22ignore_global%22%3Afalse%2C%22long_legend%22%3Atrue%2C%22x_axis%22%3Afalse%2C%22has_std_err%22%3Afalse%2C%22trend_line%22%3Afalse%2C%22line_type%22%3A%22Solid%22%2C%22line_width%22%3A2%2C%22shadow%22%3Afalse%2C%22filters%22%3A%7B%22data%22%3A%5B%5D%2C%22total%22%3A0%7D%2C%22z_index%22%3A0%2C%22visibility%22%3Anull%2C%22enabled%22%3Atrue%2C%22metric%22%3A%22job_count%22%2C%22realm%22%3A%22Jobs%22%2C%22category%22%3A%22Jobs%22%2C%22id%22%3A-755536863343043%7D%5D',
            'swap_xy' => 'false',
            'share_y_axis' => 'false',
            'hide_tooltip' => 'false',
            'show_guide_lines' => 'y',
            'showContextMenu' => 'y',
            'scale' => '1',
            'format' => 'jsonstore',
            'width' => '1428',
            'height' => '525',
            'legend_type' => 'bottom_center',
            'x_axis' => '%7B%7D',
            'y_axis' => '%7B%7D',
            'legend' => '%7B%7D',
            'defaultDatasetConfig' => '%7B%7D',
            'controller_module' => 'metric_explorer',
            'inline' => 'n',
            'datapoint' => '1475280000000',
            'datasetId' => '-755536863343043'
        );

        $this->helper->authenticate('cd');

        unset($params['start_date']);
        $response = $this->helper->post('controllers/metric_explorer.php', null, $params);
        $this->assertFalse($response[0]['success']);
        $this->assertEquals('missing required start_date parameter', $response[0]['message']);

        $params['start_date'] = '2016-12-29';
        unset($params['end_date']);
        $response = $this->helper->post('controllers/metric_explorer.php', null, $params);
        $this->assertFalse($response[0]['success']);
        $this->assertEquals('missing required end_date parameter', $response[0]['message']);

        $params['end_date'] = '2016-12-29';
        $params['data_series'] = '[object Object]';
        $response = $this->helper->post('controllers/metric_explorer.php', null, $params);
        $this->assertFalse($response[0]['success']);
        $this->assertEquals('Invalid data_series specified', $response[0]['message']);
    }

    /**
     * @dataProvider provideTokenAuthTestData
     */
    public function testGetDwDescripterTokenAuth($role, $tokenType) {
        parent::runTokenAuthTest(
            $role,
            $tokenType,
            array_replace(
                self::getDefaultRequestInput(),
                ['data' => ['operation' => 'get_dw_descripter']]
            ),
            [
                'status_code' => 200,
                'body_validator' => self::getDwDescriptersBodyValidator($this)
            ]
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
        parent::runTokenAuthTest(
            $role,
            $tokenType,
            array_replace(
                self::getDefaultRequestInput(),
                [
                    'data' => [
                        'operation' => 'get_dimension',
                        'dimension_id' => 'username',
                        'realm' => 'Jobs',
                        'public_user' => false,
                        'start' => '',
                        'limit' => '10',
                        'selectedFilterIds' => ''
                    ]
                ]
            ),
            [
                'status_code' => 200,
                'body_validator' => function (
                    $body,
                    $assertMessage
                ) use (
                    $tokenType,
                    $expectedCount
                ) {
                    $this->assertGreaterThanOrEqual(
                        0,
                        $body['totalCount'],
                        $assertMessage
                    );
                    $this->assertGreaterThanOrEqual(
                        0,
                        $body['data'],
                        $assertMessage
                    );
                    foreach ($body['data'] as $item) {
                        foreach (['id', 'name', 'short_name'] as $property) {
                            $this->assertIsString(
                                $item[$property],
                                $assertMessage
                            );
                        }
                        $this->assertIsBool(
                            $item['checked'],
                            $assertMessage
                        );
                    }
                    if (
                        'valid_token' === $tokenType
                        && !is_null($expectedCount)
                    ) {
                        $this->assertSame(
                            $expectedCount,
                            $body['totalCount'],
                            $assertMessage
                        );
                    }
                }
            ]
        );
    }

    /**
     * dataProvider for testGetDimensionFilters
     */
    public function getDimensionFiltersProvider()
    {
        $tests = [];
        foreach (parent::provideTokenAuthTestData() as $testData) {
            list($role, $tokenType) = $testData;
            if ('valid_token' !== $tokenType) {
                $tests[] = [$role, $tokenType, null];
            }
        }
        $expectedCounts = [
            'cd' => 66,
            'cs' => 66,
            'usr' => 1,
            'pi' => 6,
            'mgr' => 0
        ];
        foreach ($expectedCounts as $role => $count) {
            $tests[] = [$role, 'valid_token', $count];
        }
        return $tests;
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

        $params['start'] = 40;
        $params['limit'] = 40;

        $invalid_filter =  array(
            'data' => array(
                array(
                    'checked' => true,
                    'value_id' => 'Timmy O\'Tool',
                    'dimension_id' => 'person'
                ),
            ),
        );
        $params['global_filters'] = urlencode(json_encode($invalid_filter));
        $tests[] = array($params, 0, false);

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

        $response = $this->helper->post('controllers/metric_explorer.php', null, $params);

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
        $response = $this->helper->post('metrics/explorer/queries', null, array('data' => json_encode($settings)));

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);

        $querydata = $response[0];
        $this->assertArrayHasKey('data', $querydata);
        $this->assertArrayHasKey('recordid', $querydata['data']);
        $this->assertArrayHasKey('name', $querydata['data']);
        $this->assertArrayHasKey('ts', $querydata['data']);
        $this->assertArrayHasKey('config', $querydata['data']);

        $recordid = $querydata['data']['recordid'];

        $allcharts = $this->helper->get('metrics/explorer/queries');
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

        $justthischart = $this->helper->get('metrics/explorer/queries/' . $recordid);
        $this->assertTrue($justthischart[0]['success']);
        $this->assertEquals("Test &lt; &lt;img src=&quot;test.gif&quot; onerror=&quot;alert()&quot; /&gt;", $justthischart[0]['data']['name']);

        $cleanup = $this->helper->delete('metrics/explorer/queries/' . $recordid);
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

    /**
     * @dataProvider provideCreateQueryParamValidation
     */
    public function testCreateQueryParamValidation(
        $id,
        $role,
        $input,
        $output
    ) {
        parent::authenticateRequestAndValidateJson(
            $this->helper,
            $role,
            $input,
            $output
        );
    }

    public function provideCreateQueryParamValidation()
    {
        $validInput = [
            'path' => 'metrics/explorer/queries',
            'method' => 'post',
            'params' => null,
            'data' => ['data' => 'foo']
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'string_params' => ['data'],
                'error_body_validator' => $this->validateQueryErrorBody(
                    'creatQuery'
                )
            ]
        );
    }

    private function validateQueryErrorBody($action)
    {
        // This function is passed to parent::provideRestEndpointTests().
        return function ($message) use ($action) {
            // This function is passed to parent::validateErrorResponseBody().
            return function ($body, $assertMessage) use ($message, $action) {
                parent::assertEquals(
                    [
                        'success' => false,
                        'message' => $message,
                        'action' => $action
                    ],
                    $body,
                    $assertMessage
                );
            };
        };
    }

    /**
     * @dataProvider provideUpdateQueryByIdParamValidation
     */
    public function testUpdateQueryByIdParamValidation(
        $id,
        $role,
        $input,
        $output
    ) {
        // Get a query ID.
        $chartSettings = $this->chartDataProvider()[0][0];
        $settings = [
            'name' => 'test',
            'ts' => microtime(true),
            'config' => $chartSettings
        ];
        $this->helper->authenticate('usr');
        $response = $this->helper->post(
            'metrics/explorer/queries',
            null,
            ['data' => json_encode($settings)]
        );
        $this->helper->logout();
        $id = $response[0]['data']['recordid'];
        $path = "metrics/explorer/queries/$id";
        $input['path'] .= $path;
        // Run the test.
        parent::authenticateRequestAndValidateJson(
            $this->helper,
            $role,
            $input,
            $output
        );
        // Delete the query ID.
        $this->helper->authenticate('usr');
        $this->helper->delete($path);
        $this->helper->logout();
    }

    public function provideUpdateQueryByIdParamValidation()
    {
        $validInput = [
            'path' => null, // set in provideUpdateQueryByIdParamValidation().
            'method' => 'post',
            'params' => null,
            'data' => []
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'string_params' => ['data', 'name', 'config'],
                'unix_ts_params' => ['ts'],
                'error_body_validator' => $this->validateQueryErrorBody(
                    'updateQuery'
                )
            ]
        );
    }
}
