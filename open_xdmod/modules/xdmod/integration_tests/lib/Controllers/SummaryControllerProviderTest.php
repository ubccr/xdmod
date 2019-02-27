<?php

namespace IntegrationTests\Controllers;

use CCR\Json;
use TestHarness\TestFiles;
use TestHarness\XdmodTestHelper;
use Traits\UtilityFunctions;

/**
 * Class SummaryControllerProvider.
 *
 * Currently tested endpoints:
 *   - getChartsReports
 *
 * Remaining endpoints:
 *   - getPortlets
 *   - setLayout
 *   - resetLayout
 */
class SummaryControllerProviderTest extends \PHPUnit_Framework_TestCase
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
     * @dataProvider provideCreateReport
     * @param array $options
     * @throws \Exception
     */
    public function testCreateReport(array $options)
    {
        $data = $options['data'];
        $user = $options['user'];
        $expectedFile = $options['expected']['file'];
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
        $this->assertTrue(isset($reportId), "Did not receive a report_id back from create_report");

        // Get reports
        $response = $this->helper->get('rest/v1/summary/chartsreports');

        $expected = JSON::loadFile(
            $this->getTestFiles()->getFile('summary_controller_provider', $expectedFile)
        );

        $this->assertEquals($expected['total'], $response[0]['total']);
        $this->assertEquals($expected['data'][0]['type'], $response[0]['data'][0]['type']);

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
            $this->getTestFiles()->getFile('summary_controller_provider', 'create_report', 'input')
        );
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

        $response = $this->helper->get('rest/v1/summary/chartsreports');
        $this->assertEquals(1, $response[0]['total']);
        $this->assertEquals('Chart', $response[0]['data'][0]['type']);

        $justthischart = $this->helper->get('rest/v1/metrics/explorer/queries/' . $recordid);
        $this->assertTrue($justthischart[0]['success']);
        $this->assertEquals("Test &lt; &lt;img src=&quot;test.gif&quot; onerror=&quot;alert()&quot; /&gt;", $justthischart[0]['data']['name']);

        $cleanup = $this->helper->delete('rest/v1/metrics/explorer/queries/' . $recordid);
        $this->assertTrue($cleanup[0]['success']);
    }


    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideCreateChart()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('summary_controller_provider', 'create_chart', 'input')
        );
    }
}
