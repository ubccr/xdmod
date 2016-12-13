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
use ETL\EtlConfiguration;
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
     * @see iAction::verify()
     * ------------------------------------------------------------------------------------------
     */

    public function verify(EtlOverseerOptions $etlOptions = null)
    {

        if ( $this->isVerified() ) {
            return;
        }

        $this->verified = false;
        if ( null !== $etlOptions ) {
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
        if ( $this->isInitialized() ) {
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
        $numAggregationPeriodsProcessed = 0;

        $this->verify();

        $totalStartTime = microtime(true);

        // The EtlOverseerOptions class allows iteration over a list of chunked date ranges

        foreach ( $etlOptions as $interval ) {

            $this->logger->info("Process date interval (start: " .
                                $etlOptions->getCurrentStartDate() .
                                ", end: " .
                                $etlOptions->getCurrentEndDate() . ")");

            $this->variableMap['START_DATE'] = $etlOptions->getCurrentStartDate();
            $this->variableMap['END_DATE'] = $etlOptions->getCurrentEndDate();

            if ( false !== $this->performPreExecuteTasks() ) {

                foreach ( $this->options->aggregation_units as $aggregationUnit ) {
                    $startTime = microtime(true);

                    // The aggregation unit must be set for the AggregationTable

                    foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {
                        $etlTable->setAggregationUnit($aggregationUnit);
                    }

                    if ( false === $this->performPreAggregationUnitTasks($aggregationUnit) ) {
                        $this->logger->notice("Pre-aggregation unit tasks failed, skipping unit '$aggregationUnit'");
                        continue;
                    }

                    // The destination should be truncated once prior to executing the aggregation
                    // but after the table exists.

                    $this->truncateDestination();

                    // Perform the dirty work

                    $numAggregationPeriodsProcessed = $this->_execute($aggregationUnit);

                    $this->performPostAggregationUnitTasks($aggregationUnit, $numAggregationPeriodsProcessed);

                    $endTime = microtime(true);
                    $msg = sprintf('Aggregation time for %s %.2fs (avg %.2fs/period)',
                                   $aggregationUnit,
                                   $endTime - $startTime,
                                   ($numAggregationPeriodsProcessed > 0 ? ($endTime - $startTime) / $numAggregationPeriodsProcessed : 0 ) );
                    $this->logger->info($msg);

                }  // foreach ( $this->options->aggregation_units as $aggregationUnit )

                // Perform any post execution actions

                $this->performPostExecuteTasks($numAggregationPeriodsProcessed);

            }  // if ( false !== $this->performPreExecuteTasks() )

        }  // foreach ( $etlOptions as $interval )

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

    abstract protected function _execute($aggregationUnit);

}  // abstract class Aggregator
