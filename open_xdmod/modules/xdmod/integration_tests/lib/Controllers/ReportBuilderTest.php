<?php

namespace IntegrationTests\Controllers;

use CCR\Json;
use TestHarness\TestFiles;
use TestHarness\XdmodTestHelper;
use Traits\UtilityFunctions;

/**
 * Class ReportBuilderTest.
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
 */
class ReportBuilderTest extends \PHPUnit_Framework_TestCase
{
    use UtilityFunctions;
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
        'http_code' => 200,
    );

    /**
     * @return TestFiles
     *
     * @throws \Exception
     */
    protected function getTestFiles()
    {
        if (!isset($this->testFiles)) {
            $this->testFiles = new TestFiles(__DIR__.'/../../');
        }

        return $this->testFiles;
    }

    protected function setUp()
    {
        $this->verbose = getenv('TEST_VERBOSE');
        if (!isset($this->verbose)) {
            $this->verbose = false;
        }
        $this->helper = new XdmodTestHelper(__DIR__.'/../../');
    }

    /**
     * @dataProvider provideEnumAvailableCharts
     *
     * @param array $options
     *
     * @throws \Exception
     */
    public function testEnumAvailableCharts(array $options)
    {
        $operation = 'enum_available_charts';

        $user = $options['user'];
        $expected = isset($options['expected']) ? $options['expected'] : self::$DEFAULT_EXPECTED;
        $output = isset($options['output']) ? $options['output'] : array(
            'name' => $user."_$operation",
            'extension' => '.json',
        );

        if ($user !== 'pub') {
            $this->helper->authenticate($user);
        }

        $params = array(
            'operation' => $operation,
        );

        $response = $this->helper->post('/controllers/report_builder.php', null, $params);

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
     *
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
     *
     * @param array $options
     *
     * @throws \Exception
     */
    public function testEnumReports(array $options)
    {
        $operation = 'enum_reports';

        $user = $options['user'];
        $expected = isset($options['expected']) ? $options['expected'] : self::$DEFAULT_EXPECTED;
        $output = isset($options['output']) ? $options['output'] : array(
            'name' => $user."_$operation",
            'extension' => '.json',
        );

        if ($user !== 'pub') {
            $this->helper->authenticate($user);
        }

        $params = array(
            'operation' => $operation,
        );

        $response = $this->helper->post('/controllers/report_builder.php', null, $params);

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
     *
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
     *
     * @param array $options
     *
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
            $this->log('Creating Chart...');

            // create the chart...
            $success = $this->createChart($chart);
            $this->assertTrue($success, 'Unable to create chart: '.json_encode($chart));

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
                    'end_date' => $endDate,
                );

                // render the chart image so that a temp file is created on the backend.
                $this->reportImageRenderer($results);
            }
        }

        // render the charts as volatile
        foreach ($chartParams as $chartData) {
            $params = $chartData['params'];

            $params['type'] = 'volatile';
            $this->reportImageRenderer($params);
        }

        // Retrieve the next available report name for this user.
        $reportName = $this->getNewReportName();

        $data['report_name'] = $reportName;

        // Attempt to create the report.
        $reportId = $this->createReport($data);

        // Ensure we were successful
        $this->assertTrue(isset($reportId), 'Did not receive a report_id back from create_report');

        $this->log('Removing Report...');
        // Attempt to remove the report
        $removedReport = $this->removeReportById($reportId);
        $this->log('Report Removed!');

        // Ensure that we were successful
        $this->assertTrue($removedReport, "Did not remove the report identified by: $reportId");

        // Now, go through each of the charts and remove them as well.
        foreach ($charts as $chart) {
            $success = $this->removeChart($chart);
            $this->assertTrue($success, 'Unable to remove chart: '.json_encode($chart));
        }

        $this->log("Logging out of: $user");
        if ($user !== 'pub') {
            $this->helper->logout();
        }
        $this->log('Logged out!');
        $this->log('**********');
    }

    /**
     * @return array|object
     *
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
     * @param array $options
     *
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
     *
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
     *
     * @param array $options
     *
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
     *
     * @throws \Exception
     */
    public function provideEnumTemplates()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('report_builder', 'enum_templates', 'input')
        );
    }

    /**
     * Retrieves a list of all charts thare are currently available to the
     * logged in user.
     *
     * @return array json in the form:
     *               {
     *               "status": <string> ("success", ... ),
     *               "has_charts": <boolean>,
     *               "charts_in_other_roles": [],
     *               "queue": [
     *               {
     *               "chart_id": <string>,
     *               "thumbnail_link": <string> ( "/report_image_renderer.php?type=chart_pool&ref=3;19&token=" ),
     *               "chart_title": <string>,
     *               "chart_drill_details": <string>,
     *               "chart_date_description": <string> ( "2018-02-01 to 2018-02-28" ),
     *               "type": <string> ( "image" )
     *               "timeframe_type": <string> ( "Previous month" )
     *               }
     *               ]
     *               }
     */
    private function enumAvailableCharts()
    {
        $data = array(
            'operation' => 'enum_available_charts',
        );
        $response = $this->helper->post('/controllers/report_builder.php', null, $data);

        $this->log('Response Content-Type: ['.$response[1]['content_type'].']');
        $this->log('Response HTTP-Code   : ['.$response[1]['http_code'].']');

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);

        $json = $response[0];

        $this->log('Response Data: '.json_encode($json));

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

        $this->log('Response Content-Type: ['.$response[1]['content_type'].']');
        $this->log('Response HTTP-Code   : ['.$response[1]['http_code'].']');

        $this->assertEquals('image/png', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);
    }
}
