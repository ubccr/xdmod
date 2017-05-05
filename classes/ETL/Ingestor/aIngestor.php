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
use ETL\Configuration\EtlConfiguration;
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

        if ( ! $options instanceof IngestorOptions ) {
            $msg = "Options is not an instance of IngestorOptions";
            $this->logAndThrowException($msg);
        }

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see aAction::initialize()
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(EtlOverseerOptions $etlOverseerOptions = null)
    {
        if ( $this->isInitialized() ) {
            return;
        }

        $this->initialized = false;

        parent::initialize($etlOverseerOptions);

        $this->initialized = true;

        return true;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::execute()
     * ------------------------------------------------------------------------------------------
     */

    public function execute(EtlOverseerOptions $etlOverseerOptions)
    {
        $inDryrunMode = $this->getEtlOverseerOptions()->isDryrun();

        $time_start = microtime(true);
        $totalRecordsProcessed = 0;

        $this->initialize($etlOverseerOptions);

        // We could truncate tables after performPreExecuteTasks (where manageTables() is called),
        // but then if a table is truncated and modified the modification happens before the
        // truncation, which could be slow.

        $this->truncateDestination();

        // The EtlOverseerOptions class allows iteration over a list of chunked date ranges

        if ( false !== $this->performPreExecuteTasks() ) {

            // If this action supports chunking of the date range, use the chunked list
            // otherwise use the entire date range.

            if ( null !== $this->getEtlOverseerOptions()->getChunkSizeDays() && $this->supportDateRangeChunking ) {
                $datePeriodChunkList = $etlOverseerOptions->getChunkedDatePeriods();
                $this->logger->info("Breaking ETL period into " . count($datePeriodChunkList) . " chunks");
            } else {
                // Generate an array containing a single tuple. This may be (null, null)
                // if no start/end date was provided.
                $datePeriodChunkList = array(array( $this->currentStartDate, $this->currentEndDate ));
            }

            $numDateIntervals = count($datePeriodChunkList);
            $intervalNum = 1;

            foreach ( $datePeriodChunkList as $dateInterval ) {

                // Set current start and end dates for use deeper down in the machinery.

                $this->currentStartDate = $dateInterval[0];
                $this->currentEndDate = $dateInterval[1];

                $this->logger->info(
                    "Process date interval ($intervalNum/$numDateIntervals) "
                    . "(start: "
                    . ( null === $this->currentStartDate ? "none" : $this->currentStartDate )
                    . ", end: "
                    . ( null === $this->currentEndDate ? "none" : $this->currentEndDate )
                    . ")"
                );

                $localVariableMap = array(
                    'START_DATE' => $this->currentStartDate,
                    'END_DATE' => $this->currentEndDate
                );
                $this->variableMap = array_merge($this->variableMap, $localVariableMap);

                $numRecordsProcessed = $this->_execute();
                $totalRecordsProcessed += $numRecordsProcessed;
                $intervalNum++;

            }  // foreach ( $datePeriodChunkList as $dateInterval )

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
