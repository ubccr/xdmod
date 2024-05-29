<?php

namespace ComponentTests\Export;

use ComponentTests\BaseTest;
use DataWarehouse\Export\FileManager;
use ZipArchive;
use xd_utilities;

/**
 * Test batch processing of export requests.
 *
 * @coversDefaultClass \DataWarehouse\Export\FileManager
 */
class FileManagerTest extends BaseTest
{
    /**
     * Test artifacts path.
     */
    const TEST_GROUP = 'component/export/file_manager';

    /**
     * @var \DataWarehouse\Export\FileManager
     */
    private static $fileManager;

    /**
     * Export directory path.
     * @var string
     */
    private static $exportDir;

    /**
     * Create file manager and test data.
     */
    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();
        self::$fileManager = new FileManager();
        self::$exportDir = xd_utilities\getConfiguration(
            'data_warehouse_export',
            'export_directory'
        );
    }

    /**
     * Test getting export data file paths.
     *
     * @param int $id Export request ID.  Does not need to exist in the
     *   database.
     * @covers ::getExportDataFilePath
     * @dataProvider exportRequestIdProvider
     */
    public function testGetExportDataFilePath($id)
    {
        $path = self::$fileManager->getExportDataFilePath($id);
        $this->assertMatchesRegularExpression(
            sprintf('/^%s/', preg_quote(self::$exportDir, '/')),
            $path,
            'Path begins with export directory'
        );
        $this->assertMatchesRegularExpression(
            sprintf('/\b%s\b/', preg_quote($id, '/')),
            $path,
            'Path contains ID'
        );
    }

    /**
     * Test getting data file names.
     *
     * @covers ::getDataFileName
     * @dataProvider exportRequestProvider
     */
    public function testGetDataFileName(array $request)
    {
        $file = self::$fileManager->getDataFileName($request);
        $this->assertMatchesRegularExpression(
            sprintf('/\b%s\b/', preg_quote($request['realm'], '/')),
            $file,
            'File name contains realm name'
        );
        $this->assertMatchesRegularExpression(
            sprintf('/\b%s\b/', preg_quote($request['start_date'], '/')),
            $file,
            'File name contains start date'
        );
        $this->assertMatchesRegularExpression(
            sprintf('/\b%s\b/', preg_quote($request['end_date'], '/')),
            $file,
            'File name contains end date'
        );
        $this->assertMatchesRegularExpression(
            sprintf(
                '/\.%s$/',
                preg_quote(strtolower($request['export_file_format']), '/')
            ),
            $file,
            'File name ends with correct extension'
        );
    }

    /**
     * Test getting zip files names.
     *
     * @covers ::getZipFileName
     * @dataProvider exportRequestProvider
     */
    public function testGetZipFileName(array $request)
    {
        $file = self::$fileManager->getZipFileName($request);
        $this->assertMatchesRegularExpression(
            sprintf('/\b%s\b/', preg_quote($request['realm'], '/')),
            $file,
            'File name contains realm name'
        );
        $this->assertMatchesRegularExpression(
            sprintf('/\b%s\b/', preg_quote($request['start_date'], '/')),
            $file,
            'File name contains start date'
        );
        $this->assertMatchesRegularExpression(
            sprintf('/\b%s\b/', preg_quote($request['end_date'], '/')),
            $file,
            'File name contains end date'
        );
        $this->assertMatchesRegularExpression(
            '/\.zip$/',
            $file,
            'File name ends with correct extension'
        );
    }

    /**
     * Test writing data sets to files.
     *
     * @covers ::writeDataSetToFile
     * @dataProvider exportRequestProvider
     */
    public function testWriteDataSetToFile(array $request)
    {
        $dataSet = $this->getMockBuilder('\DataWarehouse\Data\BatchDataset')
            ->disableOriginalConstructor()
            ->onlyMethods(['getHeader', 'current', 'key', 'next', 'rewind', 'valid'])
            ->getMock();
        $dataSet->method('getHeader')->willReturn(['heading1', 'heading2', 'heading3']);
        $dataSet->method('current')
         ->will($this->onConsecutiveCalls([0, 1, 2], ['a', 'b', 'c'], false));
        $dataSet->method('key')
         ->will($this->onConsecutiveCalls(1, 2, false));
        $dataSet->method('valid')
         ->will($this->onConsecutiveCalls(true, true, false));
        $dataSet->method('next')->willReturn(null);
        $dataSet->method('rewind')->willReturn(null);


        $format = $request['export_file_format'];
        $file = self::$fileManager->writeDataSetToFile($dataSet, $format);

        $this->assertFileExists($file);

        $expectedFile = $this->getTestFiles()->getFile(
            self::TEST_GROUP,
            $request['id'],
            'output',
            '.' . strtolower($format)
        );

        if (strtolower($format) === 'json') {
            $this->assertJsonFileEqualsJsonFile($expectedFile, $file, 'File contents');
        } else {
            $this->assertFileEquals($expectedFile, $file, 'File contents');
        }

        @unlink($file);
    }

    /**
     * Test creating zip files.
     *
     * @covers ::createZipFile
     * @dataProvider exportRequestProvider
     */
    public function testCreateZipFile(array $request)
    {
        $dataFile = tempnam(sys_get_temp_dir(), 'file-manager-test-');
        $testData = 'TEST DATA';
        file_put_contents($dataFile, $testData);
        $zipFile = self::$fileManager->createZipFile($dataFile, $request);
        @unlink($dataFile);
        $this->assertFileExists($zipFile);
        $zip = new ZipArchive();
        $openCode = $zip->open($zipFile, ZipArchive::CHECKCONS);
        $this->assertTrue($openCode, 'Open zip file');
        $this->assertEquals(2, $zip->numFiles, 'File count in zip file');
        $dataFileName = self::$fileManager->getDataFileName($request);
        $this->assertEquals($dataFileName, $zip->getNameIndex(0), 'Data file name');
        $this->assertEquals('README.txt', $zip->getNameIndex(1), 'README file name');
        $fileData = $zip->getFromName($dataFileName);
        $this->assertEquals($testData, $fileData, 'Data file contents');
        $zip->close();
    }

    /**
     * Test removing export files.
     *
     * @covers ::removeExportFile
     * @dataProvider exportRequestProvider
     * @depends testCreateZipFile
     */
    public function testRemoveExportFile(array $request)
    {
        $file = self::$fileManager->getExportDataFilePath($request['id']);
        $this->assertFileExists($file);
        self::$fileManager->removeExportFile($request['id']);
        $this->assertFileDoesNotExist($file);
    }

    /**
     * Test removing export files for deleted requests.
     *
     * @covers ::removeDeletedRequests
     */
    public function testRemoveDeletedRequests()
    {
        // Start with 5 requests.
        $requestIds = [1, 2, 3, 4, 5];
        foreach ($requestIds as $id) {
            file_put_contents(self::$fileManager->getExportDataFilePath($id), 'TEST DATA');
        }
        // Delete 2.
        $deletedRequestIds = [3, 4];
        $availableRequestIds = [1, 2, 5];
        self::$fileManager->removeDeletedRequests($deletedRequestIds);
        foreach ($deletedRequestIds as $id) {
            $file = self::$fileManager->getExportDataFilePath($id);
            $this->assertFileDoesNotExist($file);
        }
        foreach ($availableRequestIds as $id) {
            $file = self::$fileManager->getExportDataFilePath($id);
            $this->assertFileExists($file);
            @unlink($file);
        }
    }

    public function exportRequestIdProvider()
    {
        return $this->getTestFiles()->loadJsonFile(self::TEST_GROUP, 'export-request-ids', 'input');
    }

    public function exportRequestProvider()
    {
        return $this->getTestFiles()->loadJsonFile(self::TEST_GROUP, 'export-requests', 'input');
    }
}
