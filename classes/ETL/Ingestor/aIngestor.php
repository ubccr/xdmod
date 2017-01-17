<?php
/* ==========================================================================================
 * Abstract helper class to encapsulare functionality common to all actions (e.g., Aggregators and
 * Ingestors).  Actions may extend this class for simplicity, but they must all implement iAction.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-11-01
 *
 * @see iAction
 * ==========================================================================================
 */

namespace ETL\Ingestor;

use ETL\aRdbmsDestinationAction;
use ETL\EtlOverseerOptions;
use ETL\EtlConfiguration;
use ETL\aOptions;
use \Log;

abstract class aIngestor extends aRdbmsDestinationAction
{

    /* ------------------------------------------------------------------------------------------
     * @see iAction::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);

        if (! $options instanceof IngestorOptions) {
            $msg = "Options is not an instance of IngestorOptions";
            $this->logAndThrowException($msg);
        }
    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::verify()
     * ------------------------------------------------------------------------------------------
     */

    public function verify(EtlOverseerOptions $etlOptions = null)
    {

        if ($this->isVerified()) {
            return;
        }

        $this->verified = false;
        if (null !== $etlOptions) {
            $this->setEtlOverseerOptions($etlOptions);
        }

        $this->initialize();

        parent::verify();

        $this->verified = true;

        return true;
    }  // verify()

    /* ------------------------------------------------------------------------------------------
     * @see aAction::initialize()
     * ------------------------------------------------------------------------------------------
     */

    protected function initialize()
    {
        if ($this->isInitialized()) {
            return;
        }

        $this->initialized = false;

        parent::initialize();

        $this->variableMap['START_DATE'] = $this->etlOverseerOptions->getStartDate();
        $this->variableMap['END_DATE'] = $this->etlOverseerOptions->getEndDate();

        $this->initialized = true;

        return true;
    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::execute()
     * ------------------------------------------------------------------------------------------
     */

    public function execute(EtlOverseerOptions $etlOptions)
    {
        $this->setEtlOverseerOptions($etlOptions);
        $inDryrunMode = $this->etlOverseerOptions->isDryrun();

        $this->verify();

        $time_start = microtime(true);
        $totalRecordsProcessed = 0;

        // We could truncate tables after performPreExecuteTasks (where manageTables() is called),
        // but then if a table is truncated and modified the modification happens before the
        // truncation, which could be slow.

        $this->truncateDestination();

        // The EtlOverseerOptions class allows iteration over a list of chunked date ranges

        if (false !== $this->performPreExecuteTasks()) {
            foreach ($etlOptions as $interval) {
                $this->logger->info("Process date interval (start: " .
                                    $etlOptions->getCurrentStartDate() .
                                    ", end: " .
                                    $etlOptions->getCurrentEndDate() . ")");

                $this->variableMap['START_DATE'] = $etlOptions->getCurrentStartDate();
                $this->variableMap['END_DATE'] = $etlOptions->getCurrentEndDate();

                $numRecordsProcessed = $this->_execute();
                $totalRecordsProcessed += $numRecordsProcessed;
            }  // foreach ( $etlOptions as $interval )

            $this->performPostExecuteTasks($totalRecordsProcessed);
        }  // if ( false !== $this->performPreExecuteTasks() )

        $time_end = microtime(true);
        $time = $time_end - $time_start;

        $message = sprintf(
            '%s: Rows Processed: %d records (Time Taken: %01.2f s)',
            get_class($this),
            $totalRecordsProcessed,
            $time
        );
        $this->logger->info($message);

        // NOTE: This is needed for the log summary.
        $this->logger->notice(array(
                                  'action'           => (string) $this,
                                  'start_time'       => $time_start,
                                  'end_time'         => $time_end,
                                  'elapsed_time'     => round($time, 5),
                                  'records_examined' => $totalRecordsProcessed,
                                  'records_loaded'   => $totalRecordsProcessed
                                  ));
    }

    /* ------------------------------------------------------------------------------------------
     * Perform any pre-execution tasks. For example, disabling table keys on MyISAM tables, or other
     * setup tasks.
     *
     * NOTE: This method must check if we are in DRYRUN mode before executing any tasks.
     *
     * @return true on success
     * ------------------------------------------------------------------------------------------
     */

    abstract protected function performPreExecuteTasks();

    /* ------------------------------------------------------------------------------------------
     * Perform any post-execution tasks. For example, enabling table keys on MyISAM tables, or
     * tracking table history.
     *
     * NOTE: This method must check if we are in DRYRUN mode before executing any tasks.
     *
     * @param $numRecordsProcessed The number of records processed during this period.
     *
     * @return true on success
     * ------------------------------------------------------------------------------------------
     */

    abstract protected function performPostExecuteTasks($numRecordsProcessed);

    /* ------------------------------------------------------------------------------------------
     * Perform the actual work of ingestion.
     *
     * @return The number of records processed
     * ------------------------------------------------------------------------------------------
     */

    abstract protected function _execute();
}  // abstract class aIngestor
