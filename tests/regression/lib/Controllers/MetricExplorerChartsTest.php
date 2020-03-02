<?php

namespace RegressionTests\Controllers;

class MetricExplorerChartsTest extends \PHPUnit_Framework_TestCase
{
    /* If the 'expected' value is set to null then the test harness will
     * use this function to print out the results from the api call. This
     * can be used to generate new expected test results.
     */
    private function output($chartData)
    {
        $result = array(
            'total' => $chartData->totalCount,
            'series_data' => array()
        );
        foreach ($chartData->data[0]->series as $series) {
            $result['series_data'][] = array(
                'name' => $series->name,
                'y' => $series->data[0]->y,
                'percentage' => $series->data[0]->percentage
            );
        }
        var_export($result);
    }

    /**
     * @dataProvider remainderChartProvider
     */
    public function testChartData($requestData, $expected)
    {
        $helper = new \TestHarness\XdmodTestHelper();
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

        $series = $chartData->data[0]->series;
        $this->assertCount(count($expected['series_data']), $series);

        $sdata = reset($expected['series_data']);

        foreach ($series as $s) {
            $this->assertEquals($sdata['name'], $s->name);
            $this->assertEquals($sdata['y'], $s->data[0]->y, '', 1.0E-6);
            $this->assertEquals($sdata['percentage'], $s->data[0]->percentage);
            $sdata = next($expected['series_data']);
        }

        $helper->logout();
    }

    public function getChartRequest($settings)
    {
        $dataseries = array();
        foreach ($settings as $setting) {
            $dataseries[] = $this->generateDataSeries($setting);
        }

        $chartSettings = array(
            'show_title' => 'y',
            'timeseries' => 'y',
            'aggregation_unit' => 'Auto',
            'start_date' => '2016-12-31',
            'end_date' => '2016-12-31',
            'global_filters' => '%7B%22data%22%3A%5B%5D%2C%22total%22%3A0%7D',
            'title' => 'Metric Explorer Test Chart',
            'show_filters' => 'true',
            'show_warnings' => 'true',
            'show_remainder' => 'true',
            'start' => '0',
            'limit' => '4',
            'timeframe_label' => 'User Defined',
            'operation' => 'get_data',
            'data_series' => urlencode(json_encode($dataseries)),
            'swap_xy' => 'false',
            'share_y_axis' => 'false',
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
        $tests = array();

        $tests[] = array(
            $this->getChartRequest(array(array('realm' => 'Jobs', 'group_by' => 'username', 'metric' => 'total_cpu_hours'))),
            array(
                'total' => 55,
                'series_data' => array(
                    array( 'name' => 'honbu', 'y' => 86581.6175, 'percentage' => 0),
                    array( 'name' => 'meapi', 'y' => 22004.0533, 'percentage' => 0),
                    array( 'name' => 'moorh', 'y' => 20518.0064, 'percentage' => 0),
                    array( 'name' => 'garwa', 'y' => 11780.8131, 'percentage' => 0),
                    array( 'name' => 'All 51 Others', 'y' => 81345.1393, 'percentage' => null)
                )
            )
        );

        $tests[] = array(
            $this->getChartRequest(array(array('realm' => 'Jobs', 'group_by' => 'pi', 'metric' => 'max_processors'))),
            array (
                'total' => '34',
                'series_data' => array (
                    array('name' => 'Thrush, Hermit', 'y' => 336, 'percentage' => 0),
                    array('name' => 'Dunlin', 'y' => 192, 'percentage' => 0),
                    array('name' => 'Scaup, Lesser', 'y' => 192, 'percentage' => 0),
                    array('name' => 'Nuthatch', 'y' => 112, 'percentage' => 0),
                    array('name' => 'Maximum over all 30 others', 'y' => 96, 'percentage' => null),
                )
            )
        );

        $tests[] = array(
            $this->getChartRequest(array(array('realm' => 'Jobs', 'group_by' => 'person', 'metric' => 'total_wallduration_hours'))),
            array (
                'total' => '55',
                'series_data' => array (
                    array('name' => 'Moorhen', 'y' => 20518.0064, 'percentage' => 0),
                    array('name' => 'Honey-buzzard', 'y' => 8276.1947, 'percentage' => 0),
                    array('name' => 'Grey, Lesser', 'y' => 5534.6542, 'percentage' => 0),
                    array('name' => 'Lapwing', 'y' => 2761.8142, 'percentage' => 0),
                    array('name' => 'All 51 Others', 'y' => 5231.8753, 'percentage' => null),
                )
            )
        );

        $tests[] = array(
            $this->getChartRequest(array(array('realm' => 'Jobs', 'group_by' => 'queue', 'metric' => 'min_processors'))),
            array (
                'total' => '17',
                'series_data' => array (
                    array('name' => 'roti', 'y' => 32, 'percentage' => 0),
                    array('name' => 'chapti', 'y' => 12, 'percentage' => 0),
                    array('name' => 'focaccia', 'y' => 12, 'percentage' => 0),
                    array('name' => 'nann', 'y' => 12, 'percentage' => 0),
                    array('name' => 'Minimum over all 13 others', 'y' => 1, 'percentage' => null),
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
                    array('name' => 'Moorhen [<span style="color:#1199ff">Wall Hours: Total</span>]', 'y' => 20518.0064, 'percentage' => null),
                    array('name' => 'Honey-buzzard [<span style="color:#1199ff">Wall Hours: Total</span>]', 'y' => 8276.1947, 'percentage' => null),
                    array('name' => 'Grey, Lesser [<span style="color:#1199ff">Wall Hours: Total</span>]', 'y' => 5534.6542, 'percentage' => null),
                    array('name' => 'Lapwing [<span style="color:#1199ff">Wall Hours: Total</span>]', 'y' => 2761.8142, 'percentage' => null),
                    array('name' => 'All 51 Others [<span style="color:#1199ff">Wall Hours: Total</span>]', 'y' => 5231.8753, 'percentage' => null),
                    array('name' => 'white [<span style="color:#1199ff">Wall Hours: Total</span>]', 'y' => 32448.9128, 'percentage' => null),
                    array('name' => 'black [<span style="color:#1199ff">Wall Hours: Total</span>]', 'y' => 7527.9847, 'percentage' => null),
                    array('name' => 'pikelet [<span style="color:#1199ff">Wall Hours: Total</span>]', 'y' => 1112.6033, 'percentage' => null),
                    array('name' => 'croutons [<span style="color:#1199ff">Wall Hours: Total</span>]', 'y' => 481.5464, 'percentage' => null),
                    array('name' => 'All 13 Others [<span style="color:#1199ff">Wall Hours: Total</span>]', 'y' => 751.4976, 'percentage' => null)
                ),
            )
        );
        return $tests;
    }
}
