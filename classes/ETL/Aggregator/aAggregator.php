<?php
/* ==========================================================================================
 * Abstract base class for aggregators.  We define the main control loop here and break the
 * aggregation process down into the following steps:
 *
 * 1. pre- and post-execution tasks
 * 2. pre- and post-aggregation unit tasks
 * 3. The actual aggregation task
 *
 * The extending class must implement those methods.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-11-01
 *
 * @see iAction
 * ==========================================================================================
 */

namespace ETL\Aggregator;

use ETL\aRdbmsDestinationAction;
use ETL\EtlOverseerOptions;
use ETL\Configuration\EtlConfiguration;
use ETL\aOptions;
use \Log;

abstract class aAggregator extends aRdbmsDestinationAction
{

    /* ------------------------------------------------------------------------------------------
     * @see iAction::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);

        if ( ! $options instanceof AggregatorOptions ) {
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
        $this->initialize($etlOverseerOptions);

        $totalStartTime = microtime(true);
        $numAggregationPeriodsProcessed = 0;

        // If this action supports chunking of the date range, use the chunked list
        // otherwise use the entire date range.

        if ( null !== $this->getEtlOverseerOptions()->getChunkSizeDays() && $this->supportDateRangeChunking ) {
            $datePeriodChunkList = $etlOverseerOptions->getChunkedDatePeriods();
            $this->logger->info("Breaking ETL period into " . count($datePeriodChunkList) . " chunks");
        } else {
            // Generate an array containing a single tuple
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
                'END_DATE' => $this->currentEndDate,
            );
            $this->variableMap = array_merge($this->variableMap, $localVariableMap);

            if ( false !== $this->performPreExecuteTasks() ) {

                foreach ( $this->options->aggregation_units as $aggregationUnit ) {
                    $startTime = microtime(true);

                    // The aggregation unit must be set for the AggregationTable

                    foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {
                        $etlTable->aggregation_unit = $aggregationUnit;
                    }

                    $this->variableMap['AGGREGATION_UNIT'] = $aggregationUnit;

                    if ( false === $this->performPreAggregationUnitTasks($aggregationUnit) ) {
                        $this->logger->notice("Pre-aggregation unit tasks failed, skipping unit '$aggregationUnit'");
                        continue;
                    }

                    // The destination should be truncated once prior to executing the aggregation
                    // but after the table exists.

                    $this->truncateDestination();

                    $this->logger->debug(
                        sprintf("Available Variables: %s", $this->getVariableMapDebugString())
                    );

                    // Perform the dirty work

                    $numAggregationPeriodsProcessed = $this->_execute($aggregationUnit);

                    $this->performPostAggregationUnitTasks($aggregationUnit, $numAggregationPeriodsProcessed);

                    $endTime = microtime(true);
                    $msg = sprintf(
                        'Aggregation time for %s %.2fs (avg %.2fs/period)',
                        $aggregationUnit,
                        $endTime - $startTime,
                        ($numAggregationPeriodsProcessed > 0 ? ($endTime - $startTime) / $numAggregationPeriodsProcessed : 0 )
                    );
                    $this->logger->info($msg);

                }  // foreach ( $this->options->aggregation_units as $aggregationUnit )

                // Perform any post execution actions

                $this->performPostExecuteTasks($numAggregationPeriodsProcessed);

            }  // if ( false !== $this->performPreExecuteTasks() )

            $intervalNum++;

        }  // foreach ( $datePeriodChunkList as $dateInterval )

        // NOTE: This is needed for the log summary.
        $totalEndTime = microtime(true);
        $this->logger->notice(array(
                                  "message"        => "end",
                                  'action'         => (string) $this,
                                  'start_time'     => $totalStartTime,
                                  'end_time'       => $totalEndTime,
                                  'elapsed_time'   => round(($totalEndTime - $totalStartTime)/60, 3) . "s"
                                  ));

    }  // execute()

    /* ------------------------------------------------------------------------------------------
     * Perform any pre-aggregation unit tasks. This are performed prior to aggregating each
     * aggregation unit (e.g., day, month, quarter) and might be verifying that a status table
     * contains the correct columns or that the date tables for the aggregation unit exist.
     *
     * NOTE: This method must check if we are in DRYRUN mode before executing any tasks.
     *
     * @param $aggregationUnit The aggregation unit being processed.
     *
     * @return true on success
     * ------------------------------------------------------------------------------------------
     */

    abstract protected function performPreAggregationUnitTasks($aggregationUnit);

    /* ------------------------------------------------------------------------------------------
     * Perform any post-aggregation unit tasks. This are performed after aggregating each aggregation
     * unit (e.g., day, month, quarter) and might be performing cleanup or marking jobs as aggregated
     * for that unit.
     *
     * NOTE: This method must check if we are in DRYRUN mode before executing any tasks.
     *
     * @param $aggregationUnit The aggregation unit being processed.
     * @param $numAggregationPeriodsProcessed The number of aggregation periods processed for this unit.
     *
     * @return true on success
     * ------------------------------------------------------------------------------------------
     */

    abstract protected function performPostAggregationUnitTasks($aggregationUnit, $numAggregationPeriodsProcessed);

    /* ------------------------------------------------------------------------------------------
     * Perform the actual aggregation for the specified aggregation unit.
     *
     * @param $aggregationUnit The current aggregation unit
     *
     * @return The number of aggregation periods processed
     * ------------------------------------------------------------------------------------------
     */

    // @codingStandardsIgnoreLine
    abstract protected function _execute($aggregationUnit);
}  // abstract class Aggregator
