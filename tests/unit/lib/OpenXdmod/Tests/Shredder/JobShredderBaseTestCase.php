<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Tests\Shredder;

use CCR\DB\NullDB;
use Exception;
use Log;
use PHPUnit_Framework_TestCase;
use TestHarness\TestFiles;

/**
 * Base class for job shredder test classes.
 */
abstract class JobShredderBaseTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var \TestHarness\TestFiles
     */
    private $testFiles;

    /**
     * @var \CCR\DB\NullDB
     */
    protected $db;

    /**
     * @var \Log
     */
    protected $logger;

    /**
     * Create a null database and logger.
     */
    public function setUp()
    {
        $this->db = new NullDB();
        $this->logger = Log::singleton('null');
    }

    /**
     * @return \TestHarness\TestFiles
     */
    protected function getTestFiles()
    {
        if (!isset($this->testFiles)) {
            $this->testFiles = new TestFiles(__DIR__ . '/../../../../..');
        }
        return $this->testFiles;
    }

    /**
     * Load log file test cases.
     *
     * The input must be in a line oriented file ending with ".log" and the
     * output must be in a JSON file.  Each line in the input file will be
     * added to an array with the corresponding element from the output file.
     * If the output file top level element is an object then keys from that
     * object will be used as keys for the test cases.
     *
     * @return array[]
     */
    protected function getLogFileTestCases($name)
    {
        if (!defined('static::TEST_GROUP')) {
            throw new Exception(
                sprintf(
                    'Class %s must define class constant TEST_GROUP',
                    get_class($this)
                )
            );
        }

        $files = $this->getTestFiles();

        // Load input file into an array.
        $inputFile = $files->getFile(static::TEST_GROUP, $name, 'input', '.log');
        $inputData = file($inputFile, FILE_IGNORE_NEW_LINES);

        // Output file must contain a JSON array.
        $outputData = $files->loadJsonFile(static::TEST_GROUP, $name, 'output');

        if (count($inputData) !== count($outputData)) {
            throw new Exception(
                sprintf(
                    'Input count (%d) does not match output count (%d)',
                    count($inputData),
                    count($outputData)
                )
            );
        }

        // Relies on PHP json_decode retaining the order of keys when the
        // output is a JSON object and not an array.
        $testCases = [];
        $i = 0;
        while ((list($testName, $outputTestCase) = each($outputData))) {
            $testCases[$testName] = [$inputData[$i], $outputTestCase];
            ++$i;
        }

        return $testCases;
    }
}
