<?php
/**
 * Process batch export requests.
 */

namespace DataWarehouse\Export;

use CCR\DB;
use CCR\Loggable;
use CCR\MailWrapper;
use DataWarehouse\Data\RawDataset;
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
     * Process a single export request.
     *
     * @param array $request The export request data.
     */
    private function processSubmittedRequest(array $request)
    {
        $this->logger->debug([
            'message' => 'Processing request',
            'batch_export_request.id' => $request['id']
        ]);

        $user = XDUser::getUserByID($request['user_id']);

        if ($user === null) {
            $this->logger->err([
                'message' => 'User not found',
                'Users.id' => $request['user_id'],
                'batch_export_request.id' => $request['id']
            ]);
            return;
        }

        try {
            $this->dbh->beginTransaction();
            $this->queryHandler->submittedToAvailable($request['id']);

            $dataSet = $this->getDataSet($request);

            $dataFile = $this->writeDataToFile($dataSet, $request['format']);

            $zipFile = 'TODO';
            $this->createZipFile($dataFile, $zipFile);

            // Query for same record to get expiration date.
            $request = $this->queryHandler->getRequestRecord($request['id']);
            $this->sendExportSuccessEmail($user, $request);
            $this->dbh->commit();
        } catch (Exception $e) {
            $this->dbh->rollback();
            $this->logger->err([
                'message' => 'Failed to export data: ' . $e->getMessage(),
                'stacktrace' => $e->getTraceAsString()
            ]);
            $failureReason = '...';
            $this->sendExportFailureEmail(
                $user,
                $request,
                $failureReason,
                $e
            );
        }
    }

    /**
     * Process requests in the "Available" state that should be expired.
     *
     * Check if the request has expired and, if so, remove expired data and
     * update the request.
     */
    private function processExpiringRequests()
    {
        $this->logger->info('Processing expired requests');
        foreach ($this->queryHandler->listExpiringRecords() as $request) {
            $this->processExpiringRequest($request);
        }
    }

    /**
     * Process a single export request that is expiring.
     *
     * @param array $request The export request data.
     */
    private function processExpiringRequest(array $request)
    {
        $this->logger->debug([
            'message' => 'Expiring request',
            'batch_export_request.id' => $request['id']
        ]);

        try {
            $this->dbh->beginTransaction();
            // TODO
            // Delete file
            $this->queryHandler->availableToExpired($request['id']);
            $this->dbh->commit();
        } catch (Exception $e) {
            $this->dbh->rollback();
            $this->logger->err([
                'message' => 'Failed to expire record: ' . $e->getMessage(),
                'stacktrace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get the data set for the given request.
     *
     * @param array $request
     * @param \XDUser $user
     * @return \DataWarehouse\Data\RawDataset;
     * @throws \Exception
     */
    private function getDataSet(array $request, XDUser $user)
    {
        $this->logger->info([
            'message' => 'Querying data',
            'Users.id' => $user->getUserID(),
            'user_email' => $user->getEmailAddress(),
            'user_first_name' => $user->getFirstName(),
            'user_last_name' => $user->getLastName(),
            'realm' => $request['realm'],
            'start_date' => $request['start_date'],
            'end_date' => $request['end_date']
        ]);

        try {
            $className = $this->realmManager->getRawDataQueryClass($request['realm']);
            $this->logger->debug(sprintf('Instantiating query class "%s"', $className));
            $query = new $className(
                [
                    'start_date' => $request['start_date'],
                    'end_date' => $request['end_date']
                ],
                'accounting'
            );
            $allRoles = $user->getAllRoles();
            $query->setMultipleRoleParameters($allRoles, $user);

            $dataSet = new RawDataset($query, $user);

            // Data are fetched from the database as a side effect of checking
            // for results.
            if ($dataSet->hasResults()) {
                $this->logger->debug('Data set has results');
            }

            return $dataSet;
        } catch (Exception $e) {
            throw new Exception('Failed to execute batch export query', 0, $e);
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
     * Create a zip file containing a single file.
     *
     * @param string $dataFile Absolute path to file that will be put in zip file.
     * @param string $zipFile Absolute path to zip file that will be created.
     * @throws \Exception
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
     * Send email indicating a successful export.
     *
     * @param \XDUser $user The user that requested the export.
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
     * Send email indicating a failed export.
     *
     * Sends one email to the user that created the request and another email
     * to the tech support recipient.
     *
     * @param \XDUser $user The user that created the request.
     * @param array $request Export request data.
     * @param string $failureReason Friendly text explaining failure.
     * @param \Exception $e The exception that caused the failure.
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
