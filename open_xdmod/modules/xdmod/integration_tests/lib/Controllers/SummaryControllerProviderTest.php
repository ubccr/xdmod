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
     * @return array|object
     *
     * @throws \Exception
     */
    public function provideCreateReport()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('summary_controller_provider', 'create_report', 'input')
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

        $this->log('Logged out!');
        $this->log('**********');
    }
}
