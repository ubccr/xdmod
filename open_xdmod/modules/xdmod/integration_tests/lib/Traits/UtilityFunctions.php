<?php
namespace Traits;

trait UtilityFunctions
{
    public function log($msg)
    {
        if ($this->verbose) {
            echo "$msg\n";
        }
    }


    /**
     * Create the chart specified by the provided $data.
     *
     * @param array $data
     * @param array $expected
     *
     * @return mixed
     */
    public function createChart(array $data, $expected = array())
    {
        $operation = 'add_to_queue';

        if ((isset($data['operation']) && $data['operation'] !== $operation) ||
            !isset($data['operation'])) {
            $data['operation'] = $operation;
        }

        $action = 'add';
        if (!array_key_exists('action', $expected)) {
            $expected['action'] = $action;
        }

        if (!array_key_exists('response', $expected)) {
            $expected['response'] = array(
                'success' => true,
                'action' => $action,
            );
        }

        return $this->processChartAction($data, $expected);
    }

    /**
     * A generic helper function that does the heavy lifting for both add and
     * remove Chart.
     *
     * @param array $data
     * @param array $expected
     *
     * @return mixed
     */
    public function processChartAction(array $data, array $expected)
    {
        $expectedAction = $expected['action'];
        $expectedContentType = array_key_exists('content_type', $expected) ? $expected['content_type'] : 'application/json';
        $expectedHttpCode = array_key_exists('http_code', $expected) ? $expected['http_code'] : 200;
        $expectedResponse = $expected['response'];

        $this->log("Processing Chart Action: $expectedAction");

        $response = $this->helper->post('/controllers/chart_pool.php', null, $data);

        $this->log('Response Content-Type: ['.$response[1]['content_type'].']');
        $this->log('Response HTTP-Code   : ['.$response[1]['http_code'].']');

        $this->assertEquals($expectedContentType, $response[1]['content_type']);
        $this->assertEquals($expectedHttpCode, $response[1]['http_code']);

        $json = $response[0];

        $this->log("\tResponse: ".json_encode($json));

        $this->assertEquals($expectedResponse, $json);

        return $json['success'];
    }

    /**
     * Creates the report identified by the contents of $data.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function createReport(array $data)
    {
        $this->log('Creating Report');
        $response = $this->helper->post('/controllers/report_builder.php', null, $data);

        $this->log('Response Content-Type: ['.$response[1]['content_type'].']');
        $this->log('Response HTTP-Code   : ['.$response[1]['http_code'].']');

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);

        $json = $response[0];

        $this->assertArrayHasKey('action', $json);
        $this->assertArrayHasKey('phase', $json);
        $this->assertArrayHasKey('status', $json);
        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('report_id', $json);

        $this->assertEquals('save_report', $json['action']);
        $this->assertEquals('create', $json['phase']);
        $this->assertEquals('success', $json['status']);
        $this->assertEquals(true, $json['success']);

        return $json['report_id'];
    }

    /**
     * Attempts to retrieve the next available report name for the currently
     * logged in user.
     *
     * @return mixed
     */
    public function getNewReportName()
    {
        $data = array(
            'operation' => 'get_new_report_name',
        );

        $response = $this->helper->post('/controllers/report_builder.php', null, $data);

        $this->log('Response Content-Type: ['.$response[1]['content_type'].']');
        $this->log('Response HTTP-Code   : ['.$response[1]['http_code'].']');

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);

        $json = $response[0];

        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('report_name', $json);
        $this->assertEquals(true, $json['success']);
        $this->assertTrue(!empty($json['report_name']));

        return $json['report_name'];
    }

    /**
     * Attempts to remove the report identified by the provided $reportId.
     *
     * @param $reportId
     *
     * @return mixed
     */
    public function removeReportById($reportId)
    {
        $operation = 'remove_report_by_id';
        $data = array(
            'operation' => $operation,
            'selected_report' => $reportId,
        );

        $response = $this->helper->post('/controllers/report_builder.php', null, $data);

        $this->log('Response Content-Type: ['.$response[1]['content_type'].']');
        $this->log('Response HTTP-Code   : ['.$response[1]['http_code'].']');

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);

        $json = $response[0];

        $this->log('Response Data: '.json_encode($json));

        $this->assertArrayHasKey('action', $json);
        $this->assertArrayHasKey('success', $json);

        $this->assertEquals($operation, $json['action']);

        return $json['success'];
    }

    /**
     * Remove the chart specified by the provided $data parameter.
     *
     * @param array $data
     * @param array $expected
     *
     * @return mixed
     */
    public function removeChart(array $data, $expected = array())
    {
        $operation = 'remove_from_queue';
        if ((isset($data['operation']) && $data['operation'] !== $operation) ||
            !isset($data['operation'])) {
            $data['operation'] = $operation;
        }

        $action = 'remove';
        if (!array_key_exists('action', $expected)) {
            $expected['action'] = $action;
        }

        if (!array_key_exists('response', $expected)) {
            $expected['response'] = array(
                'success' => true,
                'action' => $action,
            );
        }

        return $this->processChartAction($data, $expected);
    }

    private function enumAvailableCharts()
    {
        $data = array(
            'operation' => 'enum_available_charts'
        );
        $response = $this->helper->post('/controllers/report_builder.php', null, $data);

        $this->log("Response Content-Type: [" . $response[1]['content_type'] . "]");
        $this->log("Response HTTP-Code   : [" . $response[1]['http_code'] . "]");

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);

        $json = $response[0];

        $this->log("Response Data: " . json_encode($json));

        return $json;
    }

    /**
     * Renders the report image ( chart ) identified by the contents of $params.
     *
     * @param array $params
     */
    public function reportImageRenderer(array $params)
    {
        $response = $this->helper->get('/report_image_renderer.php', $params);

        $this->log("Response Content-Type: [" . $response[1]['content_type'] . "]");
        $this->log("Response HTTP-Code   : [" . $response[1]['http_code'] . "]");

        $this->assertEquals('image/png', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);
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
