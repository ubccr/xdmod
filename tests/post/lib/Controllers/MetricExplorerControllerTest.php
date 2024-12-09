<?php

namespace PostTests\Controllers;

use IntegrationTests\TestHarness\XdmodTestHelper;
use PHPUnit\Framework\TestCase;

class MetricExplorerControllerTest extends TestCase
{
    /**
     * Check the filters are encoded properly
     */
    public function testFilterEncoding()
    {
        $helper = new XdmodTestHelper();
        $helper->authenticate('cd');

        $params = array(
            'operation' => 'get_dimension',
            'dimension_id' => 'username',
            'realm' => 'Jobs',
            'public_user' => 'false',
            'start' => '0',
            'limit' => '10',
            'search_text' =>'fa'
        );

        $response = $helper->post('/controllers/metric_explorer.php', null, $params);

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);

        $filterdata = $response[0];

        $this->assertEquals(1, $filterdata['totalCount']);
        $this->assertEquals("façade", $filterdata['data'][0]['name']);
    }

    /**
     * Check the show raw data endpoint
     */
    public function testRawDataEncoding()
    {
        $helper = new XdmodTestHelper();
        $helper->authenticate('cd');

        $config = <<<EOF
{
    "show_title": "y",
    "timeseries": "y",
    "aggregation_unit": "Auto",
    "start_date": "2023-12-06",
    "end_date": "2024-12-06",
    "global_filters": "%7B%22data%22%3A%5B%7B%22id%22%3A%22resource%3D2%22%2C%22value_id%22%3A%222%22%2C%22value_name%22%3A%22mortorq%22%2C%22dimension_id%22%3A%22resource%22%2C%22realms%22%3A%5B%22Cloud%22%2C%22Jobs%22%2C%22ResourceSpecifications%22%2C%22Storage%22%5D%2C%22checked%22%3Atrue%7D%5D%7D",
    "title": "untitled%20query%201",
    "show_filters": "true",
    "show_warnings": "true",
    "show_remainder": "false",
    "start": "0",
    "limit": "20",
    "timeframe_label": "1%20year",
    "operation": "get_rawdata",
    "data_series": "%5B%7B%22group_by%22%3A%22resource%22%2C%22color%22%3A%22auto%22%2C%22log_scale%22%3Afalse%2C%22std_err%22%3Afalse%2C%22value_labels%22%3Afalse%2C%22display_type%22%3A%22line%22%2C%22combine_type%22%3A%22side%22%2C%22sort_type%22%3A%22value_desc%22%2C%22ignore_global%22%3Afalse%2C%22long_legend%22%3Atrue%2C%22x_axis%22%3Afalse%2C%22has_std_err%22%3Afalse%2C%22trend_line%22%3Afalse%2C%22line_type%22%3A%22Solid%22%2C%22line_width%22%3A2%2C%22shadow%22%3Afalse%2C%22filters%22%3A%7B%22data%22%3A%5B%5D%2C%22total%22%3A0%7D%2C%22z_index%22%3A0%2C%22visibility%22%3Anull%2C%22enabled%22%3Atrue%2C%22metric%22%3A%22job_count%22%2C%22realm%22%3A%22Jobs%22%2C%22category%22%3A%22Jobs%22%2C%22id%22%3A7991646453251053%7D%5D",
    "swap_xy": "false",
    "share_y_axis": "false",
    "hide_tooltip": "false",
    "show_guide_lines": "y",
    "showContextMenu": "y",
    "scale": "1",
    "format": "jsonstore",
    "width": "1625",
    "height": "583",
    "legend_type": "bottom_center",
    "font_size": "3",
    "featured": "false",
    "trendLineEnabled": "",
    "x_axis": "%7B%7D",
    "y_axis": "%7B%7D",
    "legend": "%7B%7D",
    "defaultDatasetConfig": "%7B%7D",
    "controller_module": "metric_explorer",
    "inline": "n",
    "datapoint": "1701388800000",
    "datasetId": "7991646453251053"
}
EOF;

        $response = $helper->post('/controllers/metric_explorer.php', null, json_decode($config));

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);

        $rawdata = $response[0];

        $this->assertEquals(1, $rawdata['totalAvailable']);
        $this->assertEquals("Ιωάννης, Γιάννης", $rawdata['data'][0]['name']);
        $this->assertEquals("schón Straße", $rawdata['data'][0]['job_name']);
    }
}
