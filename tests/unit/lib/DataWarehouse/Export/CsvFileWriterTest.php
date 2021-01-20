<?php

namespace UnitTesting\DataWarehouse\Export;

use CCR\Log;
use DataWarehouse\Export\FileWriter\CsvFileWriter;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use TestHarness\TestFiles;

/**
 * Test data warehouse export CSV file writer.
 */
class CsvFileWriterTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test artifacts path.
     * @var string
     */
    const TEST_GROUP = 'unit/data_warehouse/export/file_writer/csv';

    /**
     * @var LoggerInterface
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
     * Test writing records to file.
     *
     * @dataProvider writeRecordsProvider
     */
    public function testWriteRecords(array $records, $fileContents)
    {
        $file = tempnam(sys_get_temp_dir(), 'dw-export-test-');
        $fileWriter = new CsvFileWriter($file, self::$logger);
        foreach ($records as $record) {
            $fileWriter->writeRecord($record);
        }
        $fileWriter->close();
        $this->assertEquals($fileContents, file_get_contents($file), 'File contents');
        @unlink($file);
    }

    public function writeRecordsProvider()
    {
        return $this->getTestFiles()->loadJsonFile(self::TEST_GROUP, 'write_records');
    }
}
