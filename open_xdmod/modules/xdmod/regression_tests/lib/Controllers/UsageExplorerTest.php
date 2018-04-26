<?php

namespace Controllers;

class UsageExplorerTest extends \PHPUnit_Framework_TestCase
{
    protected static $baseDir;
    protected static $helper;
    protected static $messages = array();
    protected static $expectedEndpoint;
    /*
     * These are used when testing against two different databases
     * such as when doing federation testing, this allows certain values to be
     * ignored, such as the addition of the organization abbrev and resource
     * when added to the core to help differentiate.
     */
    protected static $replaceRegex = array();
    protected static $replacements = array(
        'REPLACED'
    );
    protected $delta = 1.0e-2;
    /*
     * Allow for skipping of certain tests that have known issues
     */
    protected $skip = array();
    /*
     * What aggregation units to run tests on, as well as skip certain tests
     * depending on the aggregation unit.
     */
    protected $aggregationUnits = array(
        'Day' => array(),
        'Month' => array(),
        'Quarter' => array(),
        'Year' => array()
    );

    public static function tearDownAfterClass()
    {
        self::$helper->logout();
        if(count(self::$messages)){
            print_r("\n----OTHER NOTICES----\n" . implode("\n", self::$messages) . "\n----OTHER NOTICES----\n");
        }
    }
    /**
     * @dataProvider csvExportProvider
     */
    public function testCsvExport($testName, $input, $expectedFile, $userRole)
    {
        $aggUnit = $input['aggregation_unit'];
        $datasetType = $input['dataset_type'];
        $fullTestName = $testName . $datasetType . '-' . $aggUnit . '-' . $userRole;
        if(in_array($testName, $this->skip) || in_array($testName, $this->aggregationUnits[$aggUnit])) {
            $this->markTestIncomplete($fullTestName . ' intentionally skipped');
        }
        else {
            $response = self::$helper->post('/controllers/user_interface.php', null, $input);
            $csvdata = $response[0];
            $curldata = $response[1];
            /*
             * this temporarliy allows the "failed" tests of the public
             * user to pass, need to figure out a more robust way for
             * public user not having access to pass
             */
            if(gettype($csvdata) === "array"){
                $csvdata = print_r($csvdata, 1);
            }
            $csvdata = preg_replace(self::$replaceRegex, self::$replacements, $csvdata);

            if(!empty($expectedFile)){
                $expected = file_get_contents($expectedFile);
                $expected = preg_replace(self::$replaceRegex, self::$replacements, $expected);
                if($expected === $csvdata){
                    $this->assertEquals($expected, $csvdata);
                    return;
                }

                $failures = $this->csvDataDiff($expected, $csvdata, $fullTestName);
                if(empty($failures))
                {
                    // This happens because of maths (specifically floating point maths)
                    self::$messages[] = "$fullTestName IS ONLY ==";
                    return;
                }
                else {
                    if($userRole === 'public' || substr($expectedFile, -10) !== 'public.csv'){
                        throw new PHPUnit_Framework_ExpectationFailedException(
                            count($failures)." assertions failed:\n\t".implode("\n\t", $failures)
                        );
                    }
                }
            }

            if(empty($expectedFile) && $userRole !== 'public'){
                $this->markTestIncomplete(
                    'Cant create expected output for non public user before public user utput created.'
                );
            }
            else {
                $endpoint = parse_url(self::$helper->getSiteUrl());
                $outputDir = self::$baseDir .
                    '/expected/' .
                    $endpoint['host'] .
                    '/' . $testName .
                    '/'  ;
                if(!file_exists($outputDir)){
                    mkdir($outputDir, 0777, true);
                }
                $outputDir = realpath($outputDir);

                $outputFile = $outputDir . '/' . $datasetType . '-' . $aggUnit . '-' . $userRole . '.csv';
                file_put_contents(
                    $outputFile,
                    $csvdata
                );
                $this->markTestSkipped(
                    'Created Expected output for ' . $fullTestName
                );
            }
        }
    }
    protected function defaultSetup(){
        self::$helper = new \TestHarness\XdmodTestHelper();
        $envUserrole = getenv('REG_TEST_USER_ROLE');
        if(!empty($envUserrole)){
            self::$helper->authenticate($envUserrole);
        }
        $envBaseDir = getenv('REG_TEST_BASE');
        if(!empty($envBaseDir)){
            self::$baseDir = __DIR__ . $envBaseDir;
        }
        $envResource = getenv('REG_TEST_RESOURCE');
        $envRegex = getenv('REG_TEST_REGEX');
        if(!empty($envRegex)){
            self::$replaceRegex = explode(',', $envRegex);
        }
        $envReplace = getenv('REG_TEST_REPLACE');
        if(!empty($envReplace)){
            self::$replacements = explode(',', $envReplace);
        }
        self::$expectedEndpoint = 'reference';
        $envExpected = getenv('REG_TEST_ALT_EXPECTED');
        if(!empty($envExpected)){
            self::$expectedEndpoint = $envExpected;
        }
    }
    public function csvExportProvider()
    {
        self::$baseDir = __DIR__ . '/../../../tests/artifacts/xdmod-test-artifacts/xdmod/regression/current/';

        $this->defaultSetup();

        $statistics = array(
            'active_person_count',
            'active_pi_count',
            'active_resource_count',
            'avg_cpu_hours',
            'avg_job_size_weighted_by_cpu_hours',
            'avg_node_hours',
            'avg_processors',
            'avg_waitduration_hours',
            'avg_wallduration_hours',
            'expansion_factor',
            'job_count',
            'max_processors',
            'min_processors',
            'normalized_avg_processors',
            'running_job_count',
            'started_job_count',
            'submitted_job_count',
            'total_cpu_hours',
            'total_node_hours',
            'total_waitduration_hours',
            'total_wallduration_hours',
            'utilization'
        );

        $group_bys = array(
            'fieldofscience',
            'jobsize',
            'jobwalltime',
            'nodecount',
            'none',
            'nsfdirectorate',
            'parentscience',
            'person',
            'pi',
            'queue',
            'resource',
            'resource_type',
            'username'
        );

        $varSettings = array(
            'realm' => array('Jobs'),
            'dataset_type' => array('aggregate', 'timeseries'),
            'statistic' => $statistics,
            'group_by' => $group_bys,
            'aggregation_unit' => array_keys($this->aggregationUnits)
        );

        return $this->generateTests($varSettings);
    }
    protected function getExpectedFile($baseDir, $testName, $role, $expectedEndpoint){
        $expectedFile = $baseDir . '/expected/' . $expectedEndpoint .
            '/' . $testName;
        if(($expectedFilename = realpath($expectedFile . '-' . $role . '.csv' ) ) === false){
            $expectedFilename = realpath($expectedFile . '-public.csv');
        }
        return $expectedFilename;
    }

    protected function generateTests($allSettings){
        $reference = array(
            'public_user' => (self::$helper->getUserrole() === 'public') ? 'true' : 'false',
            /*
             * This date specifically only tests the date range of the reference data.
             * To use this on another dataset be sure to change your date range.
             */
            'start_date' => '2016-12-22',
            'end_date' => '2017-01-01',
            'timeframe_label' => '2016',
            'scale' => '1',
            'aggregation_unit' => 'Auto',
            'dataset_type' => 'aggregate',
            'thumbnail' => 'n',
            'query_group' => 'po_usage',
            'display_type' => 'line',
            'combine_type' => 'side',
            'limit' => '10',
            'offset' => '0',
            'log_scale' => 'n',
            'show_guide_lines' => 'y',
            'show_trend_line' => 'y',
            'show_percent_alloc' => 'n',
            'show_error_bars' => 'y',
            'show_aggregate_labels' => 'n',
            'show_error_labels' => 'n',
            'show_title' => 'y',
            'width' => '916',
            'height' => '484',
            'legend_type' => 'bottom_center',
            'font_size' => '3',
            'inline' => 'n',
            'operation' => 'get_data',
            'format' => 'csv'
        );

        $testData = array();
        foreach(\TestHarness\Utilities::getCombinations($allSettings) as $settings) {
            $testReqData = $reference;
            foreach ($settings as $key => $value) {
                $testReqData[$key] = $value;
            }

            $testName = $testReqData['realm'] . '/' . $testReqData['group_by'] .
             '/' . $testReqData['statistic'] . '/';
             $fullName =  $testName . $testReqData['dataset_type'] .
                '-' . $testReqData['aggregation_unit'];
            $expectedFilename =
            $this->getExpectedFile(
                self::$baseDir,
                $fullName,
                self::$helper->getUserrole(),
                self::$expectedEndpoint
            );
            $testData[$fullName . '-' . self::$helper->getUserrole()] =
                array(
                    $testName,
                    $testReqData,
                    $expectedFilename,
                    self::$helper->getUserrole()
                );
        }
        if (getenv('REG_TEST_ALL') === '1') {
            return $testData;
        } else {
            return array_intersect_key($testData, array_flip(array_rand($testData, 35)));
        }
    }

    protected function getResultAsCSV($raw) {
        $datasRegEx = '/(?<=---------\n)([\s\S]*)(?=\n---------)/';
        $matches;
        preg_match($datasRegEx, $raw, $matches, PREG_OFFSET_CAPTURE, 0);
        $csv = str_getcsv($matches[0][0], "\n"); //parse the rows
        foreach($csv as &$row){
            $row = str_getcsv($row); //parse the items in rows
        }
        return $csv;
    }

    protected function getAssocCSV($csv) {
        array_walk($csv, function (&$a) use ($csv) {
            $a = array_combine($csv[0], $a);
        });
        return $csv;
    }

    protected function csvDataDiff($expected, $provided, $testName){
        $failures = [];
        $expectedCSV = $this->getResultAsCSV($expected);
        $providedCSV = $this->getResultAsCSV($provided);

        $expectedRows = count($expectedCSV);
        try {
            $this->assertCount($expectedRows, $providedCSV, $testName . ' Row count != ');
        } catch(PHPUnit_Framework_ExpectationFailedException $e) {
            $failures[] = $e->getMessage();
        }
        if($expectedRows === count($providedCSV)){
            $expectedHeader = $expectedCSV[0];
            $providedHeader = $providedCSV[0];
            $useAssoc = false;
            if(count(array_diff_assoc($expectedHeader, $providedHeader)) > 0){
                sort($expectedHeader);
                sort($providedHeader);
                if($expectedHeader !== $providedHeader){
                    try {
                        $this->assertTrue(false, $testName . ' CSV Headers different');
                    } catch(PHPUnit_Framework_ExpectationFailedException $e) {
                        $failures[] = $e->getMessage();
                    }
                }
                $useAssoc = true;
                self::$messages[] = $testName . ' column order mismatch';
                $expectedCSV = self::getAssocCSV($expectedCSV);
                $providedCSV = self::getAssocCSV($providedCSV);
            }

            for($i = 1; $i < $expectedRows; $i++){
                $expectedRow = $expectedCSV[$i];
                $providedRow = $providedCSV[$i];

                $expectedColumnCount = count($expectedRow);
                try {
                    $this->assertCount($expectedColumnCount, $providedRow, $testName . ' Column count != ');
                } catch(PHPUnit_Framework_ExpectationFailedException $e) {
                    $failures[] = $e->getMessage();
                }
                foreach($expectedHeader as $key => $value){
                    $index = $useAssoc ? $value : $key;
                    $expectedRowValue = $expectedRow[$index];
                    $providedRowValue = $providedRow[$index];
                    $rowMessage =
                        $testName . ' values do not match for column ' .
                        $index .
                        ' row ' . $i;
                    if(is_numeric($expectedRowValue)){
                        $errorFormula = "| {expected} $expectedRowValue - {actual} $providedRowValue |";
                        if(abs($expectedRowValue) > 1.0e-30) {
                            $relativeError = abs($expectedRowValue - $providedRowValue) / $expectedRowValue;
                            $errorFormula .= " / $expectedRowValue";
                        } else {
                            $relativeError = abs($expectedRowValue - $providedRowValue);
                        }
                        try {
                            $this->assertTrue($relativeError < $this->delta, $testName . " ( $errorFormula ) => " . $relativeError  . ' > ' . $this->delta );
                        } catch(PHPUnit_Framework_ExpectationFailedException $e) {
                            $failures[] = $e->getMessage();
                        }

                    }
                    else {
                        try {
                            $this->assertEquals($expectedRowValue, $providedRowValue, $rowMessage);
                        } catch(PHPUnit_Framework_ExpectationFailedException $e) {
                            $failures[] = $e->getMessage();
                        }
                    }
                }
            }
        }
        return $failures;
    }
}
