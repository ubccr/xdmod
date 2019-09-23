<?php

namespace UnitTesting\DataWarehouse\Export;

use DataWarehouse\Export\FileWriter\NullFileWriter;
use PHPUnit_Framework_TestCase;
use Log;
use TestHarness\TestFiles;

/**
 * Test data warehouse export null file writer.
 */
class NullFileWriterTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test artifacts path.
     * @var string
     */
    const TEST_GROUP = 'unit/data_warehouse/export/file_writer/null';

    /**
     * @var \Log
     */
    private static $logger;

    /**
     * Create logger.
     */
    public static function setUpBeforeClass()
    {
        self::$logger = Log::singleton('null');
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
     * Test creating each type of file write using the factory class.
     *
     * @dataProvider writeRecordsProvider
     */
    public function testWriteRecords(array $records)
    {
        $file = tempnam(sys_get_temp_dir(), 'dw-export-test-');
        $fileWriter = new NullFileWriter($file, self::$logger);
        foreach ($records as $record) {
            $fileWriter->writeRecord($record);
        }
        $fileWriter->close();
        $this->assertEquals('', file_get_contents($file), 'File contents');
        @unlink($file);
    }

    public function writeRecordsProvider()
    {
        return $this->getTestFiles()->loadJsonFile(self::TEST_GROUP, 'write_records');
    }
}
