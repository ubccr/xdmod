<?php

namespace UnitTests\DataWarehouse\Export;

use CCR\Log;
use DataWarehouse\Export\FileWriter\NullFileWriter;
use \PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use IntegrationTests\TestHarness\TestFiles;

/**
 * Test data warehouse export null file writer.
 */
class NullFileWriterTest extends TestCase
{
    /**
     * Test artifacts path.
     * @var string
     */
    const TEST_GROUP = 'unit/data_warehouse/export/file_writer/null';

    /**
     * @var LoggerInterface
     */
    private static $logger;
    protected TestFiles $testFiles;

    /**
     * Create logger.
     */
    public static function setupBeforeClass(): void
    {
        self::$logger = Log::singleton('null');
    }

    /**
     * @return TestFiles
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
