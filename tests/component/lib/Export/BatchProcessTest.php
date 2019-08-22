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
    private static $mailbox = '/var/mail/root';

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
     * Get emails in root mailbox.
     *
     * @return array[]
     */
    private static function getEmails()
    {
        if (!file_exists(self::$mailbox)) {
            return [];
        }

        $emails = [];
        $currentEmail = [];
        $body = '';
        $lines = file(self::$mailbox);
        while (count($lines) > 0) {
            $line = array_shift($lines);
            // Check for a new message.
            if (substr($line, 0, 5) === 'From ') {
                // Store previous email.
                if (!empty($currentEmail)) {
                    // Remove trailing newline.
                    $body = substr($body, 0, -1);
                    // Undo "From " escaping.
                    $body = preg_replace("/\n>([>]*From )/", "\n$1", $body);
                    $currentEmail['body'] = $body;
                    $emails[] = $currentEmail;
                }
                $currentEmail = [];
                $headers = [];
                // Parse headers.
                while (count($lines) > 0 && $lines[0] != "\n") {
                    $line = substr(array_shift($lines), 0, -1);
                    list($key, $value) = explode(': ', $line, 2);
                    while ($lines[0][0] === "\t") {
                        $value .= ' ' . substr(substr(array_shift($lines), 0, -1), 1);
                    }
                    $headers[$key] = $value;
                }
                $currentEmail['headers'] = $headers;
                $body = '';
                // Skip blank line before body.
                array_shift($lines);
            } else {
                $body .= $line;
            }
        }
        // Store last email.
        if (!empty($currentEmail)) {
            // Remove trailing newline.
            $body = substr($body, 0, -1);
            // Undo "From " escaping.
            $body = preg_replace("/\n>([>]*From )/", "\n$1", $body);
            $currentEmail['body'] = $body;
            $emails[] = $currentEmail;
        }
        return $emails;
    }

    /**
     * Get information about all the files in the export directory.
     *
     * @return array[]
     */
    private static function getExportFiles()
    {
        $dir = self::$exportDirectory;

        return array_map(
            function ($file) use ($dir) {
                return stat($dir . DIRECTORY_SEPARATOR . $file);
            },
            // Filter out files starting with ".".
            array_filter(
                scandir($dir),
                function ($file) {
                    return $file[0] !== '.';
                }
            )
        );
    }

    /**
     * Test the batch processor dry run option.
     */
    public function testDryRun()
    {
        $batchProcessor = new BatchProcessor();
        $batchProcessor->setDryRun(true);

        // Capture state before processing requests.
        $emails = self::getEmails();
        $files = self::getExportFiles();
        $submittedRequests = self::$queryHandler->listSubmittedRecords();
        $expiringRequests = self::$queryHandler->listExpiringRecords();

        $batchProcessor->processRequests();

        $this->assertEquals($emails, self::getEmails(), 'No new emails');
        $this->assertEquals($files, self::getExportFiles(), 'No new export files');
        $this->assertEquals(
            $submittedRequests,
            self::$queryHandler->listSubmittedRecords(),
            'No submitted requests changed'
        );
        $this->assertEquals(
            $expiringRequests,
            self::$queryHandler->listExpiringRecords(),
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
