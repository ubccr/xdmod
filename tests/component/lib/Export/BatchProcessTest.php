<?php

namespace ComponentTests\Export;

use CCR\DB;
use ComponentTests\BaseTest;
use DataWarehouse\Export\BatchProcessor;
use DataWarehouse\Export\FileManager;
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
     * Database handle.
     * @var \CCR\DB\iDatabase
     */
    private static $dbh;

    /**
     * @var \DataWarehouse\Export\QueryHandler
     */
    private static $queryHandler;

    /**
     * @var \DataWarehouse\Export\FileManager
     */
    private static $fileManager;

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
     * Primary key of a submitted batch export request.
     * @var int
     */
    private static $submittedRequestId;

    /**
     * Primary key of an expiring batch export request.
     * @var int
     */
    private static $expiringRequestId;

    /**
     * Create test objects and data.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setupBeforeClass();
        self::$queryHandler = new QueryHandler();
        self::$exportDirectory = xd_utilities\getConfiguration(
            'data_warehouse_export',
            'export_directory'
        );
        self::$dbh = DB::factory('database');

        list($row) = self::$dbh->query('SELECT COUNT(*) AS count FROM batch_export_requests');
        if ($row['count'] > 0) {
            error_log(sprintf('Expected 0 rows in moddb.batch_export_requests, found %d', $row['count']));
        }

        $exportFileCount = count(self::getExportFiles());
        if ($exportFileCount > 0) {
            error_log(sprintf('Expected 0 export files in %s found %d', self::$exportDirectory, $exportFileCount));
        }

        // Find a valid user ID.
        list($user) = self::$dbh->query("SELECT id FROM Users WHERE username = 'normaluser'");

        // Create records and set one to be expiring.
        self::$submittedRequestId = self::$queryHandler->createRequestRecord($user['id'], 'Jobs', '2019-01-01', '2019-01-31', 'CSV');
        self::$expiringRequestId = self::$queryHandler->createRequestRecord($user['id'], 'Jobs', '2019-01-01', '2019-01-31', 'JSON');
        self::$queryHandler->submittedToAvailable(self::$expiringRequestId);
        self::$dbh->execute(
            'UPDATE batch_export_requests SET export_expires_datetime = :expiration_date WHERE id = :id',
            [
                'expiration_date' => date('Y-m-d H:i:s', time() - 1),
                'id' => self::$expiringRequestId
            ]
        );
        self::$fileManager = new FileManager();
        file_put_contents(self::$fileManager->getExportDataFilePath(self::$expiringRequestId), 'TEST DATA');
    }

    /**
     * Remove test data from database and generated files.
     */
    public static function tearDownAfterClass(): void
    {
        // Delete any requests that weren't already deleted.
        self::$dbh->execute('DELETE FROM batch_export_requests');

        // Delete files.
        foreach (self::getExportFiles() as $file) {
            @unlink($file['path']);
        }
    }

    /**
     * Get emails in root mailbox.
     *
     * @return array[]
     */
    private static function getEmails()
    {
        while (trim(`postqueue -p | tail -n1 | awk '{print $5}'`) != '') {
            usleep(10000);
        }

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
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                return array_merge(stat($path), ['path' => $path]);
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
        // Capture state before processing requests.
        $emails = self::getEmails();
        $files = self::getExportFiles();
        $submittedRequests = self::$queryHandler->listSubmittedRecords();
        $expiringRequests = self::$queryHandler->listExpiringRecords();

        $batchProcessor = new BatchProcessor();
        $batchProcessor->setDryRun(true);
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
    }

    /**
     * Test processing batch export requests.
     */
    public function testRequestProcessing()
    {
        // Capture state before processing requests.
        $emailsBefore = self::getEmails();
        $filesBefore = self::getExportFiles();
        $submittedRequestsBefore = self::$queryHandler->listSubmittedRecords();
        $expiringRequestsBefore = self::$queryHandler->listExpiringRecords();

        $batchProcessor = new BatchProcessor();
        $batchProcessor->processRequests();

        // Capture state after processing requests.
        $emailsAfter = self::getEmails();
        $filesAfter = self::getExportFiles();
        $submittedRequestsAfter = self::$queryHandler->listSubmittedRecords();
        $expiringRequestsAfter = self::$queryHandler->listExpiringRecords();

        $this->assertEquals(
            count($emailsBefore) + 1,
            count($emailsAfter),
            '1 new email'
        );
        $this->assertEquals(
            count($filesBefore),
            count($filesAfter),
            'Same number of export files'
        );
        $filePathsAfter = array_map(
            function ($file) {
                return $file['path'];
            },
            $filesAfter
        );
        $this->assertContains(
            self::$fileManager->getExportDataFilePath(self::$submittedRequestId),
            $filePathsAfter,
            'Submitted request now has a data file'
        );
        $this->assertNotContains(
            self::$fileManager->getExportDataFilePath(self::$expiringRequestId),
            $filePathsAfter,
            'Expiring request no longer has a data file'
        );
        $this->assertEquals(
            count($submittedRequestsBefore) - 1,
            count($submittedRequestsAfter),
            'One less submitted request'
        );

        $submittedRequestsIdsBefore = array_map(
            function ($request) {
                return $request['id'];
            },
            $submittedRequestsBefore
        );
        $this->assertContains(
            self::$submittedRequestId,
            $submittedRequestsIdsBefore,
            'Submitted request ID listed before processing'
        );

        $submittedRequestsIdsAfter = array_map(
            function ($request) {
                return $request['id'];
            },
            $submittedRequestsAfter
        );
        $this->assertNotContains(
            self::$submittedRequestId,
            $submittedRequestsIdsAfter,
            'Submitted request ID not listed after processing'
        );

        $this->assertEquals(
            count($expiringRequestsBefore) - 1,
            count($expiringRequestsAfter),
            'One less expiring request'
        );

        $expiringRequestsIdsBefore = array_map(
            function ($request) {
                return $request['id'];
            },
            $expiringRequestsBefore
        );
        $this->assertContains(
            self::$expiringRequestId,
            $expiringRequestsIdsBefore,
            'Expiring request ID listed before processing'
        );

        $expiringRequestsIdsAfter = array_map(
            function ($request) {
                return $request['id'];
            },
            $expiringRequestsAfter
        );
        $this->assertNotContains(
            self::$expiringRequestId,
            $expiringRequestsIdsAfter,
            'Expiring request ID not listed after processing'
        );
    }
}
