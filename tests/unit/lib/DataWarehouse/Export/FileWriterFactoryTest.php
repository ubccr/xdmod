<?php

namespace UnitTests\DataWarehouse\Export;

use DataWarehouse\Export\FileWriter\FileWriterFactory;
use \PHPUnit\Framework\TestCase;
use IntegrationTests\TestHarness\TestFiles;

/**
 * Test data warehouse export file.
 */
class FileWriterFactoryTest extends TestCase
{
    /**
     * Test artifacts path.
     * @var string
     */
    const TEST_GROUP = 'unit/data_warehouse/export/file_writer/factory';

    /**
     * @var \DataWarehouse\Export\FileWriter\FileWriterFactory
     */
    private static $fileWriterFactory;

    /**
     * @var TestFiles
     */
    private $testFiles;

    /**
     * Create file writer factory.
     */
    public static function setupBeforeClass(): void
    {
        self::$fileWriterFactory = new FileWriterFactory();
    }

    /**
     * @return TestFiles
     */
    private function getTestFiles()
    {
        if (!isset($this->testFiles)) {
            $this->testFiles = new TestFiles(__DIR__ . '/../../../..');
        }
        return $this->testFiles;
    }

    /**
     * Test creating each type of file write using the factory class.
     *
     * @dataProvider fileWriterCreationProvider
     */
    public function testFileWriterCreation($format, $className)
    {
        $fileWriter = self::$fileWriterFactory->createFileWriter($format, '/dev/null');
        $this->assertInstanceOf('\DataWarehouse\Export\FileWriter\iFileWriter', $fileWriter, 'File writer implements iFileWriter interface');
        $this->assertInstanceOf($className, $fileWriter);
    }

    /**
     * Test creating an invalid file writer format.
     */
    public function testFileWriterFactoryException()
    {
        $this->expectExceptionMessageMatches("/Unsupported format/");
        $this->expectException(\Exception::class);
        self::$fileWriterFactory->createFileWriter('foo', '/dev/null');
    }

    public function fileWriterCreationProvider()
    {
        return $this->getTestFiles()->loadJsonFile(self::TEST_GROUP, 'create_file_writer');
    }
}
