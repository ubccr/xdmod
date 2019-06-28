<?php
/**
 * Process batch export requests.
 */

namespace DataWarehouse\Export;

use CCR\DB;
use CCR\Loggable;
use CCR\MailWrapper;
use Exception;
use XDUser;
use ZipArchive;
use xd_utilities;

class BatchProcessor extends Loggable
{
    /**
     * Database handle for moddb.
     * @var \CCR\DB\iDatabase
     */
    private $dbh;

    /**
     * Flag to indicate that any processing is a dry run.
     * @var boolean
     */
    private $dryRun = false;

    /**
     * Data warehouse export query handler.
     * @var \DataWarehouse\Export\QueryHandler
     */
    private $queryHandler;

    /**
     * Construct a new batch processor.
     */
    public function __construct()
    {
        $this->dbh = DB::factory('database');
        $this->queryHandler = new QueryHandler();
    }

    /**
     * Set whether or not processing the requests will be a dry run.
     *
     * If this is a dry run then no export files will be generated, no emails
     * will be sent and no changes will be made to export requests in the
     * database.
     *
     * @param boolean $dryRun
     */
    public function setDryRun($dryRun)
    {
        $this->dryRun = $dryRun;
    }

    /**
     * Process all requests.
     */
    public function processRequests()
    {
        $this->processSubmittedRequests();
        $this->processExpiringRequests();
    }

    /**
     * Process requests in the "Submitted" state.
     *
     * Generate the data export and update the request.
     */
    private function processSubmittedRequests()
    {
        $this->logger->info('Processing submitted requests');
        foreach ($this->queryHandler->listSubmittedRecords() as $request) {
            $this->processSubmittedRequest($request);
        }
    }

    /**
     */
    private function processSubmittedRequest(array $request)
    {
        $user = XDUser::getUserByID($request['user_id']);

        if ($user === null) {
            $this->logger->err(
                sprintf(
                    'No user found for id = %d, request_id = %d',
                    $request['user_id'],
                    $request['id']
                )
            );
            return;
        }

        try {
            $this->dbh->beginTransaction();

            $this->queryHandler->submittedToAvailable($request['id']);
            // TODO: update request array with expiration date
            //$request = $this->queryHandler->get
            $this->dbh->commit();
            $this->sendExportSuccessEmail($user, $request);
        } catch (Exception $e) {
            $this->dbh->rollback();
            $this->sendExportFailureEmail(
                $user,
                $request,
                $e->getMessage(),
                $e->getPrevious()
            );
        }
    }

    /**
     * Process requests in the "Available" state.
     *
     * Check if the request has expired and, if so, remove expired data and
     * update the request.
     */
    private function processExpiringdRequests()
    {
        $this->logger->info('Processing expired requests');
        foreach ($this->queryHandler->listExpiringRecords() as $request) {
            $this->processExpiringRequest($request);
        }
    }

    /**
     */
    private function processExpiringRequest(array $request)
    {
        try {

        } catch (Exception $e) {
        }
    }

    /**
     */
    private function generateExportFile()
    {
        if ($this->dryRun) {
            $this->logger->notice('dry run: Not generating export file');
            return;
        }

        $this->logger->info('Generating export file');
        // TODO
    }

    /**
     */
    private function createZipFile($dataFile, $zipFile)
    {
        if ($this->dryRun) {
            $this->logger->notice('dry run: Not creating zip file');
            return;
        }

        $this->logger->info('Creating zip file');

        $zip = new ZipArchive();
        $zipOpenCode = $zip->open($zipFile, ZipArchive::CREATE);

        if ($zipOpenCode !== true) {
            $this->logAndThrowException(
                sprintf(
                    'Failed to create zip file "%s", error code "%s"',
                    $zipFile,
                    $zipOpenCode
                )
            );
        }

        if ($zip->addFile($dataFile, basename($dataFile)) === false) {
            $this->logAndThrowException(
                sprintf(
                    'Failed to add file "%s" to zip file "%s"',
                    $dataFile,
                    $zipFile
                )
            );
        }

        $zip->close();
    }

    /**
     */
    private function removeExportFile()
    {
        if ($this->dryRun) {
            $this->logger->notice('dry run: Not removing export file');
            return;
        }

        $this->logger->info('Removing export file');
        // TODO
    }

    /**
     * Send emails indicating a successful export.
     *
     * @param XDUser $user The user that requested the export.
     * @param array $request The batch request data.
     */
    private function sendExportSuccessEmail(XDUser $user, array $request)
    {
        if ($this->dryRun) {
            $this->logger->notice('dry run: Not sending success email');
            return;
        }

        $this->logger->info('Sending success email');

        MailWrapper::sendTemplate(
            'batch_export_success',
            [
                'subject' => 'Batch export ready for download',
                'toAddress' => $user->getEmailAddress(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'current_date' => date('Y-m-d'),
                'expiration_date' => $request[''], // TODO
                'download_url' => '', // TODO
                'maintainer_signature' => MailWrapper::getMaintainerSignature()
            ]
        );
    }

    /**
     */
    private function sendExportFailureEmail(
        XDUser $user,
        array $request,
        $failureReason,
        Exception $e
    ) {
        if ($this->dryRun) {
            $this->logger->notice('dry run: Not sending failure email');
            return;
        }

        $this->logger->info('Sending failure email');

        MailWrapper::sendTemplate(
            'batch_export_failure_admin_notice',
            [
                'subject' => 'Batch export failed',
                'toAddress' => xd_utilities\getConfiguration('general', 'tech_support_recipient'),
                'user_email' => $user->getEmailAddress(),
                'user_first_name' => $user->getFirstName(),
                'user_last_name' => $user->getLastName(),
                'current_date' => date('Y-m-d'),
                'failure_reason' => $failureReason,
                'failure_exception' => $e->getMessage(),
                'failure_stack_trace' => $e->getTraceAsString()
            ]
        );

        MailWrapper::sendTemplate(
            'batch_export_failure',
            [
                'subject' => 'Batch export failed',
                'toAddress' => $user->getEmailAddress(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'current_date' => date('Y-m-d'),
                'failure_reason' => $failureReason,
                'maintainer_signature' => MailWrapper::getMaintainerSignature()
            ]
        );
    }
}
