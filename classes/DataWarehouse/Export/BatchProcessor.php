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
use Log;
use XDUser;
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
     * Data warehouse export realm manager.
     * @var \DataWarehouse\Export\RealmManager
     */
    private $realmManager;

    /**
     * Data warehouse export file manager.
     * @var \DataWarehouse\Export\FileManager
     */
    private $fileManager;

    /**
     * Construct a new batch processor.
     */
    public function __construct(Log $logger = null)
    {
        // Must set properties that are used in `setLogger` before calling the
        // parent constructor.
        $this->fileManager = new FileManager($logger);
        parent::__construct($logger);
        $this->dbh = DB::factory('database');
        $this->queryHandler = new QueryHandler();
        $this->realmManager = new RealmManager();
    }

    /**
     * Set the logger for this object.
     *
     * @see \CCR\Loggable::setLogger()
     * @param \Log $logger A logger instance or null to use the null logger.
     * @return self This object for method chaining.
     */
    public function setLogger(Log $logger = null)
    {
        parent::setLogger($logger);
        $this->fileManager->setLogger($logger);
        return $this;
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
        $this->logger->info([
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
            if (!$this->dryRun) {
                $this->queryHandler->submittedToAvailable($request['id']);
            }
            $dataSet = $this->getDataSet($request, $user);
            $format = $this->dryRun ? 'null' : $request['export_file_format'];
            $dataFile = $this->fileManager->writeDataSetToFile($dataSet, $format);
            $this->fileManager->createZipFile($dataFile, $request);

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
            if (!$this->dryRun) {
                $this->queryHandler->submittedToFailed($request['id']);
            }
            $this->sendExportFailureEmail($user, $request, $e);
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
        $this->logger->info('Processing expiring requests');
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
        $this->logger->info([
            'message' => 'Expiring request',
            'batch_export_request.id' => $request['id']
        ]);

        if ($this->dryRun) {
            $this->logger->notice('dry run: Not expiring export file');
            return;
        }

        try {
            $this->dbh->beginTransaction();
            $this->queryHandler->availableToExpired($request['id']);
            $this->fileManager->removeExportFile($request['id']);
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
            'batch_export_request.id' => $request['id'],
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
            $dataSet = new RawDataset($query, $user);

            $this->logger->debug('Executing query');

            // Data are fetched from the database as a side effect of checking
            // for results.
            if ($dataSet->hasResults()) {
                $this->logger->debug('Data set has results');
            }

            return $dataSet;
        } catch (Exception $e) {
            $this->logger->err([
                'message' => $e->getMessage(),
                'stacktrace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to execute batch export query', 0, $e);
        }
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

        // Remove time from expiration date time.
        list($expirationDate) = explode(' ', $request['export_expires_datetime']);

        MailWrapper::sendTemplate(
            'batch_export_success',
            [
                'subject' => 'Batch export ready for download',
                'toAddress' => $user->getEmailAddress(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'current_date' => date('Y-m-d'),
                'expiration_date' => $expirationDate,
                'download_url' => sprintf(
                    '%srest/v1/warehouse/export/download/%d',
                    xd_utilities\getConfigurationUrlBase('general', 'site_address'),
                    $request['id']
                ),
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
     * @param \Exception $e The exception that caused the failure.
     */
    private function sendExportFailureEmail(
        XDUser $user,
        array $request,
        Exception $e
    ) {
        if ($this->dryRun) {
            $this->logger->notice('dry run: Not sending failure email');
            return;
        }

        $this->logger->info([
            'message' => 'Sending failure email',
            'batch_export_request.id' => $request['id']
        ]);

        $message = $e->getMessage();
        $stackTrace = $e->getTraceAsString();
        while ($e = $e->getPrevious()) {
            $stackTrace .= sprintf(
                "\n\n%s\n%s",
                $e->getMessage(),
                $e->getTraceAsString()
            );
        }

        MailWrapper::sendTemplate(
            'batch_export_failure_admin_notice',
            [
                'subject' => 'Batch export failed',
                'toAddress' => xd_utilities\getConfiguration('general', 'tech_support_recipient'),
                'user_email' => $user->getEmailAddress(),
                'user_username' => $user->getUsername(),
                'user_formal_name' => $user->getFormalName(true),
                'current_date' => date('Y-m-d'),
                'exception_message' => $message,
                'exception_stack_trace' => $stackTrace
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
                'failure_reason' => $message,
                'maintainer_signature' => MailWrapper::getMaintainerSignature()
            ]
        );
    }
}
