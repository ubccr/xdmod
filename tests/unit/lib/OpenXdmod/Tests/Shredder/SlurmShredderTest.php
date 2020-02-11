<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Tests\Shredder;

use CCR\DB\NullDB;
use OpenXdmod\Shredder;
use Log;
use TestHarness\TestFiles;

/**
 * PBS shredder test class.
 */
class SlurmShredderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_GROUP = 'unit/shredder/slurm';

    private $testFiles;

    protected $db;

    protected $logger;

    public function setUp()
    {
        $this->db = new NullDB();
        $this->logger = Log::singleton('null');
    }

    /**
     * @return \TestHarness\TestFiles
     */
    public function getTestFiles()
    {
        if (!isset($this->testFiles)) {
            $this->testFiles = new TestFiles(__DIR__ . '/../../../../..');
        }
        return $this->testFiles;
    }

    public function testShredderConstructor()
    {
        $shredder = Shredder::factory('slurm', $this->db);
        $this->assertInstanceOf('\OpenXdmod\Shredder\Slurm', $shredder);
    }

    /**
     * @dataProvider accountingLogProvider
     */
    public function testShredderParsing($line, $row)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Slurm')
            ->setConstructorArgs(array($this->db))
            ->setMethods(array('insertRow', 'getResourceConfig'))
            ->getMock();

        $shredder
            ->expects($this->once())
            ->method('insertRow')
            ->with($row);

        $shredder
            ->method('getResourceConfig')
            ->willReturn(array());

        $shredder->setLogger($this->logger);

        $shredder->setResource('testresource');

        $shredder->shredLine($line);
    }

    /**
     * @dataProvider accountingLogWithJobArraysProvider
     */
    public function testJobArrayParsing($line, array $arrayIds)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Slurm')
            ->setConstructorArgs(array($this->db))
            ->setMethods(array('insertRow'))
            ->getMock();

        $callCount = 0;

        $shredder
            ->expects($this->exactly(count($arrayIds)))
            ->method('insertRow')
            ->with($this->callback(
                function ($subject) use (&$callCount, $arrayIds) {

                    // There is a bug in the PHPUnit version being used
                    // that calls the callback more that it should.
                    // See https://github.com/sebastianbergmann/phpunit-mock-objects/pull/311
                    if ($callCount >= count($arrayIds)) {
                        return true;
                    }

                    return $arrayIds[$callCount++] == $subject['job_array_index'];
                }
            ));

        $shredder->setLogger($this->logger);

        $shredder->shredLine($line);
    }

    /**
     * @dataProvider accountingLogWithGpuGresProvider
     */
    public function testJobGpuGresParsing($line, $gpuCount)
    {
        $shredder = $this
            ->getMockBuilder('\OpenXdmod\Shredder\Slurm')
            ->setConstructorArgs([$this->db])
            ->setMethods(['insertRow'])
            ->getMock();
        $shredder
            ->expects($this->once())
            ->method('insertRow')
            ->with(new \PHPUnit_Framework_Constraint_ArraySubset(['ngpus' => $gpuCount]));
        $shredder->setLogger($this->logger);
        $shredder->shredLine($line);
    }

    /**
     * Load test data.
     *
     * The input must be in a line oriented file ending with ".log" and the
     * output must be in a JSON file with a top level element that is an array.
     * Each line in the input file and each element of the array will be
     * returned together.
     *
     * @return array[]
     */
    private function getTestCases($name)
    {
        $files = $this->getTestFiles();

        // Load input file into an array.
        $inputFile = $files->getFile(self::TEST_GROUP, $name, 'input', '.log');
        $inputData = file($inputFile, FILE_IGNORE_NEW_LINES);

        // Output file must contain a JSON array.
        $outputData = $files->loadJsonFile(self::TEST_GROUP, $name, 'output');

        // Using array_map to zip input and output.
        return array_map(null, $inputData, $outputData);
    }

    public function accountingLogProvider()
    {
        return $this->getTestCases('accounting-logs');
    }

    public function accountingLogWithJobArraysProvider()
    {
        return $this->getTestCases('accounting-logs-with-job-arrays');
    }

    public function accountingLogWithGpuGresProvider()
    {
        return $this->getTestCases('accounting-logs-with-gpu-gres');
    }
}
