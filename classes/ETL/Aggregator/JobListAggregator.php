<?php
/* ==========================================================================================
 * JobListAggregator used to aggregate tables that have an associated _job_list table.
 * The _job_list table contains the mappings from the aggregate table to the original
 * fact table. This is used by the "Show Raw Data" drill feature in the Metric Explorer
 * UI tab.
 *
 * @see pdoAggregator
 * @see iAction
 * ------------------------------------------------------------------------------------------
 */

namespace ETL\Aggregator;

use ETL\iAction;

class JobListAggregator extends pdoAggregator implements iAction
{
    /* ------------------------------------------------------------------------------------------
     * Delete the old records from each destination table and its associated _job_list table
     */
    protected function deleteAggregationPeriodData($aggregationUnit, $aggregationPeriodId, array $sqlRestrictions = array())
    {
        $totalRowsDeleted = 0;

        foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {
            $qualifiedDestTableName = $etlTable->getFullName();

            $joblisttable = $etlTable->getSchema() . '.`' . $etlTable->getName(false) . '_joblist`';

            $deleteSql = "DELETE $qualifiedDestTableName, $joblisttable FROM $qualifiedDestTableName LEFT JOIN $joblisttable ON $qualifiedDestTableName.id = $joblisttable.agg_id WHERE {$aggregationUnit}_id = $aggregationPeriodId";

            if ( count($sqlRestrictions) > 0 ) {
                $deleteSql .= " AND " . implode(" AND ", $sqlRestrictions);
            }

            $this->logger->debug(
                sprintf("Delete aggregation unit SQL %s:\n%s", $this->destinationEndpoint, $deleteSql)
            );
            $totalRowsDeleted += $this->destinationHandle->execute($deleteSql);
        }

        return $totalRowsDeleted;
    }
}  // class JobListAggregator
