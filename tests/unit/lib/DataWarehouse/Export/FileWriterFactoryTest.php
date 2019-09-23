<?php

namespace UnitTesting\DataWarehouse\Export;

use DataWarehouse\Export\FileWriter\FileWriterFactory;
use PHPUnit_Framework_TestCase;
use TestHarness\TestFiles;

/**
 * Test data warehouse export file.
 */
class FileWriterTest extends PHPUnit_Framework_TestCase
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
     * @var \TestHarness\TestFiles
     */
    private $testFiles;

    /**
     * Create file writer factory.
     */
    public static function setUpBeforeClass()
    {
        self::$fileWriterFactory = new FileWriterFactory();
    }

    /**
     * @return \TestHarness\TestFiles
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
     *
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /Unsupported format/
     */
    public function testFileWriterFactoryException()
    {
        self::$fileWriterFactory->createFileWriter('foo', '/dev/null');
    }

    public function fileWriterCreationProvider()
    {
        return $this->getTestFiles()->loadJsonFile(self::TEST_GROUP, 'create_file_writer');
    }
}
