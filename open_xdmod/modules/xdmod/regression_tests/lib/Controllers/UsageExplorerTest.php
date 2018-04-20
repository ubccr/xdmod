<?php

namespace RegressionTests\Controllers;

class UsageExplorerTest extends \PHPUnit_Framework_TestCase
{
    protected static $baseDir;
    protected static $helper;
    protected static $messages = array();
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
    private $delta = 1.0e-2;
    /*
     * Allow for skipping of certain tests that have known issues
     */
    private $skip = array();
    /*
     * What aggregation units to run tests on, as well as skip certain tests
     * depending on the aggregation unit.
     */
    private $aggregationUnits = array(
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
    public function testCsvExport($testName, $input, $expectedFile)
    {
        $aggUnit = $input['aggregation_unit'];
        $fullTestName = $testName . ' by ' . $aggUnit;
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
            if ($expectedFile === null) {

                $endpoint = parse_url(self::$helper->getSiteUrl());
                $outputDir = self::$baseDir .
                    '/expected/' .
                    $endpoint['host'] .
                    '/' . $testName .
                    '/'  ;
                if(!file_exists($outputDir)){
                    mkdir($outputDir, 0777, true);
                }
                file_put_contents($outputDir . $aggUnit . '-' . self::$helper->getUserrole() . '.csv', $csvdata);
                $this->markTestSkipped(
                    'Created Expected output for ' . $fullTestName
                );
                return;
            }
            $expected = file_get_contents($expectedFile);
            /*
             * this temporarliy allows the "failed" tests of the public
             * user to pass, need to figure out a more robust way for
             * public user not having access to pass
             */

            $csvdata = preg_replace(self::$replaceRegex, self::$replacements, $csvdata);
            $expected = preg_replace(self::$replaceRegex, self::$replacements, $expected);

            if($expected === $csvdata){
                $this->assertEquals($expected, $csvdata);
            }
            else {
                $this->csvDataDiff($expected, $csvdata, $fullTestName);
            }
        }

    }

    public function csvExportProvider()
    {
        /*
         * Generaly the environment variables are used for
         * federation tests (comparing different endpoints)
         * the two that are also used on other tests are
         * REG_TEST_USER_ROLE
         * REG_TEST_BASE_DIR
         */
        $envUserrole = getenv('REG_TEST_USER_ROLE');
        self::$helper = new \TestHarness\XdmodTestHelper();
        if(!empty($envUserrole)){
            self::$helper->authenticate($envUserrole);
        }
        self::$baseDir = __DIR__ . '/../../../tests/artifacts/xdmod-test-artifacts/xdmod/regression/current/';
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
        $envFormat = getenv('REG_TEST_FORMAT');
        if(empty($envFormat)){
            $envFormat = 'csv';
        }
        $expectedEndpoint = getenv('REG_TEST_ALT_EXPECTED');
        if(empty($expectedEndpoint)){
            $expectedEndpoint = 'reference';
        }

        $testData = array();

        foreach (glob(self::$baseDir . '/input/*.json') as $filename) {

            $testName = basename($filename, '.json');
            $testReqData = json_decode(file_get_contents($filename), true);
            $testReqData['public_user'] = empty($envUserrole) ? 'true' : 'false';
            $testReqData['format'] = $envFormat;
            if(!empty($envResource)){
                $testReqData['resource'] = $envResource;
            }
            foreach($this->aggregationUnits as $k => $v){
                $testReqData['aggregation_unit'] = $k;
                $testCase = array(
                    $testName,
                    $testReqData,
                    null
                );
                $expectedFile = self::$baseDir .
                    '/expected/' .
                    $expectedEndpoint .
                    '/' . $testName .
                    '/' . $k .
                    '-' . (empty($envUserrole) ? 'public' : $envUserrole) .
                    '-8.0.0.csv';
                if (file_exists($expectedFile) ) {
                    $testCase[2] = $expectedFile;
                } else {
                    $expectedFile = self::$baseDir .
                        '/expected/' .
                        $expectedEndpoint .
                        '/' . $testName .
                        '/' . $k .
                        '-' . (empty($envUserrole) ? 'public' : $envUserrole) .
                        '.csv';
                    if (file_exists($expectedFile) ) {
                        $testCase[2] = $expectedFile;
                    }
                }
                $testData[$testName. '-' . $k . '-' . (empty($envUserrole) ? 'public' : $envUserrole)] = $testCase;
            }
        }
        if(empty($testData)){
            $this->markTestIncomplete(
                'No input, please run assets/scripts/maketest.js'
            );
        }
        if (getenv('REG_TEST_ALL') === '1') {
            return $testData;
        } else {
            return array_intersect_key($testData, array_flip(array_rand($testData, 35)));
        }
    }
    private function getResultAsCSV($raw){
        $datasRegEx = '/(?<=---------\n)([\s\S]*)(?=\n---------)/';
        $matches;
        preg_match($datasRegEx, $raw, $matches, PREG_OFFSET_CAPTURE, 0);
        $csv = str_getcsv($matches[0][0], "\n"); //parse the rows
        foreach($csv as &$row){
            $row = str_getcsv($row); //parse the items in rows
        }
        return $csv;
    }
    private function getAssocCSV($csv){
        array_walk($csv, function (&$a) use ($csv) {
            $a = array_combine($csv[0], $a);
        });
        return $csv;
    }

    private function csvDataDiff($expected, $provided, $testName){
        $expectedCSV = $this->getResultAsCSV($expected);
        $providedCSV = $this->getResultAsCSV($provided);
        $expectedRows = count($expectedCSV);
        $this->assertCount($expectedRows, $providedCSV, $testName . ' Row count != ');
        if($expectedRows === count($providedCSV)){
            $expectedHeader = $expectedCSV[0];
            $providedHeader = $providedCSV[0];
            $useAssoc = false;
            if(count(array_diff_assoc($expectedHeader, $providedHeader)) > 0){
                sort($expectedHeader);
                sort($providedHeader);
                if($expectedHeader !== $providedHeader){
                    $this->assertTrue(false, $testName . ' CSV Headers different');
                }
                $useAssoc = true;
                self::$messages[] = $testName . ' column order mismatch';
                $expectedCSV = $this->getAssocCSV($expectedCSV);
                $providedCSV = $this->getAssocCSV($providedCSV);
            }

            for($i = 1; $i < $expectedRows; $i++){
                $expectedRow = $expectedCSV[$i];
                $providedRow = $providedCSV[$i];

                $expectedColumnCount = count($expectedRow);
                $this->assertCount($expectedColumnCount, $providedRow, $testName . ' Column count != ');
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

                        $this->assertTrue($relativeError < $this->delta, $testName . " ( $errorFormula ) => " . $relativeError  . ' > ' . $this->delta );
                    }
                    else {
                        $this->assertEquals($expectedRowValue, $providedRowValue, $rowMessage);
                    }
                }
            }
        }
    }
}
