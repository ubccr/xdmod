<?php

namespace UnitTesting\DataWarehouse\Export;

use CCR\Logging;
use DataWarehouse\Export\FileWriter\JsonFileWriter;
use PHPUnit_Framework_TestCase;
use Log;
use Psr\Log\LoggerInterface;
use TestHarness\TestFiles;

/**
 * Test data warehouse export JSON file writer.
 */
class JsonFileWriterTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test artifacts path.
     * @var string
     */
    const TEST_GROUP = 'unit/data_warehouse/export/file_writer/json';

    /**
     * @var LoggerInterface
     */
    private static $logger;

    /**
     * Create logger.
     */
    public static function setUpBeforeClass()
    {
        self::$logger = Logging::singleton('null');
    }

    /**
     * @return \TestHarness\TestFiles
     */
    public function getTestFiles()
    {
        if (!isset($this->testFiles)) {
            $this->testFiles = new TestFiles(__DIR__ . '/../../../..');
        }
        return $this->testFiles;
    }

    /**
     * Test writing records to file.
     *
     * @dataProvider writeRecordsProvider
     */
    public function testWriteRecords(array $records, $fileContents)
    {
        $file = tempnam(sys_get_temp_dir(), 'dw-export-test-');
        $fileWriter = new JsonFileWriter($file, self::$logger);
        foreach ($records as $record) {
            $fileWriter->writeRecord($record);
        }
        $fileWriter->close();
        $this->assertJsonStringEqualsJsonString($fileContents, file_get_contents($file), 'File contents');
        @unlink($file);
    }

    public function writeRecordsProvider()
    {
        return $this->getTestFiles()->loadJsonFile(self::TEST_GROUP, 'write_records');
    }
}
