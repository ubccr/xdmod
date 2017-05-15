<?php
/* ==========================================================================================
 * Aggregator for jobs that make use of a status table. For example, the jobfact table has triggers
 * that update the jobfactstatus table on insert/update/delete to manage the current state of jobs
 * added to the table.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-02-23
 *
 * @see pdoAggregator
 * @see iAction
 * ------------------------------------------------------------------------------------------
 */

namespace ETL\Aggregator;

use ETL\iAction;
use \PDOException;

class JobsAggregator extends pdoAggregator implements iAction
{
    // Name of the status table that we will be updating
    const STATUS_TABLE = "jobfactstatus";

    // An optional, comma separated, list of resource identifiers used to restrict operations to only
    // those resources. Generated from $this->resourceIdList
    protected $resourceIdListString;

    /* ------------------------------------------------------------------------------------------
     * Verify columns in the jobfactstatus table.
     *
     * @see aAggregator::performPreAggregationUnitTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performPreAggregationUnitTasks($aggregationUnit)
    {
        // Verify the jobfactstatus table supports this aggregation unit.

        $sourceSchema = $this->sourceEndpoint->getSchema(true);
        $tableName = $this->destinationEndpoint->quoteSystemIdentifier(self::STATUS_TABLE);
        $sql = "SHOW COLUMNS IN $tableName IN {$sourceSchema} LIKE 'aggregated_{$aggregationUnit}'";

        $this->logger->info("Verify status table {$sourceSchema}.{$tableName} for '$aggregationUnit'");

        try {
            $this->logger->debug($sql);
            if ( 0 === $this->utilityHandle->execute($sql) ) {
                $msg = "Aggregation unit column 'aggregated_{$aggregationUnit}' not found in {$sourceSchema}.{$tableName}";
                $this->logger->notice($msg);
                return false;
            }
        } catch (PDOException $e ) {
            $this->logAndThrowException(
                "Error querying {$sourceSchema}.{$tableName}",
                array('exception' => $e, 'sql' => $sql, 'endpoint' => $this->utilityEndpoint)
            );
        }

        return parent::performPreAggregationUnitTasks($aggregationUnit);

    }  // performPreAggregationUnitTasks()

    /* ------------------------------------------------------------------------------------------
     * Update the jobfactstatus table to mark jobs as aggregated by the aggregation unit.
     *
     * @see aAggregator::performPostAggregationUnitTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performPostAggregationUnitTasks($aggregationUnit, $numAggregationPeriodsProcessed)
    {
        $sourceSchema = $this->sourceEndpoint->getSchema(true);
        $tableName = $this->destinationEndpoint->quoteSystemIdentifier(self::STATUS_TABLE);

        $this->logger->info("Update status table {$sourceSchema}.{$tableName} for '$aggregationUnit'");

        if ( 0 == $numAggregationPeriodsProcessed ) {
            return parent::performPostAggregationUnitTasks($aggregationUnit, $numAggregationPeriodsProcessed);
        }

        $sql = "UPDATE {$sourceSchema}.{$tableName} SET aggregated_{$aggregationUnit} = 1";
        $whereClauses = array();

        // If we're forcing the date we want to only update jobfactstatus for the dates that we're
        // actually updating.

        if ( null !== $this->resourceIdListString ) {
            $whereClauses[] =  "resource_id IN (" . $this->resourceIdListString . ")";
        }

        if ( $this->getEtlOverseerOptions()->isForce() ) {
            if ( null !== $this->currentStartDate ) {
                $qStartDate = $this->sourceEndpoint->quote($this->currentStartDate);
                $whereClauses[] = "end_time_ts >= UNIX_TIMESTAMP($qStartDate)";
            }
            if ( null !== $this->currentEndDate ) {
                $qEndDate = $this->sourceEndpoint->quote($this->currentEndDate);
                $whereClauses[] = "end_time_ts <= UNIX_TIMESTAMP($qEndDate)";
            }
        }  // if ( $this->getEtlOverseerOptions()->isForce() )

        if ( 0 != count($whereClauses) ) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $this->logger->debug($sql);

        try {
            if ( ! $this->getEtlOverseerOptions()->isDryrun() ) {
                $numRows = $this->destinationHandle->execute($sql);
                $this->logger->info("Updated $numRows rows");
            }
        } catch (PDOException $e ) {
            $this->logAndThrowException(
                "Error updating {$sourceSchema}.{$tableName}",
                array('exception' => $e, 'sql' => $sql, 'endpoint' => $this->destinationEndpoint)
            );
        }

        return parent::performPostAggregationUnitTasks($aggregationUnit, $numAggregationPeriodsProcessed);

    }  // performPostAggregationUnitTasks()

    /* ------------------------------------------------------------------------------------------
     * @see aAggregator::performPreExecuteTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performPreExecuteTasks()
    {
        // Ensure that we have resource spec information for all resources reporting jobs

        $this->checkResourceSpecs();

        // This aggregator will be going away when OSG is migrated to the new job record & job task
        // tables, but in the mean time force the restriction to OSG.
        $this->resourceIdListString = "2799";

        return true;
    }  // performPreExecuteTasks()

    /* ------------------------------------------------------------------------------------------
     * Clear out any entries from the jobfactstatus table that have been completely aggregated.
     *
     * @see aAggregator::performPostExecuteTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performPostExecuteTasks($numRecordsProcessed)
    {
        $sourceSchema = $this->sourceEndpoint->getSchema(true);
        $tableName = $this->destinationEndpoint->quoteSystemIdentifier(self::STATUS_TABLE);

        $numRows = 0;

        $this->logger->info("Clean status table {$sourceSchema}.{$tableName}");

        if ( 0 === $numRecordsProcessed ) {
            return parent::performPostExecuteTasks($numRecordsProcessed);
        }

        // Clean up fully aggregated jobs from the jobfactstatus table.  Dynamically query the list of
        // aggregation periods in the jobfactstatus table so we don't have to hard code them here.

        try {
            $whereClauses = array();
            $sql = "SHOW COLUMNS IN $tableName IN $sourceSchema LIKE 'aggregated_%'";

            $result = $this->destinationHandle->query($sql);

            foreach ( $result as $row ) {
                $whereClauses[] = $row['Field'] . "=1";
            }

            // If we're restricting aggregation to selected resources only delete entries for those resources

            if ( null !== $this->resourceIdListString ) {
                $whereClauses[] = "resource_id IN (" . $this->resourceIdListString . ")";
            }

            if ( $this->getEtlOverseerOptions()->isForce() ) {
                if ( null !== $this->currentStartDate ) {
                    $qStartDate = $this->sourceEndpoint->quote($this->currentStartDate);
                    $whereClauses[] = "end_time_ts >= UNIX_TIMESTAMP($qStartDate)";
                }
                if ( null !== $this->currentEndDate ) {
                    $qEndDate = $this->sourceEndpoint->quote($this->currentEndDate);
                    $whereClauses[] = "end_time_ts <= UNIX_TIMESTAMP($qEndDate)";
                }
            }  // if ( $this->getEtlOverseerOptions()->isForce() )

            // If we always run the full set of aggregation periods, this can be done once at the end...

            $sql = "DELETE FROM {$sourceSchema}.{$tableName} WHERE " . implode(" AND ", $whereClauses);
            $this->logger->debug($sql);

            if ( ! $this->getEtlOverseerOptions()->isDryrun() ) {
                $numRows = $this->destinationHandle->execute($sql);
                $this->logger->info("Removed $numRows rows");
            }

        } catch (PDOException $e ) {
            $this->logAndThrowException(
                "Error cleaning {$sourceSchema}.{$tableName}",
                array('exception' => $e, 'sql' => $sql, 'endpoint' => $this->destinationEndpoint)
            );
        }

        return parent::performPostExecuteTasks($numRecordsProcessed);

    }  // performPostExecuteTasks()


    /* ------------------------------------------------------------------------------------------
     * This is the heart of the aggregation process and decides what actually gets aggregated.  Query
     * the database for the date ids that are dirty (i.e., those that have un-aggregated entries) for
     * each slice of the period being aggregated. Only ids containing entries that are waiting for
     * aggregation are returned unless we are forcing aggregation over a certain period.
     *
     * NOTE: Date periods are returned starting with the most recent so newer data will be aggregated
     *   first.
     *
     * NOTE: This method must check if we are in DRYRUN mode before executing any tasks and return an
     *   empty array.
     *
     * @param $aggregationUnit The aggregation unit that we are currently processing
     *
     * @return The result statement
     * ------------------------------------------------------------------------------------------
     */

    protected function getDirtyAggregationPeriods($aggregationUnit)
    {
        if ( empty($aggregationUnit) ) {
            $msg = "Empty aggregation unit";
            $this->logAndThrowException($msg);
        }

        $sourceSchema = $this->sourceEndpoint->getSchema(true);
        $utilitySchema = $this->utilityEndpoint->getSchema(true);
        $tableName = $this->sourceEndpoint->quoteSystemIdentifier(self::STATUS_TABLE);
        $dateRangeSql = "";
        $minMaxJoin = null;

        // If we are forcing aggregation for a specific period, simply select all periods that overlap
        // the specified date range

        if ( $this->getEtlOverseerOptions()->isForce() ) {

            $ranges = array();

            if ( null !== $this->currentStartDate ) {
                $startDate = $this->sourceHandle->quote($this->currentStartDate);
                $ranges[] = "d.${aggregationUnit}_end_ts >= UNIX_TIMESTAMP($startDate)";
            }

            if ( null !== $this->currentEndDate ) {
                $endDate = $this->sourceHandle->quote($this->currentEndDate);
                $ranges[] = "d.${aggregationUnit}_start_ts <= UNIX_TIMESTAMP($endDate)";
            }

            $dateRangeSql = implode(" AND ", $ranges);

        } else {

            // Under normal operation, be smart about what to aggregate. Check the status table and only
            // select periods where a job needs to be aggregated.
            //
            // Using the js_limits sub-query to determine the min and max dates in the range, we can leave
            // out the join with the jobfactstatus table and SIGNIFICANTLY reduce the amount of time the
            // query takes for larger result sets.
            //
            // HOWEVER, if there is an outlier this can cause the selected range to be large and result in
            // unnecessary re-aggregation of jobs between the outlier and the rest of the jobs. For
            // example, a historical job gets added or updated all periods from the historial job to the
            // present will be re-aggregated.

            /* --------------------------------------------------------------------------------
             * The following query will select only those periods matching a job in the status table but
             * takes FAR longer to run on larger data sets such as OSG (e.g., 409k jobs = ~256 seconds vs
             * 2-3 seconds).  Since this is run for each aggregation period this slowed aggregation
             * considerably. This should be tuned if possible since it's a better result. -smg

             SELECT distinct
             d.id as period_id,
             d.`year` as year_id,
             d.`day` as period_value,
             d.day_start as period_start,
             d.day_end as period_end,
             d.day_start_ts as period_start_ts,
             d.day_end_ts as period_end_ts,
             d.hours as period_hours,
             d.seconds as period_seconds
             FROM modw.days d,
             (
             SELECT js.start_time_ts, js.end_time_ts
             FROM federated_osg.jobfactstatus js
             WHERE js.aggregated_day = 0
             ) js_limits
             WHERE
             (js_limits.end_time_ts between d.day_start_ts and d.day_end_ts)
             OR (d.day_end_ts between js_limits.start_time_ts and js_limits.end_time_ts)
             ORDER BY 2 DESC, 3 DESC;

             * --------------------------------------------------------------------------------
             */

            $whereClauses = array("aggregated_${aggregationUnit} = 0");
            if ( null !== $this->resourceIdListString ) {
                $whereClauses[] = "resource_id IN (" . $this->resourceIdListString . ")";
            }

            $minMaxSql = "SELECT MIN(start_time_ts) as min_start, MAX(end_time_ts) as max_end " .
                "FROM {$sourceSchema}.{$tableName}" .
                ( 0 != count($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "" );

            $minMaxJoin = "(\n  $minMaxSql\n) js_limits";

            $dateRangeSql = "d.${aggregationUnit}_end_ts >= js_limits.min_start " .
                "AND d.${aggregationUnit}_start_ts <= js_limits.max_end";

        }  // else ( $this->getEtlOverseerOptions()->isForce() )

        // NOTE: The "ORDER BY 2 DESC, 3 DESC" is important because it allows most recent periods to be
        // aggregated first.

        // Note that period_start_day_id and period_end_day_id are not used by the OsgJobsAggregator

        $sql =
            "SELECT distinct
         d.id as period_id,
         d.`year` as year_value,
         d.`${aggregationUnit}` as period_value,
         d.${aggregationUnit}_start as period_start,
         d.${aggregationUnit}_end as period_end,
         d.${aggregationUnit}_start_ts as period_start_ts,
         d.${aggregationUnit}_end_ts as period_end_ts,
         d.hours as period_hours,
         d.seconds as period_seconds,
         0 as period_start_day_id,
         0 as period_end_day_id
       FROM {$utilitySchema}.${aggregationUnit}s d" . (null !== $minMaxJoin ? ",\n$minMaxJoin" : "" ) . "
       WHERE $dateRangeSql
       ORDER BY 2 DESC, 3 DESC";

        // If we're running in DRYRUN mode return an empty array. This allows us to skip the aggregation
        // period loop.
        $result = array();

        try {
            $this->logger->debug("Select dirty aggregation periods:\n$sql");
            if ( ! $this->getEtlOverseerOptions()->isDryrun() ) {
                $result = $this->utilityHandle->query($sql);
            }
        } catch (PDOException $e) {
            $this->logAndThrowException(
                "Error querying aggregation dirty date ids",
                array('exception' => $e, 'sql' => $sql, 'endpoint' => $this->utilityEndpoint)
            );
        }

        return $result;

    }  // getDirtyAggregationPeriods()


    /* ------------------------------------------------------------------------------------------
     * Verify that we have all of the necessary data on resources that have provided job data for the
     * aggregation period. This includes nodes and processor counts.
     *
     * @param $startDate The start of the aggregation period
     * @param $endDate The start of the aggregation period
     *
     * @return true on success
     * @throw Exception on failure
     * ------------------------------------------------------------------------------------------
     */

    protected function checkResourceSpecs()
    {
        if ( $this->verifiedResourceSpecs ) {
            return;
        }

        $utilitySchema = $this->utilityEndpoint->getSchema();
        $sourceSchema = $this->sourceEndpoint->getSchema();

        $sql =
            "select distinct resource_id as resource_id
       from {$sourceSchema}.jobfact
       where
       start_time_ts between unix_timestamp(:startDate) and unix_timestamp(:endDate)
       and resource_id not in (select distinct resource_id from ${utilitySchema}.resourcespecs where processors is not null)" .
            ( null !== $this->resourceIdListString ? " and resource_id IN (" . $this->resourceIdListString . ")" : "");

        $params = array(
            ":startDate" => $this->currentStartDate,
            ":endDate" => $this->currentEndDate
            );

        $this->logger->debug("Verify resource specs exist " . $this->sourceEndpoint . ":\n$sql");
        $result = $this->sourceHandle->query($sql, $params);
        if ( count($result) > 0 ) {
            $resources = array();
            foreach ($result as $resource) {
                $resources[] = $resource['resource_id'];
            }

            $howToUpdateResource =
                \xd_utilities\getConfiguration('features', 'xsede') == 'on'
                ? 'update the resource config files in "' . CONFIG_DIR . '/ingestors/TGcDB"'
                : 'update the resource definition file located at "' . CONFIG_DIR . '/resource_specs.json"';
            $msg = "New Resource(s) in resourcespecs table does not have processor information. Enum of resource_id(s): "
                . implode(',', $resources) . "\n"
                . "To fix this problem, determine values for processors, ppn and nodes count for each resource and "
                . $howToUpdateResource . ".";
            $this->logAndThrowException($msg);
        }

        $this->verifiedResourceSpecs = true;

    }  // checkResourceSpecs()
}  // class JobsAggregator
