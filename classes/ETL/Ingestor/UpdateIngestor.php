<?php
/* ------------------------------------------------------------------------------------------
 * Ingestor for updating specific columns in a table.
 *
 * @author Steve Gallo 2016-06-16
 * ------------------------------------------------------------------------------------------
 */

namespace ETL\Ingestor;

// PEAR logger
use \Log;

use ETL\iAction;
use ETL\Configuration;
use ETL\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\aRdbmsDestinationAction;
use ETL\aOptions;
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;

class UpdateIngestor extends aRdbmsDestinationAction implements iAction
{
    // Data parsed from the source JSON file or inline from the source_data definition
    protected $data = null;

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

        if ( ! $options instanceof IngestorOptions ) {
            $msg = "Options is not an instance of IngestorOptions";
            $this->logAndThrowException($msg);
        }

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::initialize()
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(EtlOverseerOptions $etlOverseerOptions = null)
    {
        if ( $this->isInitialized() ) {
            return;
        }

        $this->initialized = false;

        parent::initialize($etlOverseerOptions);

        // This action only supports 1 destination table so use the first one and log a warning if
        // there are multiple.

        reset($this->etlDestinationTableList);
        $this->etlDestinationTable = current($this->etlDestinationTableList);
        $etlTableKey = key($this->etlDestinationTableList);
        if ( count($this->etlDestinationTableList) > 1 ) {
            $msg = $this . " does not support multiple ETL destination tables, using first table with key: '$etlTableKey'";
            $logger->warning($msg);
        }

        // Verify that we have a properly formatted "source_data" and "update" property

        $requiredKeys = array('source_data', 'update');

        foreach ( $requiredKeys as $key ) {
            if ( ! isset($this->parsedDefinitionFile->$key) ) {
                $msg = "'$key' key missing in definition file: " . $this->definitionFile;
                $this->logAndThrowException($msg);
            }
        }

        if ( ! isset($this->parsedDefinitionFile->update->set) || ! is_array($this->parsedDefinitionFile->update->set) ) {
            $msg = "'set' key missing or not an array in 'update' block: " . $this->definitionFile;
            $this->logAndThrowException($msg);
        }

        if ( ! isset($this->parsedDefinitionFile->update->where) || ! is_array($this->parsedDefinitionFile->update->where) ) {
            $msg = "'where' key missing or not an array in 'update' block: " . $this->definitionFile;
            $this->logAndThrowException($msg);
        }

        if ( ! isset($this->parsedDefinitionFile->source_data->data) ) {
            $msg = "'data' key missing in in 'source_data' block: " . $this->definitionFile;
            $this->logAndThrowException($msg);
        }

        if ( ! isset($this->parsedDefinitionFile->source_data->fields) || ! is_array($this->parsedDefinitionFile->source_data->fields) ) {
            $msg = "'fields' key missing or not an array in 'source_data' block: " . $this->definitionFile;
            $this->logAndThrowException($msg);
        }

        // 2. Create data & verify source. Data source is iteratable. Instantiate object based on columns and data.
        // 3. In execute()
        //    a. Construct prepared update statement
        //    b. Iterate over data source and execute statement

        // Merge source data fields with update set and where fields and ensure that they exist in
        // the table definition

        $updateColumns = array_merge(
            $this->parsedDefinitionFile->update->set,
            $this->parsedDefinitionFile->update->where,
            $this->parsedDefinitionFile->source_data->fields
        );

        $missingColumnNames = array_diff(
            array_unique($updateColumns),
            $this->etlDestinationTable->getColumnNames()
        );

        if ( 0 != count($missingColumnNames) ) {
            $msg = "The following columns from the update configuration were not " .
                "found in table " . $this->etlDestinationTable->getFullName() . ": " .
                implode(", ", $missingColumnNames);
            $this->logAndThrowException($msg);
        }

        // If the data is a string assume it is a filename, otherwise assume it is parsed JSON.

        if ( is_string($this->parsedDefinitionFile->source_data->data) ) {
            $filename = $this->parsedDefinitionFile->source_data->data;
            $filename = \xd_utilities\qualify_path($filename, $this->options->paths->base_dir);

            $this->logger->debug("Load data from '$filename'");
            $opt = new DataEndpointOptions(array('name' => "Configuration",
                                                 'path' => $filename,
                                                 'type' => "jsonfile"));
            $jsonFile = DataEndpoint::factory($opt, $this->logger);
            $this->data = $jsonFile->parse();
        } elseif ( is_array($this->parsedDefinitionFile->source_data->data) ) {
            $this->data = $this->parsedDefinitionFile->source_data->data;
        } else {
            $msg = "Source data must be an inline array or a filename";
            $this->logAndThrowException($msg);
        }

        $this->initialized = true;

        return true;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::execute()
     * ------------------------------------------------------------------------------------------
     */

    public function execute(EtlOverseerOptions $etlOverseerOptions)
    {
        $numRecordsProcessed = 0;
        $numRecordsUpdated = 0;

        $time_start = microtime(true);

        $this->initialize($etlOverseerOptions);

        // The UpdateIngestor does not create the destination table so it must exist.

        $tableName = $this->etlDestinationTable->getName();
        $schema = $this->etlDestinationTable->getSchema();

        if ( ! $this->destinationEndpoint->tableExists($tableName, $schema) ) {
            $msg = "Destination table " . $this->etlDestinationTable->getFullName() . " must exist";
            if ( $this->getEtlOverseerOptions()->isDryrun() ) {
                // In dry-run mode the table may not exist if a previous action in the pipeline created it
                $this->logger->warning($msg);
            } else {
                $this->logAndThrowException($msg);
            }
        }

        // Note that the update ingestor does not manage or truncate tables.

        $sql = "UPDATE " . $this->etlDestinationTable->getFullName() . " SET "
            . implode(
                ", ",
                array_map(
                    function ($s) {
                        return "$s = ?";
                    },
                    $this->parsedDefinitionFile->update->set
                )
            )
            . " WHERE "
            . implode(
                " AND ",
                array_map(
                    function ($w) {
                        return "$w = ?";
                    },
                    $this->parsedDefinitionFile->update->where
                )
            );

        $this->logger->debug("Update query\n$sql");

        // The order and number of the fields must match the update statement
        $dataFields = array_merge($this->parsedDefinitionFile->update->set, $this->parsedDefinitionFile->update->where);

        // Set up the indexes that we will need in the correct order for each data record
        $fieldsToIndexes = array_flip($this->parsedDefinitionFile->source_data->fields);
        $dataIndexes = array_map(
            function ($field) use ($fieldsToIndexes) {
                return $fieldsToIndexes[$field];
            },
            $dataFields
        );

        if ( ! $etlOverseerOptions->isDryrun() ) {
            try {
                $updateStatement = $this->destinationHandle->prepare($sql);

                foreach ( $this->data as $record ) {
                    $row = array_map(
                        function ($index) use ($record) {
                            return $record[$index];
                        },
                        $dataIndexes
                    );
                    $updateStatement->execute($row);
                    $numRecordsUpdated += $updateStatement->rowCount();
                    $numRecordsProcessed++;
                }

            } catch (PDOException $e) {
                $this->logAndThrowException(
                    "Error updating " . $this->etlDestinationTable->getFullName(),
                    array('exception' => $e, 'sql' => $sql, 'endpoint' => $this->destinationEndpoint)
                );
            }
        }

        $time_end = microtime(true);
        $time = $time_end - $time_start;

        $this->logger->notice(array(
                                  'action'         => (string) $this,
                                  'start_time'     => $time_start,
                                  'end_time'       => $time_end,
                                  'elapsed_time'   => round($time, 5),
                                  'records_loaded' => $numRecordsProcessed,
                                  'records_updated' => $numRecordsUpdated
                                  ));
    }  // execute()
}  // class StructuredFileIngestor
