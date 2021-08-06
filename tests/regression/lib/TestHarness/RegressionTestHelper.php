<?php
/**
 * @package OpenXdmod
 * @subpackage TestHarness
 */

namespace TestHarness;

/**
 * Everything you need to test for regressions.
 */
class RegressionTestHelper extends XdmodTestHelper
{
    /**
     * Base directory of regression test data.
     *
     * Set by environment variable REG_TEST_BASE.
     *
     * @var string
     */
    private static $baseDir;

    /**
     * Name of directory containing expected data.
     *
     * Set by environment variable REG_TEST_ALT_EXPECTED.
     *
     * @var string
     */
    private static $expectedEndpoint = 'reference';

    /**
     * Values that will be replaced in CSV data
     *
     * These are used when testing against two different databases such as when
     * doing federation testing, this allows certain values to be ignored, such
     * as the addition of the organization abbrev and resource when added to
     * the core to help differentiate.
     *
     * Set by environment variable REG_TEST_REGEX.
     *
     * @var array
     */
    private static $replaceRegex = [];

    /**
     * Values that will be used as replacements in CSV data
     *
     * Set by environment variable REG_TEST_REPLACE.
     *
     * @see $replaceRegex
     * @var array
     */
    private static $replacements = ['REPLACED'];

    /**
     * Directory where timing output data will be stored.
     *
     * Set by environment variable REG_TIME_LOGDIR.
     *
     * @var string
     */
    private static $timingOutputDir;

    /**
     * Allowed relative error in differences of values.
     *
     * @var float
     */
    private static $delta = 1.0e-8;

    /**
     * Value close enough to zero to be considered zero.
     *
     * @var float
     */
    private static $almostZero = 1.0e-30;

    /*
     * Allow for skipping of certain tests that have known issues.
     *
     * @var array
     */
    private static $skip = [];

    /**
     * Number of tests to run when REG_TEST_ALL is not set.
     *
     * @var integer
     */
    private static $randomTestCount = 35;

    /**
     * User role set by REG_TEST_USER_ROLE environment variable.
     *
     * @var string
     */
    private static $envUserrole;

    /**
     * Warning messages generated during tests.
     *
     * @var array
     */
    private $messages = [];

    /**
     * Create an instance of the helper.
     *
     * @param array $config Configuration options.
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $envRegex = getenv('REG_TEST_REGEX');
        if (!empty($envRegex)) {
            self::$replaceRegex = explode(',', $envRegex);
        }

        $envReplace = getenv('REG_TEST_REPLACE');
        if (!empty($envReplace)) {
            self::$replacements = explode(',', $envReplace);
        }

        $envExpected = getenv('REG_TEST_ALT_EXPECTED');
        if (!empty($envExpected)) {
            self::$expectedEndpoint = $envExpected;
        }

        $timingTestDir = getenv('REG_TIME_LOGDIR');
        if (!empty($timingTestDir)) {
            self::$timingOutputDir = $timingTestDir;
        }
    }

    /**
     * Get the base directory of regression test data.
     *
     * Set by environment variable REG_TEST_BASE.
     *
     * @return string Absolute path to regression test data base directory.
     */
    private static function getBaseDir()
    {
        if (!isset(self::$baseDir)) {
            $envBaseDir = getenv('REG_TEST_BASE');
            if (empty($envBaseDir)) {
                self::$baseDir = __DIR__ . '/../../../artifacts/xdmod/regression/current';
            } else {
                self::$baseDir = __DIR__ . $envBaseDir;
            }
        }

        return self::$baseDir;
    }

    /**
     * Get the user role for environment.
     *
     * This function is intentionally not named getUserrole so as to not
     * conflict with the non-static function from the parent class.  It is
     * intended to be used by static functions that are called before the user
     * is authenticated.
     *
     * @return string User role name.
     */
    private static function getEnvUserrole()
    {
        if (!isset(self::$envUserrole)) {
            $envUserrole = getenv('REG_TEST_USER_ROLE');
            if (empty($envUserrole)) {
                self::$envUserrole = 'public';
            } else {
                self::$envUserrole = $envUserrole;
            }
        }

        return self::$envUserrole;
    }

    /**
     * Output messages generated during tests.
     */
    public function outputMessages()
    {
        if (count($this->messages)) {
            print_r(
                "\n----OTHER NOTICES----\n"
                . implode("\n", $this->messages)
                . "\n----OTHER NOTICES----\n"
            );
        }
    }

    /**
     * Authenticate as the given role.
     *
     * If no role is specified the role from the environment variable
     * REG_TEST_USER_ROLE will be used.
     *
     * @see \TestHarness\XdmodTestHelper::authenticate()
     * @param string $userrole The user's role.
     */
    public function authenticate($userrole = null)
    {
        if ($userrole === null) {
            $userrole = self::getEnvUserrole();
        }

        // The public user cannot authenticate.
        if ($userrole !== 'public') {
            parent::authenticate($userrole);
        }
    }

    /**
     * Generate permutations of the given settings.
     *
     * The default dates specifically only tests the date range of the
     * reference job data.  To use this on another data set be sure to specify
     * a date range.
     *
     * @param array $allSettings Array of all settings to generate test data.
     * @param string $startDate Start date in YYYY-MM-DD format.
     * @param string $endDate End date in YYYY-MM-DD format.
     * @return array Test data.
     */
    public static function generateTests(
        array $allSettings,
        $startDate = '2016-12-22',
        $endDate = '2017-01-01'
    ) {
        $userrole = self::getEnvUserrole();

        $reference = array(
            'public_user' => ($userrole === 'public') ? 'true' : 'false',
            'start_date' => $startDate,
            'end_date' => $endDate,
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
        foreach (Utilities::getCombinations($allSettings) as $settings) {
            $testReqData = $reference;
            foreach ($settings as $key => $value) {
                $testReqData[$key] = $value;
            }

            $testName
                = $testReqData['realm']
                . '/'
                . $testReqData['group_by']
                . '/'
                . $testReqData['statistic']
                . '/';

            $fullName
                = $testName
                . $testReqData['dataset_type']
                . '-'
                . $testReqData['aggregation_unit'];

            $expectedFilename = self::getExpectedFile(
                self::getBaseDir(),
                $fullName,
                $userrole,
                self::$expectedEndpoint
            );

            $testData[$fullName . '-' . $userrole] = array(
                $testName,
                $testReqData,
                $expectedFilename,
                $userrole
            );
        }

        if (getenv('REG_TEST_ALL') === '1') {
            return $testData;
        } else {
            return array_intersect_key(
                $testData,
                array_flip(array_rand($testData, self::$randomTestCount))
            );
        }
    }

    /**
     * Get the path to the file containing the expected data for a given test.
     *
     * @param mixed $name Description.
     */
    private static function getExpectedFile(
        $baseDir,
        $testName,
        $role,
        $expectedEndpoint
    ) {
        $expectedFileBase = implode(
            DIRECTORY_SEPARATOR,
            [$baseDir, 'expected', $expectedEndpoint, $testName]
        );

        $expectedFilename = realpath($expectedFileBase . '-' . $role . '.csv');
        if ($expectedFilename === false) {
            $expectedFilename = realpath($expectedFileBase . '-reference.csv');
        }

        return $expectedFilename;
    }

    /**
     * Check that the CSV export returns the expected data.
     *
     * @param string $testName Name of the test.
     * @param array $input Controller input
     * @param string $expectedFile Path to file containing expected output.
     * @param string $userRole User role used during test.
     * @return boolean True if CSV export returned expected data.
     * @throws PHPUnit_Framework_SkippedTestError If the test is skipped.
     * @throws PHPUnit_Framework_IncompleteTestError If the test is incomplete.
     * @throws PHPUnit_Framework_ExpectationFailedException If the test failed.
     */
    public function checkCsvExport($testName, $input, $expectedFile, $userRole)
    {
        $aggUnit = $input['aggregation_unit'];
        $datasetType = $input['dataset_type'];
        $fullTestName = $testName . $datasetType . '-' . $aggUnit . '-' . $userRole;

        if (in_array($testName, self::$skip)) {
            throw new \PHPUnit_Framework_SkippedTestError($fullTestName . ' intentionally skipped');
        }

        list($csvdata, $curldata) = self::post('/controllers/user_interface.php', null, $input);
        if (!empty(self::$timingOutputDir)) {
            $time_data = $fullTestName . "," . $curldata['total_time'] . "," . $curldata['starttransfer_time'] . "\n";
            $outputCSV = self::$timingOutputDir . "timings.csv";
            file_put_contents($outputCSV, $time_data, FILE_APPEND | LOCK_EX);
        }

        // This allows the "failed" tests of the public user to pass.  Need a
        // more robust way for public user not having access to pass.
        if (gettype($csvdata) === "array") {
            if ($csvdata['message'] == 'Session Expired') {
                throw new \PHPUnit_Framework_IncompleteTestError($fullTestName . ' user session expired...');
            }
            $csvdata = json_encode($csvdata, JSON_PRETTY_PRINT) . "\n";
        }

        $csvdata = preg_replace(self::$replaceRegex, self::$replacements, $csvdata);

        if (getenv('REG_TEST_FORCE_GENERATION') !== '1') {
            $expected = file_get_contents($expectedFile);
            $expected = preg_replace(self::$replaceRegex, self::$replacements, $expected);

            if ($expected === $csvdata) {
                return true;
            } else {
                try {
                    $decodedCsv = json_decode($csvdata);
                    if ($decodedCsv !== false) {
                        $actualJson = json_encode($decodedCsv, JSON_PRETTY_PRINT);
                        $expectedJson = json_encode(json_decode($expected), JSON_PRETTY_PRINT);

                        if (trim($expectedJson) === trim($actualJson)) {
                            return true;
                        }
                    }
                } catch (\Exception $e) {
                    // go ahead and ignore as this is just for json data and will fail w/ actual csv data.
                }
            }

            $this->messages[] = sprintf(
                "%s:\nRaw Expected:\n%s\n\nRaw Actual:\n%s\n",
                $fullTestName,
                $expected,
                $csvdata
            );

            $failures = $this->compareCsvData($expected, $csvdata);

            if (empty($failures)) {
                // This happens because of floating point math.
                $this->messages[] = "$fullTestName IS ONLY ==";
                return true;
            }

            throw new \PHPUnit_Framework_ExpectationFailedException(
                sprintf(
                    "%d assertions failed:\n\t%s",
                    count($failures),
                    implode("\n\t", $failures)
                )
            );
        }

        // Artifact generation mode below (REG_TEST_FORCE_GENERATION=1).

        // Using host name in output directory for federation.
        $endpoint = parse_url(self::getSiteUrl());
        $outputDir = implode(
            DIRECTORY_SEPARATOR,
            [self::getBaseDir(), 'expected', $endpoint['host'], $testName]
        );

        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $outputDir = realpath($outputDir);
        $referenceFile = $outputDir . '/' . $datasetType . '-' . $aggUnit . '-reference.csv';

        if (file_exists($referenceFile)) {
            $reference = file_get_contents($referenceFile);
            if ($reference === $csvdata) {
                return true;
            }
        }

        $outputFile = $outputDir . '/' . $datasetType . '-' . $aggUnit . '-' . ($userRole == 'public' ? 'reference' : $userRole) . '.csv';
        file_put_contents($outputFile, $csvdata);
        throw new \PHPUnit_Framework_SkippedTestError('Created Expected output for ' . $fullTestName);
    }

    /**
     * Compare CSV data.
     *
     * @param string $expected Expected CSV data.
     * @param string $provided Provided CSV data.
     * @return array Failures.
     */
    private function compareCsvData($expected, $provided)
    {
        $expectedCSV = self::getResultAsCSV($expected);
        $providedCSV = self::getResultAsCSV($provided);

        $expectedRowCount = count($expectedCSV);
        $providedRowCount = count($providedCSV);

        // If the row counts are different return a failure immediately.
        if ($expectedRowCount !== $providedRowCount) {
            return [
                sprintf(
                    'Row count != expected: %d {actual} %d',
                    $expectedRowCount,
                    $providedRowCount
                )
            ];
        }

        $failures = [];
        $expectedHeader = $expectedCSV[0];
        $providedHeader = $providedCSV[0];
        $useAssoc = false;

        if (count(array_diff_assoc($expectedHeader, $providedHeader)) > 0) {
            sort($expectedHeader);
            sort($providedHeader);

            if ($expectedHeader !== $providedHeader) {
                $failures[] = 'CSV headers differ';
            }

            $useAssoc = true;
            $this->messages[] = 'Column order mismatch';
            $expectedCSV = self::getAssocCSV($expectedCSV);
            $providedCSV = self::getAssocCSV($providedCSV);
        }

        for ($i = 1; $i < $expectedRowCount; $i++) {
            $expectedRow = $expectedCSV[$i];
            $providedRow = $providedCSV[$i];

            if (count($expectedRow) !== count($providedRow)) {
                $failures[] = 'Column counts differ';
            }

            foreach ($expectedHeader as $key => $value) {
                $index = $useAssoc ? $value : $key;
                $columnName = $useAssoc ? $index : $expectedHeader[$index];

                if (!array_key_exists($index, $providedRow)) {
                    $failures[] = sprintf(
                        "Expected key not found in provided row %d. \n\t\tExpected Key:  [%s]\n\t\tProvided Keys: [%s]",
                        $i,
                        $index,
                        implode(', ', array_keys($providedRow))
                    );
                } else {
                    $expectedRowValue = $expectedRow[$index];
                    $providedRowValue = $providedRow[$index];
                    $rowMessage = sprintf(
                        "Values do not match for column %s, row %d. \nExpected: [%s] \nActual:   [%s]",
                        $columnName,
                        $i,
                        $expectedRowValue,
                        $providedRowValue
                    );

                    if ($providedRowValue !== $expectedRowValue && is_numeric($expectedRowValue)) {
                        $errorFormula = "| {expected} $expectedRowValue - {actual} $providedRowValue |";

                        if (abs($expectedRowValue) > self::$almostZero) {
                            $relativeError = abs($expectedRowValue - $providedRowValue) / $expectedRowValue;
                            $errorFormula .= " / $expectedRowValue";
                        } else {
                            $relativeError = abs($expectedRowValue - $providedRowValue);
                        }

                        if ($relativeError > self::$delta) {
                            $failures[] = sprintf(
                                '( %s ) => %f > %f',
                                $errorFormula,
                                $relativeError,
                                self::$delta
                            );
                        } else {
                            $this->messages[] = sprintf(
                                'column: %s, row: %d ( %s ) => %f < %f',
                                $columnName,
                                $i,
                                $errorFormula,
                                $relativeError,
                                self::$delta
                            );
                        }
                    } elseif ($expectedRowValue !== $providedRowValue) {
                        $failures[] = $rowMessage;
                    }
                }
            }
        }

        return $failures;
    }

    /**
     * Convert string of CSV data to array of arrays.
     *
     * @param string $raw Raw data from CSV file.
     * @return array Parsed CSV data as array of arrays.
     */
    private static function getResultAsCSV($raw)
    {
        $datasRegEx = '/(?<=---------\n)([\s\S]*)(?=\n---------)/';
        $matches = [];
        preg_match($datasRegEx, $raw, $matches, PREG_OFFSET_CAPTURE, 0);
        if (count($matches) === 0) {
            return [];
        }

        // Parse the rows.
        $csv = str_getcsv($matches[0][0], "\n");
        foreach ($csv as &$row) {
            // Parse the items in rows.
            $row = str_getcsv($row);
        }

        return $csv;
    }

    /**
     * Convert a array of numeric arrays into an array of associative arrays
     * where the keys are the values from the first array (CSV header).
     *
     * @param array $csv Array of numeric arrays.
     * @return array Array of same arrays, but with keys from the header row.
     */
    private static function getAssocCSV($csv)
    {
        array_walk(
            $csv,
            function (&$a) use ($csv) {
                $a = array_combine($csv[0], $a);
            }
        );
        return $csv;
    }
}
