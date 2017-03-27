<?php
/* ------------------------------------------------------------------------------------------
 * Ingestor for data loaded from a file. The defintion file contains a list
 * of column names and data records to be loaded (either in that file or in an
 * external file).
 *
 * @author Steve Gallo 2015-11-11
 * ------------------------------------------------------------------------------------------
 */

namespace ETL\Ingestor;

use stdClass;

use ETL\iAction;
use ETL\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\aRdbmsDestinationAction;
use ETL\aOptions;
use ETL\DataEndpoint\StructuredFile;
use \Log;

class StructuredFileIngestor extends aIngestor implements iAction
{
    /**
     * Execution data set up by this ingestor's pre-execution tasks.
     *
     * The pre-execution function creates an array with these keys:
     *   - destColumns: A numeric array of destination columns.
     *   - destColumnsToSourceKeys: An array mapping destination columns to
     *                              the corresponding keys in the source data.
     *   - sourceValues: An array of values to be ingested during execution.
     *   - customInsertValuesComponents: An object containing replacement SQL
     *                                   for the standard placeholders in the
     *                                   INSERT statement for the destination
     *                                   columns specified in the
     *                                   configuration file.
     *
     * @var array
     */
    protected $executionData;

    // This action does not (yet) support multiple destination tables. If multiple destination
    // tables are present, store the first here and use it.
    protected $etlDestinationTable = null;

    /* ------------------------------------------------------------------------------------------
     * @see iAction::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Initialize data required to perform the action. This should be called after the constructor and
     * as part of the verification process.
     *
     * @throws Exception if any query data was not int the correct format.
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(EtlOverseerOptions $etlOverseerOptions = null)
    {
        if ( $this->isInitialized() ) {
            return;
        }

        $this->initialized = false;

        parent::initialize($etlOverseerOptions);

        // This ingestor supports an explicit source data endpoint of StructuredFile or
        // JSON data specified directly in the definition file using the source_values
        // key. If the source_values key is present, ignore the source endpoint as the
        // data will have already been parsed.

        if ( isset($this->parsedDefinitionFile->source_values) ) {
            $this->sourceEndpoint = null;
            $this->sourceHandle = null;
        } elseif ( $this->options->source !== null && ! $this->options->ignore_source ) {
            if ( ! $this->sourceEndpoint instanceof StructuredFile ) {
                $msg = "Source is not an instance of ETL\\DataEndpoint\\StructuredFile";
                $this->logAndThrowException($msg);
            }
        }

        // This action only supports 1 destination table so use the first one and log a warning if
        // there are multiple.

        reset($this->etlDestinationTableList);
        $this->etlDestinationTable = current($this->etlDestinationTableList);
        $etlTableKey = key($this->etlDestinationTableList);
        if ( count($this->etlDestinationTableList) > 1 ) {
            $msg = $this . " does not support multiple ETL destination tables, using first table with key: '$etlTableKey'";
            $logger->warning($msg);
        }

        if ( ! isset($this->parsedDefinitionFile->destination_columns) ) {
            $msg = "destination_columns key not present in definition file: " . $this->definitionFile;
            $this->logAndThrowException($msg);
        }

        $destinationColumns = $this->parsedDefinitionFile->destination_columns;
        if (is_object($destinationColumns)) {
            $destinationColumns = array_keys(get_object_vars(
                $destinationColumns
            ));
        } elseif (! is_array($destinationColumns)) {
            $msg = "destination_columns is invalid format: " . $this->definitionFile;
            $this->logAndThrowException($msg);
        }

        if (
            ! $this->sourceEndpoint
            && ! isset($this->parsedDefinitionFile->source_values)
        ) {
            $msg = "source file not configured and (default) source values not in definition file";
            $this->logAndThrowException($msg);
        }

        // Verify that the columns exist
        $missingColumnNames = array_diff(
            $destinationColumns,
            $this->etlDestinationTable->getColumnNames()
        );

        if ( 0 != count($missingColumnNames) ) {
            $msg = "The following columns from the data file were not found in table " . $this->etlDestinationTable->getFullName() . ": " .
                implode(", ", $missingColumnNames);
            $this->logAndThrowException($msg);
        }

        $this->initialized = true;

        return true;

    }  // initialize()

    /**
     * @see aIngestor::_execute()
     */
    protected function _execute()
    {
        $destColumns = $this->executionData['destColumns'];
        $destColumnsToSourceKeys = $this->executionData['destColumnsToSourceKeys'];
        $sourceValues = $this->executionData['sourceValues'];
        $customInsertValuesComponents = $this->executionData['customInsertValuesComponents'];

        $numColumns = count($destColumns);
        $numRecords = count($sourceValues);

        // Insert data for each column.
        //
        // NOTE: Null values will not overwrite non-null values in the database.
        // This is done to handle destinations that can be populated by
        // multiple sources with varying levels of detail.
        $valuesComponents = array_map(function ($destColumn) use ($customInsertValuesComponents) {
            return (
                property_exists($customInsertValuesComponents, $destColumn)
                ? $customInsertValuesComponents->$destColumn
                : '?'
            );
        }, $destColumns);

        $sql = "INSERT INTO " . $this->etlDestinationTable->getFullName() . " (" .
            implode(",", $destColumns) .
            ") VALUES (" .
            implode(",", $valuesComponents) .
            ") ON DUPLICATE KEY UPDATE " .
            implode(", ", array_map(function ($destColumn) {
                return "$destColumn = COALESCE(VALUES($destColumn), $destColumn)";
            }, $destColumns))
        ;

        $this->logger->debug("Insert query " . $this->destinationEndpoint . ":\n$sql");

        if ( ! $this->getEtlOverseerOptions()->isDryrun() ) {
            try {
                $insertStatement = $this->destinationHandle->prepare($sql);

                foreach ( $sourceValues as $sourceValue ) {
                    $insertStatement->execute($this->convertSourceValueToRow(
                        $sourceValue,
                        $destColumns,
                        $destColumnsToSourceKeys
                    ));
                }
            } catch (PDOException $e) {
                $this->logAndThrowException(
                    "Error inserting file data",
                    array('exception' => $e, 'endpoint' => $this)
                );
            }
        }

        return $numRecords;
    }  // execute()

    /**
     * @see aIngestor::performPreExecuteTasks
     */
    protected function performPreExecuteTasks()
    {
        $this->time_start = microtime(true);

        $this->initialize($this->getEtlOverseerOptions());

        $this->manageTable($this->etlDestinationTable, $this->destinationEndpoint);

        $this->truncateDestination();

        // The destination columns may be specified as an array when the source
        // values are given as arrays and an object for when the source values
        // are given as objects. Regardless of form, get a list of destination
        // columns and a mapping of those columns to their corresponding keys in
        // source values.
        $destColumns = $this->parsedDefinitionFile->destination_columns;
        if (is_array($destColumns)) {
            $destColumnsToSourceKeys = array_flip($destColumns);
            if ($destColumnsToSourceKeys === null) {
                $msg = "destination_columns is an invalid array: " . $this->definitionFile;
                $this->logAndThrowException($msg);
            }
        } else {
            $destColumnsToSourceKeys = (array) $destColumns;
            $destColumns = array_keys($destColumnsToSourceKeys);
        }

        // If a source data endpoint was given, use it. Otherwise, use data
        // values specified in the definition file.
        if ($this->sourceEndpoint) {
            $sourceValues = $this->sourceEndpoint->parse();
        } else {
            $sourceValues = $this->parsedDefinitionFile->source_values;
        }

        // If any custom SQL fragments for insertion were specified, use them.
        $customInsertValuesComponents = $this->parsedDefinitionFile->custom_insert_values_components;
        if ($customInsertValuesComponents === null) {
            $customInsertValuesComponents = new stdClass();
        }

        $this->executionData = array(
            'destColumns' => $destColumns,
            'destColumnsToSourceKeys' => $destColumnsToSourceKeys,
            'sourceValues' => $sourceValues,
            'customInsertValuesComponents' => $customInsertValuesComponents,
        );

        return true;
    }

    /**
     * @see aIngestor::performPostExecuteTasks
     */
    protected function performPostExecuteTasks($numRecords = null)
    {
        $time_start = $this->time_start;
        $time_end = microtime(true);
        $time = $time_end - $time_start;

        $logArray = array(
            'action'         => (string) $this,
            'start_time'     => $time_start,
            'end_time'       => $time_end,
            'elapsed_time'   => round($time, 5),
        );

        if ($numRecords !== null) {
            $logArray['records_loaded'] = $numRecords;
        }

        $this->logger->notice($logArray);

        return true;
    }

    /**
     * Convert a given data point into a set of values for database insertion.
     *
     * NOTE: Values not found in the data point will be treated as null.
     *
     * @param  array|stdClass $sourceValue     The data point to convert.
     * @param  array $destColumns              An ordered list of columns an
     *                                         INSERT statement is expected
     *                                         to be provided values for.
     * @param  array $destColumnsToSourceKeys  A mapping of columns to their
     *                                         corresponding keys in the data.
     * @return array                           A set of values ready to be
     *                                         used with an INSERT statement.
     */
    protected function convertSourceValueToRow(
        $sourceValue,
        $destColumns,
        $destColumnsToSourceKeys
    ) {
        $row = array();
        foreach ($destColumns as $destColumn) {
            $sourceKey = $destColumnsToSourceKeys[$destColumn];

            // If the key is an integer, then the data point should be an array.
            // Otherwise, the data point should be an object.
            if (is_int($sourceKey)) {
                $row[] = (
                    isset($sourceValue[$sourceKey])
                    ? $sourceValue[$sourceKey]
                    : null
                );
            } else {
                $row[] = (
                    property_exists($sourceValue, $sourceKey)
                    ? $sourceValue->$sourceKey
                    : null
                );
            }
        }
        return $row;
    }
}  // class StructuredFileIngestor
