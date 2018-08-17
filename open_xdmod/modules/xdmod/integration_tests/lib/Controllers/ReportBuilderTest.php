<?php

namespace IntegrationTests\Controllers;

use CCR\Json;
use TestHarness\TestFiles;
use TestHarness\XdmodTestHelper;

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
 * Remaining controllers:
 *   - report_builder
 *     - build_from_template.php
 *     - download_report.php
 *     - fetch_report_data.php
 *     - get_preview_data.php
 *     - send_report.php
 *
 * @package IntegrationTests\Controllers
 */
class ReportBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var XdmodTestHelper
     */
    protected $helper;

    /**
     * @var TestFiles
     */
    protected $testFiles;

    /**
     * @var bool
     */
    protected $verbose;

    private static $DEFAULT_EXPECTED = array(
        'content_type' => 'application/json',
        'http_code' => 200
    );


    /**
     * @return TestFiles
     * @throws \Exception
     */
    protected function getTestFiles()
    {
        if (!isset($this->testFiles)) {
            $this->testFiles = new TestFiles(__DIR__ . '/../../');
        }
        return $this->testFiles;
    }

    protected function setUp()
    {
        $this->verbose = getenv('TEST_VERBOSE');
        if (!isset($this->verbose)) {
            $this->verbose = false;
        }
        $this->helper = new XdmodTestHelper(__DIR__ . '/../../');
    }

    /**
     * @dataProvider provideEnumAvailableCharts
     * @param array $options
     * @throws \Exception
     */
    public function testEnumAvailableCharts(array $options)
    {
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

        $response = $this->helper->post("/controllers/report_builder.php", null, $params);

        $this->assertEquals($expected['content_type'], $response[1]['content_type']);
        $this->assertEquals($expected['http_code'], $response[1]['http_code']);

        $expectedFileName = $this->getTestFiles()->getFile('report_builder', $output['name'], 'output', $output['extension']);
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
            $this->getTestFiles()->getFile('report_builder', 'enum_available_charts', 'input')
        );
    }

    /**
     * @dataProvider provideEnumReports
     * @param array $options
     * @throws \Exception
     */
    public function testEnumReports(array $options)
    {
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

        $response = $this->helper->post("/controllers/report_builder.php", null, $params);

        $this->assertEquals($expected['content_type'], $response[1]['content_type']);
        $this->assertEquals($expected['http_code'], $response[1]['http_code']);

        $expectedFileName = $this->getTestFiles()->getFile('report_builder', $output['name'], 'output', $output['extension']);
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
            $this->getTestFiles()->getFile('report_builder', 'enum_reports', 'input')
        );
    }

    /**
     * @dataProvider provideCreateReport
     * @param array $options
     * @throws \Exception
     */
    public function testCreateReport(array $options)
    {
        $data = $options['data'];
        $user = $options['user'];
        $charts = $options['charts'];

        if ($user !== 'pub') {
            $this->helper->authenticate($user);
        }

        $this->log("Logged in as $user");

        $chartParams = array();

        foreach ($charts as $chart) {
            $chartParams = array();
            $this->log("Creating Chart...");

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

                // render the chart image so that a temp file is created on the backend.
                $this->reportImageRenderer($results);
            }

        }

        // render the charts as volatile
        // also, ensure the chart values in the report data are up to date.
        $count = 1;
        foreach ($chartParams as $chartData) {
            // first, retrieve the data previously gathered in the chart creation
            // & rendering loop above.
            $params = $chartData['params'];
            $startDate = $chartData['start_date'];
            $endDate = $chartData['end_date'];
            $chartRef = $params['ref'];

            // generate the cacheRef && chartData keys & values.
            $chartCacheRefKey = "chart_cache_ref_$count";
            $chartCacheRef = "$startDate-$endDate;xd_report_$chartRef";

            $chartDataKey = "chart_data_$count";
            $chartDataValue = $chartData['chart_id'];

            // Update the chart type to volatile
            $params['type'] = 'volatile';

            // render the volatile chart.
            $this->reportImageRenderer($params);

            // Ensure that the chart_ref & chart_data values for this entry
            // are up to date.
            $data[$chartCacheRefKey] = $chartCacheRef;
            $data[$chartDataKey] = $chartDataValue;

            $count++;
        }

        // Retrieve the next available report name for this user.
        $reportName = $this->getNewReportName();

        $data['report_name'] = $reportName;

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
            $this->getTestFiles()->getFile('report_builder', 'create_report', 'input')
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
            $this->getTestFiles()->getFile('report_builder', $expectedFile)
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
            $this->getTestFiles()->getFile('report_builder', 'create_chart', 'input')
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
            '/controllers/report_builder.php',
            null,
            array('operation' => 'enum_templates')
        );

        list($content, $curlinfo) = $response;

        $this->assertEquals($expectedHttpCode, $curlinfo['http_code']);
        $this->assertEquals($expectedContentType, $curlinfo['content_type']);

        $expected = Json::loadFile(
            $this->getTestFiles()->getFile('report_builder', $expectedFile)
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
            $this->getTestFiles()->getFile('report_builder', 'enum_templates', 'input')
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

        $response = $this->helper->post('/controllers/chart_pool.php', null, $data);

        $this->log("Response Content-Type: [" . $response[1]['content_type'] . "]");
        $this->log("Response HTTP-Code   : [" . $response[1]['http_code'] . "]");

        $this->assertEquals($expectedContentType, $response[1]['content_type']);
        $this->assertEquals($expectedHttpCode, $response[1]['http_code']);

        $json = $response[0];

        $this->log("\tResponse: " . json_encode($json));

        $this->assertEquals($expectedResponse, $json);

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
        $response = $this->helper->post('/controllers/report_builder.php', null, $data);

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

        $response = $this->helper->post('/controllers/report_builder.php', null, $data);

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

        $response = $this->helper->post('/controllers/report_builder.php', null, $data);

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
    private function reportImageRenderer(array $params)
    {
        $response = $this->helper->get('/report_image_renderer.php', $params);

        $this->log("Response Content-Type: [" . $response[1]['content_type'] . "]");
        $this->log("Response HTTP-Code   : [" . $response[1]['http_code'] . "]");

        $this->assertEquals('image/png', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);
    }

    private function log($msg)
    {
        if ($this->verbose) {
            echo "$msg\n";
        }
    }
}
