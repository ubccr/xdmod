<?php

namespace IntegrationTests\Controllers;

use CCR\Json;
use IntegrationTests\TestHarness\XdmodTestHelper;
use IntegrationTests\BaseTest;

/**
 * Class ReportBuilderTest
 *
 * Currently tested controllers:
 *   - report_builder
 *     - enum_available_charts.php
 *     - enum_reports.php
 *
 *   Happy Path Testing Only:
 *   - report_builder
 *     - get_new_report_name.php
 *     - remove_chart_from_pool.php
 *     - remove_report_by_id.php
 *     - save_report.php
 *   - chart_pool
 *     - add_to_queue
 *     - remove_from_queue
 *   - report_image_renderer.php
 *
 *   Error path testing Only:
 *     - download_report.php
 *
 * Remaining controllers:
 *   - report_builder
 *     - build_from_template.php
 *     - fetch_report_data.php
 *     - get_preview_data.php
 *     - send_report.php
 */
class ReportBuilderTest extends BaseTest
{
    /**
     * @var XdmodTestHelper
     */
    protected $helper;

    /**
     * @var bool
     */
    protected $verbose;

    private static $DEFAULT_EXPECTED = array(
        'content_type' => 'application/json',
        'http_code' => 200
    );

    protected function setup(): void
    {
        $this->verbose = getenv('TEST_VERBOSE');
        if (!isset($this->verbose)) {
            $this->verbose = false;
        }
        $this->helper = new XdmodTestHelper();
    }

    public function provideDlReportInputValidation()
    {
        $tests = array();

        $params = array(
            'operation' => 'download_report',
            'report_loc' => '/etc/shadow',
            'format' => 'pdf'
        );
        $response =  array(
            'success' => false,
            'message' => 'Invalid report_loc'
        );

        $tests[] = array($params, $response);

        $params = array(
            'operation' => 'download_report',
            'report_loc' => '3-1614908275-PVe1U',
            'format' => 'rar'
        );
        $response = [
            'success' => false,
            'message' =>  'Invalid format'
        ];

        $tests[] = array($params, $response);

        $params = array(
            'operation' => 'download_report'
        );
        $response = array(
            'success' => false,
            'message' => 'report_loc is a required parameter.'
        );

        $tests[] = array($params, $response);

        $params = array(
            'operation' => 'download_report',
            'report_loc' => '322323323232'
        );
        $response = array(
            'success' => false,
            'message' => 'format is a required parameter.'
        );

        $tests[] = array($params, $response);

        return $tests;
    }

    /**
     * @dataProvider provideDlReportInputValidation
     * Checks that input validation is performed on the dowload_report
     * endpoint
     */
    public function testDownloadReportInputValidation($params, $expected)
    {
        $this->helper->authenticate('usr');
        $data = $this->helper->get('controllers/report_builder.php', $params);

        $response = $this->helper->get('controllers/report_builder.php', $params);
        $data = $response[0];
        $curlinfo = $response[1];

        if (is_array($expected)) {
            // expect json data back
            $this->assertEquals('application/json', $curlinfo['content_type']);
            foreach ($expected as $key => $value) {
                $this->assertEquals($value, $data[$key]);
            }
        } else {
            // expect text data back
            $this->assertEquals('text/html; charset=UTF-8', $curlinfo['content_type']);
            $this->assertEquals($expected, $response[0]);
        }
    }

    /**
     * @dataProvider provideEnumAvailableCharts
     * @param array $options
     * @throws \Exception
     */
    public function testEnumAvailableCharts(array $options)
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $operation = 'enum_available_charts';

        $user = $options['user'];
        $expected = isset($options['expected']) ? $options['expected'] : self::$DEFAULT_EXPECTED;
        $output = isset($options['output']) ? $options['output'] : array(
            'name' => $user . "_$operation",
            'extension' => '.json'
        );

        if ($user !== 'pub') {
            $this->helper->authenticate($user);
        }

        $params = array(
            'operation' => $operation
        );

        $response = $this->helper->post("controllers/report_builder.php", null, $params);

        $this->assertEquals($expected['content_type'], $response[1]['content_type']);
        $this->assertEquals($expected['http_code'], $response[1]['http_code']);

        $expectedFileName = parent::getTestFiles()->getFile('report_builder', $output['name'], 'output', $output['extension']);
        if ($output['extension'] === '.json') {
            $expected = JSON::loadFile($expectedFileName);
        } else {
            $expected = @file_get_contents($expectedFileName);
        }

        $actual = $response[0];

        $this->assertEquals($expected, $actual);

        if ($user !== 'pub') {
            $this->helper->logout();
        }
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideEnumAvailableCharts()
    {
        return JSON::loadFile(
            parent::getTestFiles()->getFile('report_builder', 'enum_available_charts', 'input')
        );
    }

    /**
     * @dataProvider provideEnumReports
     * @param array $options
     * @throws \Exception
     */
    public function testEnumReports(array $options)
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $operation = 'enum_reports';

        $user = $options['user'];
        $expected = isset($options['expected']) ? $options['expected'] : self::$DEFAULT_EXPECTED;
        $output = isset($options['output']) ? $options['output'] : array(
            'name' => $user . "_$operation",
            'extension' => '.json'
        );

        if ($user !== 'pub') {
            $this->helper->authenticate($user);
        }

        $params = array(
            'operation' => $operation
        );

        $response = $this->helper->post("controllers/report_builder.php", null, $params);

        $this->assertEquals($expected['content_type'], $response[1]['content_type']);
        $this->assertEquals($expected['http_code'], $response[1]['http_code']);

        $expectedFileName = parent::getTestFiles()->getFile('report_builder', $output['name'], 'output', $output['extension']);
        if ($output['extension'] === '.json') {
            $expected = JSON::loadFile($expectedFileName);
        } else {
            $expected = @file_get_contents($expectedFileName);
        }

        $actual = $response[0];

        $this->assertEquals($actual, $expected);

        if ($user !== 'pub') {
            $this->helper->logout();
        }

    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideEnumReports()
    {
        return JSON::loadFile(
            parent::getTestFiles()->getFile('report_builder', 'enum_reports', 'input')
        );
    }

    /**
     * @dataProvider provideCreateReport
     * @param array $options
     * @throws \Exception
     */
    public function testCreateReport(array $options)
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $data = $options['data'];
        $user = $options['user'];
        $charts = $options['charts'];

        if ($user !== 'pub') {
            $this->helper->authenticate($user);
        }

        $this->log("Logged in as $user");

        $chartParams = array();
        $i = 0;
        foreach ($charts as $chart) {
            $chartParams = array();
            $this->log("Creating Chart $i...");

            // create the chart...
            $success = $this->createChart($chart);
            $this->assertTrue($success, "Unable to create chart: " . json_encode($chart));

            // list available charts to retrieve information about chart previously created
            $availableCharts = $this->enumAvailableCharts();
            $this->assertArrayHasKey('queue', $availableCharts);

            $queue = $availableCharts['queue'];

            // Loop through each of the charts that were returned and...
            foreach ($queue as $queuedChart) {
                // ensure that we have the properties that we require to
                // continue the process.
                $this->assertArrayHasKey('thumbnail_link', $queuedChart);
                $this->assertArrayHasKey('chart_date_description', $queuedChart);
                $this->assertArrayHasKey('chart_id', $queuedChart);

                // Retrieve the start and end date values
                list($startDate, $endDate) = explode(' to ', $queuedChart['chart_date_description']);

                // This property contains the: type, ref#, insertion rank and
                // token ( if present ).
                $thumbnailLink = $queuedChart['thumbnail_link'];
                $paramString = substr($thumbnailLink, strpos($thumbnailLink, '?') + 1, strlen($thumbnailLink) - strpos($thumbnailLink, '?'));

                $params = explode('&', $paramString);
                $this->log(sprintf("Params:\n %s", var_export($params, true)));
                $results = array();
                foreach ($params as $param) {
                    list($key, $value) = explode('=', $param);

                    // Don't include empty tokens.
                    if ($key === 'token' && empty($value)) {
                        continue;
                    }
                    $results[$key] = $value;
                }

                // save the chartParams off so we can use 'um later
                $chartParams[] = array(
                    'params' => $results,
                    'chart_id' => $queuedChart['chart_id'],
                    'start_date' => $startDate,
                    'end_date' => $endDate
                );
                $this->log('Rendering Report Image');
                $this->log(sprintf("New Params:\n %s", var_export($results, true)));
                // render the chart image so that a temp file is created on the backend.
                $this->reportImageRenderer($results);
            }
            $i += 1;
        }

        $this->log('Rendering Chart Params...');
        // render the charts as volatile
        foreach ($chartParams as $chartData) {

            $params = $chartData['params'];

            $params['type'] = 'volatile';
            $this->log(var_export($params, true));
            $this->reportImageRenderer($params);
        }
        $this->log('Done Rendering Chart Params!');

        $this->log('Get new report Name');
        // Retrieve the next available report name for this user.
        $reportName = $this->getNewReportName();

        $data['report_name'] = $reportName;
        $this->log('Creating Report...');
        $this->log(var_export($data, true));

        // Attempt to create the report.
        $reportId = $this->createReport($data);

        // Ensure we were successful
        $this->assertTrue(isset($reportId), "Did not receive a report_id back from create_report");

        $this->log("Removing Report...");
        // Attempt to remove the report
        $removedReport = $this->removeReportById($reportId);
        $this->log("Report Removed!");

        // Ensure that we were successful
        $this->assertTrue($removedReport, "Did not remove the report identified by: $reportId");

        // Now, go through each of the charts and remove them as well.
        foreach ($charts as $chart) {
            $success = $this->removeChart($chart);
            $this->assertTrue($success, "Unable to remove chart: " . json_encode($chart));
        }

        $this->log("Logging out of: $user");
        if ($user !== 'pub') {
            $this->helper->logout();
        }
        $this->log("Logged out!");
        $this->log("**********");
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideCreateReport()
    {
        return JSON::loadFile(
            parent::getTestFiles()->getFile('report_builder', 'create_report', 'input')
        );
    }

    /**
     * @dataProvider provideCreateChart
     *
     *
     * @param array $options
     * @throws \Exception
     */
    public function testCreateChart(array $options)
    {
        $user = $options['user'];
        $chart = $options['chart'];
        $expectedFile = $options['expected']['file'];
        $success = $options['success'];

        $expected = JSON::loadFile(
            parent::getTestFiles()->getFile('report_builder', $expectedFile)
        );

        if ($user !== 'pub') {
            $this->helper->authenticate($user);
        }

        $result = $this->createChart($chart, $expected);

        $this->assertEquals($success, $result);

        if (true === $success) {
            $this->removeChart($chart);
        }

        if ($user !== 'pub') {
            $this->helper->logout();
        }
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideCreateChart()
    {
        return JSON::loadFile(
            parent::getTestFiles()->getFile('report_builder', 'create_chart', 'input')
        );
    }

    /**
     * @dataProvider provideEnumTemplates
     * @param array $options
     * @throws \Exception
     */
    public function testEnumTemplates(array $options)
    {

        $user = $options['user'];
        $expected = $options['expected'];
        $expectedFile = $expected['file'];
        $expectedHttpCode = $expected['http_code'];
        $expectedContentType = $expected['content_type'];

        if ($user !== 'pub') {
            $this->helper->authenticate($user);
        }

        $response = $this->helper->post(
            'controllers/report_builder.php',
            null,
            array('operation' => 'enum_templates')
        );

        list($content, $curlinfo) = $response;

        $this->assertEquals($expectedHttpCode, $curlinfo['http_code']);
        $this->assertEquals($expectedContentType, $curlinfo['content_type']);

        $expected = Json::loadFile(
            parent::getTestFiles()->getFile('report_builder', $expectedFile)
        );

        $this->assertEquals($expected, $content);

        if ($user !== 'pub') {
            $this->helper->logout();
        }
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideEnumTemplates()
    {
        return JSON::loadFile(
            parent::getTestFiles()->getFile('report_builder', 'enum_templates', 'input')
        );
    }

    /**
     * Create the chart specified by the provided $data.
     *
     * @param array $data
     * @param array $expected
     * @return mixed
     */
    private function createChart(array $data, $expected = array())
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
                'action' => $action
            );
        }

        return $this->processChartAction($data, $expected);
    }

    /**
     * Remove the chart specified by the provided $data parameter.
     *
     * @param array $data
     * @param array $expected
     * @return mixed
     */
    private function removeChart(array $data, $expected = array())
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
                'action' => $action
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
     * @return mixed
     */
    private function processChartAction(array $data, array $expected)
    {
        $expectedAction = $expected['action'];
        $expectedContentType = array_key_exists('content_type', $expected) ? $expected['content_type'] : 'application/json';
        $expectedHttpCode = array_key_exists('http_code', $expected) ? $expected['http_code'] : 200;
        $expectedResponse = $expected['response'];


        $this->log("Processing Chart Action: $expectedAction");

        $response = $this->helper->post('controllers/chart_pool.php', null, $data);

        $this->log('Expected Content-Type: [' . $expectedContentType . ']');
        $this->log("Response Content-Type: [" . $response[1]['content_type'] . "]");
        $this->log('Expected HTTP-Code   : [' . $expectedHttpCode . ']');
        $this->log("Response HTTP-Code   : [" . $response[1]['http_code'] . "]");

        if (($expectedContentType !== $response[1]['content_type']) ||
            ($expectedHttpCode !== $response[1]['http_code'])) {
            echo var_export($response, true) . "\n";
        }
        $this->assertEquals($expectedContentType, $response[1]['content_type']);
        $this->assertEquals($expectedHttpCode, $response[1]['http_code']);

        $json = $response[0];

        $this->log("\tResponse: " . json_encode($json));

        $this->assertEquals($expectedResponse, $json);
        $this->log(sprintf('Done Processing %s Chart Action!', $expectedAction));
        return $json['success'];
    }

    /**
     * Creates the report identified by the contents of $data.
     *
     * @param array $data
     * @return mixed
     */
    private function createReport(array $data)
    {
        $this->log("Creating Report");
        $response = $this->helper->post('controllers/report_builder.php', null, $data);

        $this->log("Response Content-Type: [" . $response[1]['content_type'] . "]");
        $this->log("Response HTTP-Code   : [" . $response[1]['http_code'] . "]");

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
     * Attempts to remove the report identified by the provided $reportId.
     *
     * @param $reportId
     * @return mixed
     */
    private function removeReportById($reportId)
    {
        $operation = 'remove_report_by_id';
        $data = array(
            'operation' => $operation,
            'selected_report' => $reportId
        );

        $response = $this->helper->post('controllers/report_builder.php', null, $data);

        $this->log("Response Content-Type: [" . $response[1]['content_type'] . "]");
        $this->log("Response HTTP-Code   : [" . $response[1]['http_code'] . "]");

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);

        $json = $response[0];

        $this->log("Response Data: " . json_encode($json));

        $this->assertArrayHasKey('action', $json);
        $this->assertArrayHasKey('success', $json);

        $this->assertEquals($operation, $json['action']);

        return $json['success'];
    }

    /**
     * Attempts to retrieve the next available report name for the currently
     * logged in user.
     *
     * @return mixed
     */
    private function getNewReportName()
    {
        $data = array(
            'operation' => 'get_new_report_name'
        );

        $response = $this->helper->post('controllers/report_builder.php', null, $data);

        $this->log("Response Content-Type: [" . $response[1]['content_type'] . "]");
        $this->log("Response HTTP-Code   : [" . $response[1]['http_code'] . "]");

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
     * Retrieves a list of all charts thare are currently available to the
     * logged in user.
     *
     * @return array json in the form:
     *   {
     *       "status": <string> ("success", ... ),
     *       "has_charts": <boolean>,
     *       "charts_in_other_roles": [],
     *       "queue": [
     *           {
     *               "chart_id": <string>,
     *               "thumbnail_link": <string> ( "/report_image_renderer.php?type=chart_pool&ref=3;19&token=" ),
     *               "chart_title": <string>,
     *               "chart_drill_details": <string>,
     *               "chart_date_description": <string> ( "2018-02-01 to 2018-02-28" ),
     *               "type": <string> ( "image" )
     *               "timeframe_type": <string> ( "Previous month" )
     *           }
     *       ]
     *   }
     */
    private function enumAvailableCharts()
    {
        $this->log('Enum Available Charts');
        $data = array(
            'operation' => 'enum_available_charts'
        );
        $response = $this->helper->post('controllers/report_builder.php', null, $data);

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
    private function reportImageRenderer(array $params)
    {
        $response = $this->helper->get('reports/builder/image', $params);

        print_r($response);
        $this->log("Response Content-Type: [" . $response[1]['content_type'] . "]");
        $this->log("Response HTTP-Code   : [" . $response[1]['http_code'] . "]");

        $this->assertEquals('image/png', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);
    }
}
