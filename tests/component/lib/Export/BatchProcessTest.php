<?php

namespace ComponentTests\Export;

use ComponentTests\BaseTest;
use DataWarehouse\Export\BatchProcessor;
use DataWarehouse\Export\QueryHandler;
use xd_utilities;

/**
 * Test batch processing of export requests.
 *
 * @coversDefaultClass \DataWarehouse\Export\BatchProcessor
 */
class BatchProcessTest extends BaseTest
{
    /**
     * Test artifacts path.
     */
    const TEST_GROUP = 'component/export/batch_process';

    /**
     * @var \DataWarehouse\Export\QueryHandler
     */
    private static $queryHandler;

    /**
     * The file containing email data.
     * @var string
     */
    private static $mailSpool = '/var/mail/root';

    /**
     * The directory containing batch export zip files.
     * @var string.
     */
    private static $exportDirectory;

    /**
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$queryHandler = new QueryHandler();
        self::$exportDirectory = xd_utilities\getConfiguration(
            'data_warehouse_export',
            'export_directory'
        );
    }

    /**
     */
    private function getEmails()
    {
        // TODO
        return [];
    }

    /**
     */
    private function getExportFiles()
    {
        // TODO
        return [];
    }

    /**
     * Test the batch processor dry run option.
     */
    public function testDryRun()
    {
        $batchProcessor = new BatchProcessor();
        $batchProcessor->setDryRun(true);

        $emails = $this->getEmails();
        $files = $this->getExportFiles();
        $submittedRequests = $this->queryHandler->listSubmittedRecords();
        $expiringRequests = $this->queryHandler->listExpiringRecords();

        $batchProcessor->processRequests();

        $this->assertEquals($emails, $this->getEmails(), 'No new emails');
        $this->assertEquals($files, $this->getFiles(), 'No new export files');
        $this->assertEquals(
            $submittedRequests,
            $this->queryHandler->listSubmittedRecords(),
            'No submitted requests changed'
        );
        $this->assertEquals(
            $expiringRequests,
            $this->queryHandler->listExpiringRecords(),
            'No expiring requests changed'
        );

        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test processing batch export requests.
     */
    public function testRequestProcessing()
    {
        $batchProcessor = new BatchProcessor();
        $batchProcessor->processRequests();
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
