<?php
/* ==========================================================================================
 * The PDO aggregator encapsulates functionality for aggregating from one table into another. This
 * class must be extended to define an aggregator but may be as simple as wrapping the constructor
 * and performing integrety checks (i.e., SimpleAggregator).
 *
 * Aggregation is typically based off of an AggregationTable object that includes the definition of
 * the destination table, formulas for mapping the source into the destination table, group by
 * fields, and optional where clauses.  The definition of the aggregation table supports macros that
 * will be substituted at run time including:
 *
 * ${UTILITY_SCHEMA} The schema of the utility database (e.g., modw)
 * ${SOURCE_SCHEMA} The schema of the source database (e.g., modw or federated_osg)
 * ${DESTINATION_SCHEMA} The schema of the destination database (e.g., modw_aggregates)
 * ${AGGREGATION_UNIT} The current aggregation unit, specified in the aggregator definiton (e.g., day, month)
 * ${:YEAR_VALUE} The year of the current aggregation period being processed. This is iterative.
 * ${:PERIOD_SECONDS} The number of seconds in the current aggregation period being processed.
 * ${:PERIOD_VALUE} The value of the aggregation period within the year (e.g., day of year, month of year, etc.)
 * ${:PERIOD_ID} Identifier into the aggregation period tables (e.g., days, months, etc.)
 * ${:PERIOD_START} Start datetime of the current period
 * ${:PERIOD_END} End datetime of the current period
 * ${:PERIOD_START_TS} Start timestamp of the current period
 * ${:PERIOD_END_TS} End timestamp of the current period
 *
 * Note: Schema definitions are taken from the data endpoint defintions for this aggregator.
 *
 * The process for aggregating data is performed for each aggregation unit and is as follows:
 *
 * 1. Set up data endpoints and verify they are the correct type (Mysql in this case)
 * 2. Construct the definiton of the aggregation table based on an AggregationTale object
 * 3. Create or alter the aggregation table if needed. The table is altered to match the current
 *    definition and indexes are updated according to the columns to be included in the group-by. If
 *    the table is being truncated, drop the table and then re-create it.
 * 4. Generate the source SELECT and destination INSERT statements that will be used to aggregate
 *    the data.  These statements include parameters to be set during the aggregation process. If
 *    the source and destination endpoints are on the same database instance we can optimize using a
 *    SELECT...INSERT statement.
 * 5. Query the jobstatus, and date id tables to determine date ranges that have jobs to be
 *    aggregated. Only data included in these date ranges will be aggregated. If forcing aggregation
 *    of a date range query the jobfact table instead. Optionally restrict to a subset of resources.
 * 6. For each date period, perform a SELECT and INSERT to aggregate the source data and insert the
 *    aggregated data into the appropritate table.
 * 7. Optimize the new table
 * 8. Mark the jobs as aggregated for the current period in the jobfactstatus table.
 * 9. Cleanup. Purge fully aggregated jobs from the jobstatus table.
 *
 * NOTES:
 *
 * - A trigger exists on the jobfact table that creates/updates/deletes an entry in the
 *   jobfactstatus table for each job that is added, updated or removed.  The jobfactstatus table is
 *   used to control aggregation and contains a column for each aggregation period which as set to 1
 *   when aggregation is complete. Records are removed when aggregation for each period has been
 *   performed.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-10-09
 * ==========================================================================================
 */

namespace ETL\Aggregator;

use ETL\aOptions;
use ETL\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\DataEndpoint\Mysql;
use ETL\DbEntity\AggregationTable;
use ETL\DbEntity\Query;
use ETL\DbEntity\Table;
use ETL\Utilities;

use \Log;
use \PDOException;
use PDOStatement;
use \PDO;

class pdoAggregator extends aAggregator
{

    // Set to true once we have verified that the resourcespecs table is populated for resources that
    // have reported job information.
    protected $verifiedResourceSpecs = false;

    // A Query object containing the source query for this ingestor
    protected $etlSourceQuery = null;

    // This action does not (yet) support multiple destination tables. If multiple destination
    // tables are present, store the first here and use it.
    protected $etlDestinationTable = null;

    // If the query was modified for aggregation period batching, store the original FROM table here
    protected $etlSourceQueryOrigFromTable = null;

    // Flag indicating whether or not the source Query object was modified by this action
    protected $etlSourceQueryModified = false;

    // Unqualified name of the temporary table to use when batching
    const BATCH_TMP_TABLE_NAME = "agg_tmp";

    // The INSERT, SELECT, and INSERT INTO ... SELECT statements for the aggregation query.
    protected $insertSql = null;
    protected $selectSql = null;
    protected $optimizedInsertSql = null;

    /* ------------------------------------------------------------------------------------------
     * Set up data endpoints and other options.
     *
     * @param IngestorOptions $options Options specific to this Ingestor
     * @param EtlConfiguration $etlConfig Parsed configuration options for this ETL
     * @param string $defaultTablePrefix Default table prefix as defined in the child class (e.g.,
     *   "jobfact_by_")
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Initialize data required to perform the action.  Since this is an action of a target database
     * we must parse the definition of the target table.
     *
     * @throws Exception if any query data was not
     * int the correct format.
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(EtlOverseerOptions $etlOverseerOptions = null)
    {
        if ( $this->isInitialized() ) {
            return;
        }

        $this->initialized = false;

        parent::initialize($etlOverseerOptions);

        // Set up the handles to the various data sources and verify they are the correct type

        if ( ! $this->utilityEndpoint instanceof Mysql ) {
            $msg = "Utility endpoint is not an instance of ETL\\DataEndpoint\\Mysql";
            $this->logAndThrowException($msg);
        }

        if ( ! $this->sourceEndpoint instanceof Mysql ) {
            $msg = "Source endpoint is not an instance of ETL\\DataEndpoint\\Mysql";
            $this->logAndThrowException($msg);
        }

        if ( null === $this->etlSourceQuery ) {

            if ( ! isset($this->parsedDefinitionFile->source_query) ) {
                $msg = "Definition file does not contain a 'source_query' key";
                $this->logAndThrowException($msg);
            }

            $this->logger->debug("Create ETL source query object");
            $this->etlSourceQuery = new Query(
                $this->parsedDefinitionFile->source_query,
                $this->sourceEndpoint->getSystemQuoteChar()
            );
        }  // ( null === $this->etlSourceQuery )

        // --------------------------------------------------------------------------------
        // Create the list of supported macros. Macros starting with a colon (:) are PDO bind
        // paramaters passed in the loop of dirty date ids. If this list is modified, be sure to update
        // the documentation!

        $localParameters = array(
            ':YEAR_VALUE' => ":year_value",
            // Number of seconds in the aggregation period
            ':PERIOD_SECONDS' => ":period_seconds",
            // Value of the period within the year (e.g., day of year, month of year, etc.)
            ':PERIOD_VALUE' => ":period_value",
            // Identifier into the aggregation period tables (e.g., days, months, etc.)
            ':PERIOD_ID' => ":period_id",
            // Start datetime of the period
            ':PERIOD_START' => ":period_start",
            // End datetime of the period
            ':PERIOD_END' => ":period_end",
            // Start timestamp of the period
            ':PERIOD_START_TS' => ":period_start_ts",
            // End timestamp of the period
            ':PERIOD_END_TS' => ":period_end_ts",
            // The day_id of the start of this period
            ':PERIOD_START_DAY_ID' => ":period_start_day_id",
            // The day_id of the end of this period
            ':PERIOD_END_DAY_ID' => ":period_end_day_id"
        );

        $this->variableMap = array_merge($this->variableMap, $localParameters);

        // An individual action may override restrictions provided by the overseer.
        $this->setOverseerRestrictionOverrides();

        $this->getEtlOverseerOptions()->applyOverseerRestrictions($this->etlSourceQuery, $this->sourceEndpoint, $this);

        if ( null === $this->etlSourceQuery ) {
            $msg = "ETL source query is not set";
            $this->logAndThrowException($msg);
        } elseif ( ! $this->etlSourceQuery instanceof Query ) {
            $msg = "ETL source query is not an instance of Query";
            $this->logAndThrowException($msg);
        }

        // Group by fields must match existing column names. Variables are not substituted at this point
        // but it doesn't matter because the naming will still be consistent.

        $columnNames = $this->etlDestinationTable->getColumnNames();
        $missingColumnNames = array_diff($this->etlSourceQuery->getGroupBys(), $columnNames);

        if ( 0 != count($missingColumnNames) ) {
            $msg = "Columns in group by not found in table: " . implode(", ", $missingColumnNames);
            $this->logAndThrowException($msg);
        }

        $missingColumnNames = array_diff(array_keys($this->etlSourceQuery->getRecords()), $columnNames);

        if ( 0 != count($missingColumnNames) ) {
            $msg = "Columns in formulas not found in table: " . implode(", ", $missingColumnNames);
            $this->logAndThrowException($msg);
        }

        $this->initialized = true;

        return true;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * Override aRdbmsDestinationAction::createDestinationTableObjects() and use
     * AggregationTable objects instead of Table objects for the destination tables.
     *
     * @see aRdbmsDestinationAction::createDestinationTableObjects()
     * ------------------------------------------------------------------------------------------
     */

    protected function createDestinationTableObjects()
    {

        // If the etlDestinationTable is set, it will not be generated in aRdbmsDestinationAction

        if ( ! isset($this->parsedDefinitionFile->table_definition) ) {
            $msg = "Definition file does not contain a 'table_definition' key";
            $this->logAndThrowException($msg);
        }

        // This action only supports 1 destination table so use the first one and log a warning if
        // there are multiple.

        if ( is_array($this->parsedDefinitionFile->table_definition)
             && count($this->parsedDefinitionFile->table_definition) > 0 )
        {
            $tableDefinition = $this->parsedDefinitionFile->table_definition;
            $this->parsedDefinitionFile->table_definition = array_shift($tableDefinition);
            $msg = $this . " does not support multiple ETL destination tables, using first table.";
            $this->logger->warning($msg);
        }

        // Aggregation does not support multiple destination tables.

        if ( ! is_object($this->parsedDefinitionFile->table_definition) ) {
            $msg = "Table definition must be an object. Aggregation does not currently support multiple destination tables.";
            $this->logAndThrowException($msg);
        }

        $this->logger->debug("Create ETL destination aggregation table object");
        $this->etlDestinationTable = new AggregationTable(
            $this->parsedDefinitionFile->table_definition,
            $this->destinationEndpoint->getSystemQuoteChar(),
            $this->logger
        );
        $this->etlDestinationTable->setSchema($this->destinationEndpoint->getSchema());

        if ( isset($this->options->table_prefix) &&
             $this->options->table_prefix != $this->etlDestinationTable->getTablePrefix() )
        {
            $msg =
                "Overriding table prefix from " .
                $this->etlDestinationTable->getTablePrefix()
                . " to " .
                $this->options->table_prefix;
            $this->logger->debug($msg);
            $this->etlDestinationTable->setTablePrefix($this->options->table_prefix);
        }

        // Aggregation does not support multiple destination tables but we must still populate
        // the table list since it is used by methods upstream.
        $this->etlDestinationTableList[$this->parsedDefinitionFile->table_definition->name] = $this->etlDestinationTable;

    }  // createDestinationTableObjects()

    /* ------------------------------------------------------------------------------------------
     * By default, there are no pre-execution tasks.
     *
     * @see aAggregator::performPreExecuteTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performPreExecuteTasks()
    {
        // To support programmatic manipulation of the source Query object, save off the description
        // of the first join (from) table
        $sourceJoins = $this->etlSourceQuery->getJoins();
        $this->etlSourceQueryOrigFromTable = array_shift($sourceJoins);
        $this->etlSourceQueryModified = false;

        return true;
    }  // performPreExecuteTasks()

    /* ------------------------------------------------------------------------------------------
     * By default, there are no pre-execution tasks.
     *
     * @see aAggregator::performPostExecuteTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performPostExecuteTasks($numRecordsProcessed)
    {
        return true;
    }  // performPostExecuteTasks()

    /* ------------------------------------------------------------------------------------------
     * By default, there are no pre-aggregation unit tasks.
     *
     * @see aAggregator::performPreAggregationUnitTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performPreAggregationUnitTasks($aggregationUnit)
    {
        // A table matching the aggregation unit must be present in the utility schema

        if ( ! $this->verifyAggregationUnitTable($aggregationUnit) ) {
            $this->logger->notice("Aggregation unit not supported: '$aggregationUnit'");
            return false;
        }

        // --------------------------------------------------------------------------------
        // Create/alter the table for this aggregation unit. In dryrun mode, this simply prints out
        // debug information.

        // In order to properly manage the tables, we must perform variable substitution on column,
        // index, and trigger definitions as they may use the ${AGGREGATION_UNIT} macro. We can't do
        // this on the original table definition because we will need to substitute each aggregation
        // unit.

        $sqlList = array();

        foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {

            $qualifiedDestTableName = $etlTable->getFullName();
            $substitutedEtlAggregationTable = $etlTable->copyAndApplyVariables($this->variableMap);

            $this->manageTable($substitutedEtlAggregationTable, $this->destinationEndpoint);

            if ( $this->options->disable_keys && "myisam" == strtolower($etlTable->getEngine()) ) {
                $this->logger->info("Disable keys on $qualifiedDestTableName");
                $sqlList[] = "ALTER TABLE $qualifiedDestTableName DISABLE KEYS";
            }
        }

        $this->executeSqlList($sqlList, $this->destinationEndpoint, "Pre-aggregation unit tasks");

        return true;
    }  // performPreAggregationUnitTasks()

    /* ------------------------------------------------------------------------------------------
     * By default, there are no post-aggregation unit tasks.
     *
     * @see aAggregator::performPostAggregationUnitTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performPostAggregationUnitTasks($aggregationUnit, $numAggregationPeriodsProcessed)
    {
        $sqlList = array();

        foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {
            $qualifiedDestTableName = $etlTable->getFullName();

            if ( $numAggregationPeriodsProcessed > 0 ) {
                $sqlList[] = "OPTIMIZE TABLE $qualifiedDestTableName";
            }

            if ( $this->options->disable_keys && "myisam" == strtolower($etlTable->getEngine()) ) {
                $sqlList[] = "ALTER TABLE $qualifiedDestTableName ENABLE KEYS";
            }
        }

        $this->executeSqlList($sqlList, $this->destinationEndpoint, "Post-aggregation unit tasks");

        return true;

    }  // performPostAggregationUnitTasks()

    /* ------------------------------------------------------------------------------------------
     * Verify that the date id table exists for an aggregation period as well in the jobfactstatus
     * table.
     *
     * @param $aggregationUnit The unit that we are checking
     *
     * @return false if the table does not exist.
     *
     * @throws PDOException if there is an error querying the database.
     * ------------------------------------------------------------------------------------------
     */

    protected function verifyAggregationUnitTable($aggregationUnit)
    {
        $utilitySchema = $this->utilityEndpoint->getSchema();

        try {
            $tableName = $aggregationUnit . "s";
            $tableFullName =  $utilitySchema . "." . $tableName;
            if ( false === $this->utilityEndpoint->tableExists($tableName, $utilitySchema) ) {
                $this->logger->info("Table does not exist: '$tableFullName', skipping.");
                continue;
            }
        } catch (PDOException $e) {
            $this->logAndThrowException(
                "Error verifying aggregation unit table for '$aggregationUnit'",
                array('exception' => $e, 'sql' => $sql)
            );
        }

        return true;

    }  // verifyAggregationUnitTable()

    /* ------------------------------------------------------------------------------------------
     * This is the heart of the aggregation process and decides what actually gets aggregated.  Query
     * the database for the date ids that are dirty (i.e., those that have un-aggregated entries) for
     * each slice of the period being aggregated. Only ids containing entries that are waiting for
     * aggregation are returned.
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

        $dateRangeRestrictionSql = null;
        $minMaxJoin = null;
        $sourceSchema = $this->sourceEndpoint->getSchema(true);
        $utilitySchema = $this->utilityEndpoint->getSchema(true);

        // We use the first table in the join list to determine the last_modified values

        $firstTable = $this->etlSourceQueryOrigFromTable;

        $tableName = $this->sourceEndpoint->quoteSystemIdentifier($firstTable->getName());

        $aggregationPeriodQueryOptions = ( isset($this->parsedDefinitionFile->aggregation_period_query)
                                           ? $this->parsedDefinitionFile->aggregation_period_query
                                           : null );

        // In addition to the start and end timestamp for each record, we calculate the ids of the
        // daily aggregation period for these times (start_day_id and end_day_id). This is defined as:
        //
        // YEAR * 100000 + PERIOD, where PERIOD is the year, quarter, month, day, etc.
        // E.g., 201600020 is the 20th day of 2016, 201500002 for the 2nd quarter of 2015
        //
        // We can then convert to and from other supported aggregation periods ids as
        // needed. Compared to performing aggregation using (start_time_ts, end_time_ts), using
        // (start_day_id, end_day_id) speeds up XSEDE queries by an order of magnitude (avg
        // 6.52s/period vs avg 0.82s/period) and OSG queries by roughly 1.75.
        //
        // If a table to be aggregated does not explicitly store start_day_id and end_day_id for
        // each record, a conversion can be specified in the definition file. If a conversion has
        // been specified for the start_day_id and/or end_day_id, use it. Otherwise check the table
        // to see if they are present.

        $startDayIdField = null;
        $endDayIdField = null;

        // Use converstions if provided

        if ( isset($aggregationPeriodQueryOptions->conversions) ) {
            if ( isset($aggregationPeriodQueryOptions->conversions->start_day_id) ) {
                $startDayIdField = "(" . $aggregationPeriodQueryOptions->conversions->start_day_id . ")";
            }
            if ( isset($aggregationPeriodQueryOptions->conversions->end_day_id) ) {
                $endDayIdField = "(" . $aggregationPeriodQueryOptions->conversions->end_day_id . ")";
            }
        }

        // Verify table fields exist for any conversions not provided

        if ( null === $startDayIdField || null === $endDayIdField ) {

            $fromTable = Utilities::substituteVariables(
                $firstTable->getFullName(false),
                $this->variableMap,
                $this,
                "Undefined macros found in FROM table name"
            );

            $this->logger->debug("Discover table $fromTable");
            $firstTableDef = Table::discover($fromTable, $this->sourceEndpoint, null, $this->logger);

            // If we are in dryrun mode the table may not have been created yet but we still want to
            // be able to display the generated queries so simply set the start and end day id
            // fields.

            if ( false === $firstTableDef ) {
                if ( $this->getEtlOverseerOptions()->isDryrun() ) {
                    $startDayIdField = "start_day_id";
                    $endDayIdField = "end_day_id";
                } else {
                    $this->logAndThrowException("Table does not exist: $fromTable");
                }
            } else {

                $missing = array();

                if ( null === $startDayIdField && false === $firstTableDef->getColumn("start_day_id") ) {
                    $missing[] = "start_day_id";
                } else {
                    $startDayIdField = "start_day_id";
                }

                if ( null === $endDayIdField && false === $firstTableDef->getColumn("end_day_id") ) {
                    $missing[] = "end_day_id";
                } else {
                    $endDayIdField = "end_day_id";
                }

                if ( 0 != count($missing) ) {
                    $msg = "Table '$tableName' missing required field: (" . implode(", ", $missing) . ")";
                    $this->logAndThrowException($msg);
                }
            }

        }  // if ( null === $startDayIdField || null === $endDayIdField )

        // Conversion functions between days and other units

        $unitIdToStartDayId = null;
        $unitIdToEndDayId = null;
        $startDayIdToUnitId = null;
        $endDayIdToUnitId = null;

        switch ( $aggregationUnit ) {
            case 'day':
                // No conversion needed
                $unitIdToStartDayId = "d.id";
                $unitIdToEndDayId = "d.id";
                $startDayIdToUnitId = $startDayIdField;
                $endDayIdToUnitId = $endDayIdField;
                break;
            case 'month':
                $unitIdToStartDayId = "d.`year`*100000 + DAYOFYEAR(concat(d.`year`, '-', d.`month`, '-01'))";
                $unitIdToEndDayId = "d.`year`*100000 + DAYOFYEAR(date_sub(date_add(concat(d.`year`, '-', d.`month`, '-01'), interval 1 month), interval 1 day))";
                $startDayIdToUnitId = "truncate($startDayIdField/100000, 0)*100000 + "
                    . "MONTH(date_add(concat(truncate($startDayIdField/100000, 0), '-01-01'), interval (mod($startDayIdField, 1000) - 1) day))";
                $endDayIdToUnitId = "truncate($endDayIdField/100000, 0)*100000 + "
                    . "MONTH(date_add(concat(truncate($endDayIdField/100000, 0), '-01-01'), interval (mod($endDayIdField, 1000) - 1) day))";
                break;
            case 'quarter':
                $unitIdToStartDayId = "d.`year`*100000 + DAYOFYEAR(date_add(concat(d.`year`, '-01-01'), interval (d.`quarter` - 1) quarter))";
                $unitIdToEndDayId = "d.`year`*100000 + DAYOFYEAR(date_sub(date_add(concat(d.`year`, '-01-01'), interval d.`quarter` quarter), interval 1 day))";
                $startDayIdToUnitId = "truncate($startDayIdField/100000, 0)*100000 + "
                    . "QUARTER(date_add(concat(truncate($startDayIdField/100000, 0), '-01-01'), interval (mod($startDayIdField, 1000) - 1) day))";
                $endDayIdToUnitId = "truncate($endDayIdField/100000, 0)*100000 + "
                    . "QUARTER(date_add(concat(truncate($endDayIdField/100000, 0), '-01-01'), interval (mod($endDayIdField, 1000) - 1) day))";
                break;
            case 'year':
                $unitIdToStartDayId = "d.`year`*100000 + DAYOFYEAR(concat(d.`year`, '-01-01'))";
                $unitIdToEndDayId = "d.`year`*100000 + DAYOFYEAR(date_sub(date_add(concat(d.`year`, '-01-01'), interval 1 year), interval 1 day))";
                $startDayIdToUnitId = "truncate($startDayIdField/100000, 0)*100000";
                $endDayIdToUnitId = "truncate($endDayIdField/100000, 0)*100000";
                break;
            default:
                // We should never get here because we are verifying the existance of the aggregation
                // unit tables in performPreAggregationUnitTasks.
                $msg = "Unsupported aggregation unit '$aggregationUnit'";
                $this->logAndThrowException($msg);
                break;
        }  // switch ( $aggregationUnit )

        if ( $this->getEtlOverseerOptions()->isForce() ) {

            // If we are forcing aggregation for a specific time period, simply select all
            // aggregation periods that overlap the specified date range.

            if ( null === $this->currentStartDate && null === $this->currentEndDate ) {
                $this->logger->warning("Forced aggregation with no start or end date!");
            }

            $ranges = array();

            if ( null !== $this->currentStartDate ) {
                $startDate = $this->sourceHandle->quote($this->currentStartDate);
                $ranges[] = "$startDate <= d.${aggregationUnit}_end";
            }

            if ( null !== $this->currentEndDate ) {
                $endDate = $this->sourceHandle->quote($this->currentEndDate);
                $ranges[] = "$endDate >= d.${aggregationUnit}_start";
            }

            if ( 0 != count($ranges) ) {
                $dateRangeRestrictionSql = implode(" AND ", $ranges);
            }

        } else {

            // The standard aggregator uses the last_modified field to determine the records that
            // need to be processed. Find all records where last_modified falls into the specified
            // range and then find all aggregation periods that fall into the min(start) - max(end)
            // range for each of those records.  If the table does not have a last_modified column,
            // simply use all aggregation periods that fall within the specified range.

            // Construct a query to determine the periods that need to be aggregated. If no
            // restrictions are provided this will re-aggregate all records, which is resource
            // intensive for large number of records. The restrictions are specified in the
            // definition file.
            //
            // For example, if a last_modified field is present then it can be used to
            // determine records that need to be aggregated

            $query = (object) array(
                'records' => (object) array(
                    'start_period_id' => "DISTINCT $startDayIdToUnitId",
                    'end_period_id' => "$endDayIdToUnitId"
                ),
                'joins' => array(
                    (object) array(
                        'name' => $firstTable->getName(),
                        'schema' => $this->sourceEndpoint->getSchema()
                    )
                )
            );

            if ( isset($aggregationPeriodQueryOptions->overseer_restrictions) ) {
                $query->overseer_restrictions = $aggregationPeriodQueryOptions->overseer_restrictions;
            } else {
                $msg = "No restrictions on selection of periods to aggregate. "
                    . "Re-aggregating all records in " . $sourceSchema . "." . $tableName;
                $this->logger->notice($msg);
            }

            $recordRangeQuery = new Query($query, $this->sourceEndpoint->getSystemQuoteChar());
            $this->getEtlOverseerOptions()->applyOverseerRestrictions($recordRangeQuery, $this->utilityEndpoint, $this);

            $minMaxJoin = "( " . $recordRangeQuery->getSelectSql() . " ) record_ranges";
            $dateRangeRestrictionSql = "d.id BETWEEN record_ranges.start_period_id AND record_ranges.end_period_id";

        }  // else ( $this->getEtlOverseerOptions()->isForce() )

        // NOTE: The "ORDER BY 2 DESC, 3 DESC" is important because it allows most recent periods to
        // be aggregated first.

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
         $unitIdToStartDayId as period_start_day_id,
         $unitIdToEndDayId as period_end_day_id
       FROM {$utilitySchema}.${aggregationUnit}s d"
            . (null !== $minMaxJoin ? ",\n$minMaxJoin" : "" )
            . (null !== $dateRangeRestrictionSql ? "\nWHERE $dateRangeRestrictionSql" : "" ) . "
       ORDER BY 2 DESC, 3 DESC";

        // If we're running in DRYRUN mode return an empty array. This allows us to skip the aggregation
        // period loop.
        $result = array();

        try {
            $this->logger->debug("Select dirty aggregation periods SQL " . $this->sourceEndpoint . ":\n$sql");
            if ( ! $this->getEtlOverseerOptions()->isDryrun() ) {
                $result = $this->sourceHandle->query($sql);
            }
        } catch (PDOException $e) {
            $this->logAndThrowException(
                "Error querying dirty date ids",
                array('exception' => $e, 'sql' => $sql)
            );
        }

        return $result;

    }  // getDirtyAggregationPeriods()

    /* ------------------------------------------------------------------------------------------
     * Perform the actual aggregation for the specified aggregation unit.
     *
     * @param $aggregationUnit The current aggregation unit
     *
     * @return The number of aggregation periods processed
     *
     * @see aAggregator::_execute()
     * ------------------------------------------------------------------------------------------
     */

    protected function _execute($aggregationUnit)
    {
        $time_start = microtime(true);

        $this->logger->info(array(
            "message" => "aggregate start",
            "unit" => $aggregationUnit,
            "start_date" => ( null === $this->currentStartDate ? "none" : $this->currentStartDate ),
            "end_date" => ( null === $this->currentEndDate ? "none" : $this->currentEndDate )
        ));

        // Batching options


        // Get the list of periods that need to be aggregated
        $aggregationPeriodList = $this->getDirtyAggregationPeriods($aggregationUnit);
        $numAggregationPeriods = count($aggregationPeriodList);
        $firstPeriod = current($aggregationPeriodList);
        $periodSize = $firstPeriod['period_end_day_id'] - $firstPeriod['period_start_day_id'];
        $batchSliceSize = $this->options->experimental_batch_aggregation_periods_per_batch;

        // If aggregation batching is enabled, calculate whether or not it is beneficial using the
        // following formula derrived from trial and error:
        //
        // Nperiod = # periods to process = $numAggregationPeriods
        // Psize = # days per period = $period['period_end_day_id'] - $period['period_start_day_id']
        // Bsize = # Periods per batch = $this->options->experimental_batch_aggregation_periods_per_bin
        // Threshold1 = $this->options->experimental_batch_aggregation_min_num_periods = 25
        // Threshold2 = $this->options->experimental_batch_aggregation_max_num_days_per_batch = 300
        //
        // Enable = ( Nperiod >= Threshold1 (25) ) && ( Psize * Bsize <= Threshold2 (300) )

        $enableBatchAggregation =
            $this->options->experimental_enable_batch_aggregation
            && $numAggregationPeriods >= $this->options->experimental_batch_aggregation_min_num_periods
            && ($periodSize * $batchSliceSize) <= $this->options->experimental_batch_aggregation_max_num_days_per_batch;

        $this->logger->debug("[EXPERIMENTAL] Enable batch aggregation: " . ($enableBatchAggregation ? "true" : "false"));

        if ( $enableBatchAggregation ) {
            $tmpTableName = self::BATCH_TMP_TABLE_NAME;
            $qualifiedTmpTableName = $this->sourceEndpoint->getSchema(true) . "." . $this->sourceEndpoint->quoteSystemIdentifier($tmpTableName);
        }

        if ( $enableBatchAggregation && ! $this->etlSourceQueryModified ) {

            $this->logger->info("[EXPERIMENTAL] Replace first table with temp table");

            // Optimize for large numbers of periods. Modify the source query to use the temporary table
            // Remove the first join (from) and replace it with the temporary table that we are
            // going to create

            $sourceJoins = $this->etlSourceQuery->getJoins();
            $firstJoin = array_shift($sourceJoins);
            $newFirstJoin = clone $firstJoin;
            $newFirstJoin->setName($tmpTableName);
            $newFirstJoin->setSchema($this->sourceEndpoint->getSchema());

            $this->etlSourceQuery->deleteJoins();
            $this->etlSourceQuery->addJoin($newFirstJoin);
            foreach ( $sourceJoins as $join ) {
                $this->etlSourceQuery->addJoin($join);
            }
            $this->etlSourceQueryModified = true;

        } elseif ( ! $enableBatchAggregation && $this->etlSourceQueryModified ) {

            $this->logger->info("[EXPERIMENTAL] Restore original first table");

            // We are not optimizing but have previously, restore the original FROM clause

            $sourceJoins = $this->etlSourceQuery->getJoins();
            array_shift($sourceJoins);
            $this->etlSourceQuery->deleteJoins();
            $this->etlSourceQuery->addJoin($this->etlSourceQueryOrigFromTable);
            foreach ( $sourceJoins as $join ) {
                $this->etlSourceQuery->addJoin($join);
            }
            $this->etlSourceQueryModified = false;
        }  // else ( $enableBatchAggregation && ! $this->etlSourceQueryModified )

        $this->buildSqlStatements($aggregationUnit);

        // ------------------------------------------------------------------------------------------
        // Set up the select and insert statements used for aggregation and determine if we can
        // optimized the operation.

        $bindParamRegex = '/(:[a-zA-Z0-9_-]+)/';
        $discoveredBindParams = array();

        $selectStmt = null;
        $insertStmt = null;

        $optimize = $this->allowSingleDatabaseOptimization();

        if ( $optimize ) {

            $this->logger->info("Allowing same-server SQL optimizations");

            try {
                $insertStmt =  $this->destinationHandle->prepare($this->optimizedInsertSql);
            } catch (PDOException $e) {
                $this->logAndThrowException(
                    "Error preparing optimized aggregation insert statement",
                    array('exception' => $e, 'sql' => $this->optimizedInsertSql)
                );
            }

            // Detect the bind variables used in the query so we can filter these later. PDO will
            // throw an error if there are unused bind variables.

            $matches = array();
            preg_match_all($bindParamRegex, $this->optimizedInsertSql, $matches);
            $discoveredBindParams['insert'] = array_unique($matches[0]);

            $this->logger->debug("Aggregation optimized INSERT SQL ($aggregationUnit) " . $this->destinationEndpoint . ":\n" . $this->optimizedInsertSql);

        } else {

            try {
                $selectStmt =  $this->sourceHandle->prepare($this->selectSql);
            } catch (PDOException $e) {
                $this->logAndThrowException(
                    "Error preparing aggregation select statement",
                    array('exception' => $e, 'sql' => $this->selectSql)
                );
            }

            try {
                $insertStmt =  $this->destinationHandle->prepare($this->insertSql);
            } catch (PDOException $e) {
                $this->logAndThrowException(
                    "Error preparing aggregation insert statement",
                    array('exception' => $e, 'sql' => $this->insertSql)
                );
            }

            // Detect the bind variables used in the query so we can filter these later. PDO will
            // throw an error if there are unused bind variables.

            $matches = array();
            preg_match_all($bindParamRegex, $this->selectSql, $matches);
            $discoveredBindParams['select'] = $matches[0];
            preg_match_all($bindParamRegex, $this->insertSql, $matches);
            $discoveredBindParams['insert'] = $matches[0];

            $this->logger->debug("Aggregation SELECT SQL ($aggregationUnit) " . $this->sourceEndpoint . ":\n" . $this->selectSql);
            $this->logger->debug("Aggregation INSERT SQL ($aggregationUnit) " . $this->destinationEndpoint . ":\n" . $this->insertSql);

        }  // else ($optimize)

        // --------------------------------------------------------------------------------
        // Iterate over each aggregation period that we are processing.
        //
        // NOTE: The ETL date range is supported when querying for dirty aggregation periods

        $this->logger->info("Aggregate over $numAggregationPeriods ${aggregationUnit}s");

        if ( ! $enableBatchAggregation ) {

            $this->processAggregationPeriods(
                $aggregationUnit,
                $aggregationPeriodList,
                $selectStmt,
                $insertStmt,
                $discoveredBindParams,
                $numAggregationPeriods
            );

        } else {

            $aggregationPeriodListOffset = 0;
            $done = false;

            $sourceJoins = $this->etlSourceQuery->getJoins();
            $firstJoin = current($sourceJoins);
            $tmpTableAlias = $firstJoin->getAlias();

            while ( ! $done ) {

                // Process the aggregation periods in batches

                $this->logger->debug("[EXPERIMENTAL] Processing batch offset $aggregationPeriodListOffset - " . ($aggregationPeriodListOffset + $batchSliceSize));

                $batchStartTime = microtime(true);

                $aggregationPeriodSlice = array_slice($aggregationPeriodList, $aggregationPeriodListOffset, $batchSliceSize);
                if ( count($aggregationPeriodSlice) == 0 ) {
                    break;
                }

                // Is this the last slice?
                $done = ( count($aggregationPeriodSlice) < $batchSliceSize );

                // Find the min/max time range and day id for this slice so we know which jobs to
                // include in the temporary table.

                $firstSlice = current($aggregationPeriodSlice);
                $lastSlice = end($aggregationPeriodSlice);
                reset($aggregationPeriodSlice);

                // Note that slices are ordered newest to oldest

                $minDayId = $lastSlice['period_start_day_id'];
                $maxDayId = $firstSlice['period_end_day_id'];
                $minPeriodId = $lastSlice['period_id'];
                $maxPeriodId = $firstSlice['period_id'];

                // Set up the temporary table that we are going to use

                $this->logger->debug("[EXPERIMENTAL] Create temporary table $qualifiedTmpTableName with min period = $minDayId, max period = $maxDayId");

                try {
                    $sql = "DROP TEMPORARY TABLE IF EXISTS $qualifiedTmpTableName";
                    $result = $this->sourceHandle->execute($sql);
                } catch (PDOException $e ) {
                    $this->logAndThrowException(
                        "Error removing temporary batch aggregation table",
                        array('exception' => $e, 'sql' => $sql)
                    );
                }

                $origTableName =
                    $this->sourceEndpoint->getSchema(true)
                    . "."
                    . $this->sourceEndpoint->quoteSystemIdentifier($this->etlSourceQueryOrigFromTable->getName());

                try {
                    // Use the where clause from the aggregation query to create the temporary table

                    $whereClause = implode(" AND ", $this->etlSourceQuery->getWheres());

                    $whereClause = Utilities::substituteVariables(
                        $whereClause,
                        $this->variableMap,
                        $this,
                        "Undefined macros found in WHERE clause"
                    );

                    // A subset of the bind variables are available. We should check to see if there
                    // are more that we can't handle.

                    // We are taking the WHERE clause from source query so we need to
                    // support all bind parameters available to that query. Min and max
                    // information will need to come from the last and first slice,
                    // respecitively (see note below).

                    $availableParamKeys = Utilities::createPdoBindVarsFromArrayKeys($firstSlice);

                    // NOTE 1
                    //
                    // The aggregation periods are supplied in reverse order so newer
                    // periods are processed first. This means we need to invert the
                    // start/end slices.
                    //
                    // NOTE 2
                    //
                    // Since the WHERE clause was meant to be used with a SINGLE
                    // aggregation period, parameters such as period_hours and
                    // period_seconds are not summed and chosen arbitrarily from one of
                    // the periods.  Similarly, period_id and period_value are chosen
                    // arbitrarily.

                    $availableParamValues = array_map(
                        function ($k, $first, $last) {
                            if ( false !== strpos($k, '_end') || false !== strpos($k, 'end_') ) {
                                return $first;
                            } else {
                                return $last;
                            }
                        },
                        array_keys($firstSlice),
                        $firstSlice,
                        $lastSlice
                    );

                    $availableParams = array_combine($availableParamKeys, $availableParamValues);

                    $bindParams = array();
                    preg_match_all($bindParamRegex, $whereClause, $matches);
                    $bindParams = $matches[0];
                    $usedParams = array_intersect_key($availableParams, array_fill_keys($bindParams, 0));

                    $sql =
                        "CREATE TEMPORARY TABLE $qualifiedTmpTableName AS "
                        . "SELECT * FROM $origTableName $tmpTableAlias WHERE " . $whereClause;
                    $this->logger->debug("[EXPERIMENTAL] Batch temp table " . $this->sourceEndpoint . ": $sql");
                    $result = $this->sourceHandle->execute($sql, $usedParams);
                } catch (PDOException $e ) {
                    $this->logAndThrowException(
                        "Error creating temporary batch aggregation table",
                        array('exception' => $e, 'sql' => $sql)
                    );
                }

                $this->logger->info("[EXPERIMENTAL] Setup for batch $minPeriodId - $maxPeriodId (day_id $minDayId - $maxDayId): "
                                    . round((microtime(true) - $batchStartTime), 2) . "s");

                $this->processAggregationPeriods(
                    $aggregationUnit,
                    $aggregationPeriodSlice,
                    $selectStmt,
                    $insertStmt,
                    $discoveredBindParams,
                    $numAggregationPeriods,
                    $aggregationPeriodListOffset
                );

                $this->logger->info(
                    "[EXPERIMENTAL] Total time for batch (day_id $minDayId - $maxDayId): "
                    . round((microtime(true) - $batchStartTime), 2) . "s "
                    . "(" . round((microtime(true) - $batchStartTime) / count($aggregationPeriodSlice), 3) . "s/period)"
                );

                $aggregationPeriodListOffset += $batchSliceSize;

            }  // while ( ! $done )

            try {
                $sql = "DROP TEMPORARY TABLE IF EXISTS $tmpTableName";
                $result = $this->sourceHandle->execute($sql);
            } catch (PDOException $e ) {
                $this->logAndThrowException(
                    "Error removing temporary batch aggregation table",
                    array('exception' => $e, 'sql' => $sql)
                );
            }

        }  // else ( ! $enableBatchAggregation )

        $time_end = microtime(true);
        $time = $time_end - $time_start;

        $this->logger->notice(array("message"      => "aggregate end",
                                    "unit"         => $aggregationUnit,
                                    "periods"      => $numAggregationPeriods,
                                    "start_date"   => ( null === $this->currentStartDate ? "none" : $this->currentStartDate ),
                                    "end_date"     => ( null === $this->currentEndDate ? "none" : $this->currentEndDate ),
                                    "start_time"   => $time_start,
                                    "end_time"     => $time_end,
                                    "elapsed_time" => round($time, 5)
        ));

        return $numAggregationPeriods;

    }  // _execute()

    /* ------------------------------------------------------------------------------------------
     * Process the individual aggregation periods. This includes selecting the data from the source
     * table and inserting it in the aggregation table.
     *
     * @param $aggregationUnit The current aggregation unit
     * @param $aggregationPeriodList An array of information about the dirty aggregation periods to
     *   be processed.
     * @param $selectStmt A PDOStatement representing the select portion of the query. This may be
     *   NULL if we are using an optimized query.
     * @param $insertStmt A PDOStatement representing the insert portion of the query. If using an
     *   optimized query this also includes the SELECT portion.
     * @param $discoveredBindParams An associative array containing the bind parameters discovered
     *   in each of the queries. Valid keys are 'select' and 'insert'.
     * @param $totalNumAggregationPeriods The total number of aggregation periods to process
     * @param $aggregationPeriodOffset The offset into the total number of aggregation periods
     *   processed by this invocation.
     *
     * @return The number of aggregation periods processed
     *
     * @see aAggregator::_execute()
     * ------------------------------------------------------------------------------------------
     */

    protected function processAggregationPeriods(
        $aggregationUnit,
        array $aggregationPeriodList,
        PDOStatement $selectStmt = null,
        PDOStatement $insertStmt,
        array $discoveredBindParams,
        $totalNumAggregationPeriods,
        $aggregationPeriodOffset = 0
    ) {
        if ( $this->getEtlOverseerOptions()->isDryrun() ) {
            return 0;
        }

        $optimize = $this->allowSingleDatabaseOptimization();
        $numPeriodsProcessed = 0;

        foreach ($aggregationPeriodList as $aggregationPeriodInfo) {
            $dateIdStartTime = microtime(true);
            $numRecords = 0;

            // Make all of the data for each aggregation period available to the
            // query. Change the array keys into bind parameters.

            $availableParamKeys = Utilities::createPdoBindVarsFromArrayKeys($aggregationPeriodInfo);
            $availableParams = array_combine($availableParamKeys, $aggregationPeriodInfo);
            $periodId = $aggregationPeriodInfo['period_id'];

            // If we're not completely re-aggregating, delete existing entries from the aggregation table
            // matching the periods that we are aggregating. Be sure to restrict resources if necessary.

            if ( ! $this->options->truncate_destination ) {
                try {

                    $restrictions = array();

                    if ( isset($this->parsedDefinitionFile->destination_query)
                         && isset($this->parsedDefinitionFile->destination_query->overseer_restrictions) )
                    {
                        // The destination query block allows us to specify overseer restrictions
                        // that apply to operations on the destination table (e.g., deleting records
                        // from the table during aggregation). Create a dummy query object using the
                        // overseer restrictions from the destination_query block so we can apply
                        // the same restrictions to the delete query as specified in the config
                        // file.

                        $query = (object) array(
                            'records' => (object) array('junk' => 0),
                            'joins' => array( (object) array('name' => "table", 'schema' => "schema") ),
                            'overseer_restrictions' => $this->parsedDefinitionFile->destination_query->overseer_restrictions
                        );

                        $dummyQuery = new Query($query, $this->destinationEndpoint->getSystemQuoteChar(), $this->logger);
                        $this->getEtlOverseerOptions()->applyOverseerRestrictions($dummyQuery, $this->utilityEndpoint, $this);
                        $restrictions = $dummyQuery->getOverseerRestrictionValues();
                    }  // if ( isset($this->parsedDefinitionFile->destination_query) ... )

                    foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {
                        $qualifiedDestTableName = $etlTable->getFullName();

                        // This will need to get switch back once we start using period_id rather than
                        // month_id, year_id, etc.
                        // $deleteSql = "DELETE FROM {$this->qualifiedDestTableName} WHERE period_id = $period_id";

                        $deleteSql = "DELETE FROM $qualifiedDestTableName WHERE {$aggregationUnit}_id = $periodId";

                        if ( count($restrictions) > 0 ) {
                            $deleteSql .= " AND " . implode(" AND ", $dummyQuery->getOverseerRestrictionValues());
                        }

                        $this->logger->debug("Delete aggregation unit SQL " . $this->destinationEndpoint . ":\n$deleteSql");
                        $this->destinationHandle->execute($deleteSql);
                    }

                } catch (PDOException $e ) {
                    $this->logAndThrowException(
                        "Error removing existing aggregation data",
                        array('exception' => $e, 'sql' => $deleteSql)
                    );
                }
            }  // if ( ! $this->options->truncate_destination )

            // Perform aggregation on this aggregation period

            $this->logger->debug("Aggregating $aggregationUnit $periodId");

            if ( $optimize ) {

                try {
                    if ( ! $this->getEtlOverseerOptions()->isDryrun() ) {
                        $bindParams = array_intersect_key($availableParams, array_fill_keys($discoveredBindParams['insert'], 0));
                        $insertStmt->execute($bindParams);
                        $numRecords = $insertStmt->rowCount();
                    }
                } catch (PDOException $e ) {
                    $this->logAndThrowException(
                        "Error processing aggregation period",
                        array('exception' => $e, 'sql' => $this->optimizedInsertSql)
                    );
                }

            } else {

                // Query the source table and put the results into the destination table in 2 steps

                try {
                    $bindParams = array_intersect_key($availableParams, array_fill_keys($discoveredBindParams['select'], 0));
                    $selectStmt->execute($bindParams);
                    $numRecords = $selectStmt->rowCount();
                } catch (PDOException $e ) {
                    $this->logAndThrowException(
                        "Error selecting raw job data",
                        array('exception' => $e, 'sql' => $this->selectSql)
                    );

                }

                $msg = array(
                    "unit"        => $aggregationUnit,
                    "num_records" => $numRecords
                );
                $this->logger->debug(array_merge($msg, $aggregationPeriodInfo));

                // Insert the new rows.

                try {
                    while ($row = $selectStmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) {
                        $insertStmt->execute($row);
                    }
                } catch (PDOException $e ) {
                    $this->logAndThrowException(
                        "Error inserting aggregated data",
                        array('exception' => $e, 'sql' => $this->insertSql)
                    );
                }

            }  // else ( $optimize )

            $numPeriodsProcessed++;
            $this->logger->info("Aggregated $aggregationUnit ("
                                . ( $numPeriodsProcessed + $aggregationPeriodOffset)
                                . "/"
                                . $totalNumAggregationPeriods
                                . ") $periodId records = $numRecords, time = " .
                                round((microtime(true) - $dateIdStartTime), 2) . "s");

        }  // foreach ($aggregationPeriodList as $aggregationPeriodInfo)

        return $numPeriodsProcessed;

    }  // processAggregationPeriods()

    /* ------------------------------------------------------------------------------------------
     * Determine if our source and destination databases are the same and we can enable query
     * optimization. This is done using the host and port for each connection. If they are the same,
     * we may be able to perform an "INSERT...SELECT" directly in the database rather than a SELECT
     * returning the data and then a separate INSERT.
     *
     * @return true If both the source and destination are the same server.
     * ------------------------------------------------------------------------------------------
     */

    protected function allowSingleDatabaseOptimization()
    {
        if ( ! $this->options->optimize_query ) {
            return false;
        }
        if ( ! $this->sourceEndpoint->getType() == $this->destinationEndpoint->getType() ) {
            return false;
        }

        return $this->sourceEndpoint->isSameServer($this->destinationEndpoint);

    }  // allowSingleDatabaseOptimization()

    /* ------------------------------------------------------------------------------------------
     * Build the INSERT, SELECT, and INSERT INTO ... SELECT statements for the aggregation
     * query. Note that the list of fields may contain PDO parameter references.
     *
     * @param $aggregationUnit The current aggregation unit
     * @param $includeSchema TRUE if the schema should be included in table names
     *
     * @return TRUE on success, FALSE on failure
     * ------------------------------------------------------------------------------------------
     */

    protected function buildSqlStatements($aggregationUnit, $includeSchema = true)
    {

        // Build the statements for this aggregation unit. The source query may contain variables that,
        // when substituted, will result in duplicate column names (e.g., "year" in the aggregation
        // tables). Remove duplicates, keeping only the first one, and add them back to the query after
        // generating the SQL.

        // *** Should this functionality be included in the Query itself? ***

        $sourceRecords = $this->etlSourceQuery->getRecords();

        $substitutedRecordNames = array();
        $duplicateRecords = array();

        foreach ( $sourceRecords as $name => $formula ) {
            $substitutedName = Utilities::substituteVariables($name, $this->variableMap);

            if ( in_array($substitutedName, $substitutedRecordNames) ) {
                $duplicateRecords[$name] = $this->etlSourceQuery->removeRecord($name);
                $msg = "Duplicate column after substitution: (\"$name: $formula\") '$name' -> '$substitutedName'";

                // Note that we are logging duplicate year columns differently because it is known
                // there will be a duplicate year in the *_by_year aggregation tables at the
                // moment. This will be fixed in the future.

                if ( 'year' == $substitutedName ) {
                    $this->logger->notice($msg);
                } else {
                    $this->logger->warning($msg);
                }

            } else {
                $substitutedRecordNames[] = $substitutedName;
            }
        }

        $this->selectSql = $this->etlSourceQuery->getSelectSql($includeSchema);

        $this->insertSql = "INSERT INTO " . $this->etlDestinationTable->getFullName($includeSchema) . "\n" .
            "("
            . implode(",\n", array_keys($this->etlSourceQuery->getRecords()))
            . ")\nVALUES\n("
            . implode(",\n", Utilities::createPdoBindVarsFromArrayKeys($this->etlSourceQuery->getRecords()))
            . ")";

        $this->optimizedInsertSql = "INSERT INTO " . $this->etlDestinationTable->getFullName($includeSchema) . "\n" .
            "(" .
            implode(",\n", array_keys($this->etlSourceQuery->getRecords()))
            . ")\n" .
            $this->selectSql;

        if ( null !== $this->variableMap ) {

            $this->selectSql = Utilities::substituteVariables(
                $this->selectSql,
                $this->variableMap,
                $this,
                "Undefined macros found in select SQL"
            );

            $this->insertSql = Utilities::substituteVariables(
                $this->insertSql,
                $this->variableMap,
                $this,
                "Undefined macros found in insert SQL"
            );

            $this->optimizedInsertSql = Utilities::substituteVariables(
                $this->optimizedInsertSql,
                $this->variableMap,
                $this,
                "Undefined macros found in optimized insert SQL"
            );

        }

        // Put any records that we removed back into the Query

        foreach ( $duplicateRecords as $record => $formula) {
            $this->etlSourceQuery->addRecord($record, $formula);
        }

        return true;

    }  // buildSqlStatements()
}  // class pdoAggregator
