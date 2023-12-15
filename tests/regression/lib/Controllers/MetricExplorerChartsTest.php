<?php

namespace RegressionTests\Controllers;

use IntegrationTests\TestHarness\Utilities;
use IntegrationTests\TestHarness\XdmodTestHelper;

class MetricExplorerChartsTest extends \PHPUnit\Framework\TestCase
{
    private static $chartFilterTestData = array();

    public static function tearDownAfterClass(): void
    {
        // This is used to write expected results file for the
        // testChartFilters test. The output file is just written to
        // the current directory for review.
        if (!empty(self::$chartFilterTestData)) {
            file_put_contents(
                'chartFilterTests_generated.json',
                json_encode(self::$chartFilterTestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
            );
        }
    }

    /* If the 'expected' value is set to null then the test harness will
     * use this function to print out the results from the api call. This
     * can be used to generate new expected test results.
     */
    private function output($chartData)
    {
        $result = array(
            'total' => $chartData['totalCount'],
            'series_data' => array()
        );
        $chartData = $chartData->data[0]->data;
        foreach ($chartData as $series) {
            $result['series_data'][] = array(
                'name' => $series->name,
                'y' => $series->y[0],
            );
        }
        var_export($result);
    }

    /**
     * See the filterTestsProvider for instructions on how to generate
     * new tests for this function or update the expected values.
     *
     * @dataProvider filterTestsProvider
     */
    public function testChartFilters($helper, $settings, $expected)
    {
        $global_settings = array(
            'filters' => $this->getFiltersByValue($helper, $settings['realm'], $settings['filter_dimension'], $settings['filter_values']),
            'date' => $settings['date']
        );

        $requestData = $this->getChartRequest(array(array('realm' => $settings['realm'], 'group_by' => 'none', 'metric' => $settings['metric'])), $global_settings);

        $response = $helper->post('controllers/metric_explorer.php', null, $requestData);

        if ($response[1]['http_code'] != 200) {
            var_export($response);
        }

        $this->assertEquals(200, $response[1]['http_code']);

        $chartStore = json_decode($response[0]);
        $this->assertNotNull($chartStore);

        $chartData = $chartStore->data[0];

        $this->assertEquals($expected['subtitle'], $chartData->layout->annotations[1]->text);
        if (isset($expected['yvalue'])) {
            $this->assertEquals($expected['yvalue'], $chartData->data[0]->y[0]);
        } else {
            self::$chartFilterTestData[] = array(
                'settings' => $settings,
                'expected' => array(
                    'subtitle' => $expected['subtitle'],
                    'yvalue' => $chartData->data[0]->y[0]
                )
            );
        }
    }

    private function getFiltersByValue($helper, $realm, $dimension, $values)
    {
        $dimensionValues = $this->getDimensionValues($helper, $realm, $dimension);

        $filters = array();

        foreach ($values as $value) {
            foreach ($dimensionValues as $dimVal) {
                if ($value === $dimVal['name']) {
                    $filters[] = array(
                        'value_id' => $dimVal['id'],
                        'dimension_id' => $dimension,
                        'checked' => true
                    );
                    break;
                }
            }
        }

        $this->assertEquals(count($filters), count($values));

        return $filters;
    }

    private function getDimensionValues($helper, $realm, $dimension)
    {
        $params = array(
            'operation' => 'get_dimension',
            'dimension_id' => $dimension,
            'realm' => $realm,
            'public_user' => false,
            'start' => '0',
            'limit' => '10000',
            'selectedFilterIds' => ''
        );

        $response = $helper->post('/controllers/metric_explorer.php', null, $params);

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);

        $this->assertEquals($response[0]['totalCount'], count($response[0]['data']));

        return $response[0]['data'];
    }

    /**
     * Tests the scenario where multiple datasets are plotted but they have different
     * number of dataseries and the paging has paged past the max of one of the series.
     */
    public function testChartPaging()
    {
        $requestData =  $this->getChartRequest(
            array(
                array('realm' => 'Jobs', 'group_by' => 'username', 'metric' => 'total_cpu_hours'),
                array('realm' => 'Jobs', 'group_by' => 'none', 'metric' => 'total_cpu_hours'),
            ),
            array(
                'start' => '4',
                'showRemainder' => 'false'
            )
        );

        $expected = array (
            'total' => '55',
            'series_data' => array(
                array('name' => 'aytinis', 'y' => 10091.8844),
                array('name' => 'sarwa', 'y' => 6955.7733),
                array('name' => 'crane', 'y' => 6839.52),
                array('name' => 'duswa', 'y' => 5701.5467)
            )
        );
        $this->testChartData($requestData, $expected);
    }

    /**
     * @dataProvider remainderChartProvider
     */
    public function testChartData($requestData, $expected)
    {
        $helper = new XdmodTestHelper();
        $helper->authenticate('cd');

        $response = $helper->post('controllers/metric_explorer.php', null, $requestData);

        $this->assertEquals(200, $response[1]['http_code']);

        $chartData = json_decode($response[0]);
        $this->assertNotNull($chartData);

        if ($expected === null) {
            $this->output($chartData);
            $this->markTestSkipped();
            return;
        }

        $this->assertEquals($expected['total'], $chartData->totalCount);
        $chartData = $chartData->data[0];
        $series = $chartData->data;
        $this->assertCount(count($expected['series_data']), $series);

        $sdata = reset($expected['series_data']);

        foreach ($series as $s) {
            $this->assertEquals($sdata['name'], $s->name);
            $this->assertEquals($sdata['y'], $s->y[0], '', 1.0E-6);
            $sdata = next($expected['series_data']);
        }

        $helper->logout();
    }

    /**
     * Tests the scenario where multiple datasets are plotted
     * and shareY axis is true so they get the units shown in the
     * data labels.
     */
    public function testChartMultiAxis()
    {
        $requestData =  $this->getChartRequest(
            array(
                array('realm' => 'Jobs', 'group_by' => 'username', 'metric' => 'total_cpu_hours'),
                array('realm' => 'Jobs', 'group_by' => 'none', 'metric' => 'total_cpu_hours'),
            ),
            array(
                'start' => '4',
                'showRemainder' => 'false',
                'share_y_axis' => 'true'
            )
        );

        $expected = array (
            'total' => '55',
            'series_data' => array(
                array('name' => 'aytinis [<span style="color:#1199ff">CPU Hours: Total</span>]', 'y' => 10091.8844),
                array('name' => 'sarwa [<span style="color:#1199ff">CPU Hours: Total</span>]', 'y' => 6955.7733),
                array('name' => 'crane [<span style="color:#1199ff">CPU Hours: Total</span>]', 'y' => 6839.52),
                array('name' => 'duswa [<span style="color:#1199ff">CPU Hours: Total</span>]', 'y' => 5701.5467)
            )
        );

        $this->testChartData($requestData, $expected);
    }


    public function getChartRequest($data_settings, $global_settings = null)
    {
        $dataseries = array();
        foreach ($data_settings as $setting) {
            $dataseries[] = $this->generateDataSeries($setting);
        }

        $filterlist = isset($global_settings['filters']) ? $global_settings['filters'] : array();

        $global_filters = array(
            'data' => $filterlist,
            'total' => count($filterlist)
        );

        $testdate = isset($global_settings['date']) ? $global_settings['date'] : '2016-12-31';
        $startOffset = isset($global_settings['start']) ? $global_settings['start'] : '0';
        $showRemainder = isset($global_settings['showRemainder']) ? $global_settings['showRemainder'] : 'true';
        $shareYAxis = isset($global_settings['share_y_axis']) ? $global_settings['share_y_axis'] : 'false';

        $chartSettings = array(
            'show_title' => 'y',
            'timeseries' => 'y',
            'aggregation_unit' => 'Auto',
            'start_date' => $testdate,
            'end_date' => $testdate,
            'global_filters' => urlencode(json_encode($global_filters)),
            'title' => 'Metric Explorer Test Chart',
            'show_filters' => 'true',
            'show_warnings' => 'true',
            'show_remainder' => $showRemainder,
            'start' => $startOffset,
            'limit' => '4',
            'timeframe_label' => 'User Defined',
            'operation' => 'get_data',
            'data_series' => urlencode(json_encode($dataseries)),
            'swap_xy' => 'false',
            'share_y_axis' => $shareYAxis,
            'hide_tooltip' => 'false',
            'show_guide_lines' => 'y',
            'showContextMenu' => 'y',
            'scale' => '1',
            'format' => 'hc_jsonstore',
            'width' => '1388',
            'height' => '494',
            'legend_type' => 'bottom_center',
            'font_size' => '3',
            'featured' => 'false',
            'trendLineEnabled' => '',
            'x_axis' => '%7B%7D',
            'y_axis' => '%7B%7D',
            'legend' => '%7B%7D',
            'defaultDatasetConfig' => '%7B%7D',
            'controller_module' => 'metric_explorer'
        );
        return $chartSettings;
    }

    private function generateDataSeries($settings)
    {
        static $dataseries_id = 0.1356380402;
        $dataseries_id += 0.0000000001;

        return array (
            'id' => $dataseries_id,
            'metric' => $settings['metric'],
            'category' => '',
            'realm' => $settings['realm'],
            'group_by' => $settings['group_by'],
            'x_axis' => false,
            'log_scale' => false,
            'has_std_err' => false,
            'std_err' => false,
            'std_err_labels' => false,
            'value_labels' => false,
            'display_type' => 'line',
            'line_type' => '',
            'line_width' => '',
            'combine_type' => 'side',
            'sort_type' => 'value_desc',
            'filters' => array (
                'data' => array (),
                'total' => 0,
            ),
            'ignore_global' => false,
            'long_legend' => true,
            'trend_line' => false,
            'color' => '',
            'shadow' => '',
            'visibility' => '',
            'z_index' => 0,
            'enabled' => true,
        );
    }

    /**
     * These test cases cover the four different 'show remainder' cases: min, max, avg and sum.
     */
    public function remainderChartProvider()
    {
        if (!in_array('jobs', Utilities::getRealmsToTest())) {
            return array();
        }

        $tests = array();

        $tests[] = array(
            $this->getChartRequest(array(array('realm' => 'Jobs', 'group_by' => 'username', 'metric' => 'total_cpu_hours'))),
            array(
                'total' => 55,
                'series_data' => array(
                    array( 'name' => 'honbu', 'y' => 86581.6175),
                    array( 'name' => 'meapi', 'y' => 22004.0533),
                    array( 'name' => 'moorh', 'y' => 20518.0064),
                    array( 'name' => 'garwa', 'y' => 11780.8131),
                    array( 'name' => 'All 51 Others', 'y' => 81345.1393)
                )
            )
        );

        $tests[] = array(
            $this->getChartRequest(array(array('realm' => 'Jobs', 'group_by' => 'pi', 'metric' => 'max_processors'))),
            array (
                'total' => '34',
                'series_data' => array (
                    array('name' => 'Thrush, Hermit', 'y' => 336),
                    array('name' => 'Dunlin', 'y' => 192),
                    array('name' => 'Scaup, Lesser', 'y' => 192),
                    array('name' => 'Nuthatch', 'y' => 112),
                    array('name' => 'Maximum over all 30 others', 'y' => 96),
                )
            )
        );

        $tests[] = array(
            $this->getChartRequest(array(array('realm' => 'Jobs', 'group_by' => 'person', 'metric' => 'total_wallduration_hours'))),
            array (
                'total' => '55',
                'series_data' => array (
                    array('name' => 'Moorhen', 'y' => 20518.0064),
                    array('name' => 'Honey-buzzard', 'y' => 8276.1947),
                    array('name' => 'Grey, Lesser', 'y' => 5534.6542),
                    array('name' => 'Lapwing', 'y' => 2761.8142),
                    array('name' => 'All 51 Others', 'y' => 5231.8753),
                )
            )
        );

        $tests[] = array(
            $this->getChartRequest(array(array('realm' => 'Jobs', 'group_by' => 'queue', 'metric' => 'min_processors'))),
            array (
                'total' => '17',
                'series_data' => array (
                    array('name' => 'roti', 'y' => 32),
                    array('name' => 'chapti', 'y' => 12),
                    array('name' => 'focaccia', 'y' => 12),
                    array('name' => 'nann', 'y' => 12),
                    array('name' => 'Minimum over all 13 others', 'y' => 1),
                )
            )
        );

        $tests[] = array(
            $this->getChartRequest(array(
                array('realm' => 'Jobs', 'group_by' => 'person', 'metric' => 'total_wallduration_hours'),
                array('realm' => 'Jobs', 'group_by' => 'queue', 'metric' => 'total_wallduration_hours')
            )),
            array (
                'total' => '55',
                'series_data' => array (
                    array('name' => 'Moorhen', 'y' => 20518.0064),
                    array('name' => 'Honey-buzzard', 'y' => 8276.1947),
                    array('name' => 'Grey, Lesser', 'y' => 5534.6542),
                    array('name' => 'Lapwing', 'y' => 2761.8142),
                    array('name' => 'All 51 Others', 'y' => 5231.8753),
                    array('name' => 'white', 'y' => 32448.9128),
                    array('name' => 'black', 'y' => 7527.9847),
                    array('name' => 'pikelet', 'y' => 1112.6033),
                    array('name' => 'croutons', 'y' => 481.5464),
                    array('name' => 'All 13 Others', 'y' => 751.4976)
                ),
            )
        );
        return $tests;
    }

    /**
     * Generate test scenarios for all of the possible filters for each realm. This
     * function is called by the dataProvider when the expected tests file is absent.
     * This queries XDMoD to get a list of dimensions and dimension values for each realm
     * and is intended to be run against a known working XDMoD to generate a baseline
     * set of values for regression testing.
     */
    private function generateFilterTests()
    {
        // Generate test scenario for filter tests.
        $baseConfig = array(
            array('realm' => 'Jobs', 'metric' => 'total_cpu_hours', 'date' => '2016-12-31'),
            array('realm' => 'Storage', 'metric' => 'avg_logical_usage', 'date' => '2018-12-28'),
            array('realm' => 'Cloud', 'metric' => 'cloud_core_time', 'date' => '2019-06-26')
        );

        $output = array();

        $helper = new XdmodTestHelper();
        $helper->authenticate('cd');

        foreach ($baseConfig as $config)
        {
            $response = $helper->get('rest/v1/warehouse/dimensions', array('realm' => $config['realm']));
            foreach ($response[0]['results'] as $dimConfig)
            {
                $dimension = $dimConfig['id'];
                $dimensionValues = $this->getDimensionValues($helper, $config['realm'], $dimension);

                $testConfig = array(
                    'settings' => array(
                        'realm' => $config['realm'],
                        'metric' => $config['metric'],
                        'date' => $config['date'],
                        'filter_dimension' => $dimension,
                        'filter_values' => array()
                    ),
                    'expected' => array()
                );

                foreach ($dimensionValues as $dimval) {
                    $testConfig['settings']['filter_values'][] = $dimval['name'];
                    $testConfig['expected']['subtitle'] .= ' ' . $dimval['name'] . ', ';

                    if (count($testConfig['settings']['filter_values']) > 1) {
                        break;
                    }
                }
                if (count($testConfig['settings']['filter_values']) === 1) {
                    $testConfig['expected']['subtitle'] = $dimConfig['name'] . ' =  ' . $testConfig['settings']['filter_values'][0] ;
                } else {
                    $testConfig['expected']['subtitle'] = $dimConfig['name'] . ' = ( ' . implode($testConfig['settings']['filter_values'], ',  ') . ' )';
                }

                $output[] = $testConfig;
            }
        }

        return $output;
    }

    public function filterTestsProvider()
    {
        $data_file = realpath(__DIR__ . '/../../../artifacts/xdmod/regression/chartFilterTests.json');
        if (file_exists($data_file)) {
            $inputs = json_decode(file_get_contents($data_file), true);
        } else {
            // Generate test permutations. The expected values for the data points are not set.
            // this causes the test function to record the values and they are then written
            // to a file in the tearDownAfterClass function.
            $inputs = $this->generateFilterTests();
        }

        $helper = new XdmodTestHelper();
        $helper->authenticate('cd');

        $enabledRealms = Utilities::getRealmsToTest();

        $output = array();
        foreach ($inputs as $test)
        {
            if (in_array(strtolower($test['settings']['realm']), $enabledRealms)) {
                $output[] = array($helper, $test['settings'], $test['expected']);
            }
        }

        return $output;
    }
}
