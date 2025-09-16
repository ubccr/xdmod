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
use ETL\aOptions;
use ETL\Configuration\EtlConfiguration;
use Psr\Log\LoggerInterface;

class JobListAggregator extends pdoAggregator implements iAction
{

    /**
     * Text to append to the end of a table that is written to by this aggregator. This is
     * specified in options for this action using this aggregator. For the Cloud realm the
     * text should be _sessionlist. If no text to append to the table name is specified the
     * default is _joblist
     */
    private $joblistAppendTableName;

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, LoggerInterface $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);

        $this->joblistAppendTableName = (!is_null($options->joblist_append_table_name)) ? $options->joblist_append_table_name : '_joblist';
    }

    /* ------------------------------------------------------------------------------------------
     * Delete the old records from each destination table and its associated _job_list table
     */
    protected function deleteAggregationPeriodData($aggregationUnit, $aggregationPeriodStartId, $aggregationPeriodEndId, array $sqlRestrictions = array())
    {
        $totalRowsDeleted = 0;

        foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {
            $qualifiedDestTableName = $etlTable->getFullName();

            $joblisttable = $etlTable->getSchema() . '.`' . $etlTable->getName(false) . $this->joblistAppendTableName . '`';
            $deleteSql = "DELETE $qualifiedDestTableName, $joblisttable FROM $qualifiedDestTableName LEFT JOIN $joblisttable ON $qualifiedDestTableName.id = $joblisttable.agg_id WHERE {$aggregationUnit}_id BETWEEN $aggregationPeriodStartId AND $aggregationPeriodEndId";

            if ( count($sqlRestrictions) > 0 ) {
                $deleteSql .= " AND " . implode(" AND ", $sqlRestrictions);
            }

            $this->logger->info(
                sprintf("Delete aggregation unit SQL %s:\n%s", $this->destinationEndpoint, $deleteSql)
            );
            $totalRowsDeleted += $this->destinationHandle->execute($deleteSql);
        }

        return $totalRowsDeleted;
    }
}  // class JobListAggregator
