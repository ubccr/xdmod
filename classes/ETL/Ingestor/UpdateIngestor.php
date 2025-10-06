<?php
/* ------------------------------------------------------------------------------------------
 * Ingestor for updating specific columns in a table.
 *
 * @author Steve Gallo 2016-06-16
 * ------------------------------------------------------------------------------------------
 */

namespace ETL\Ingestor;

use PDOException;

use ETL\iAction;
use ETL\Configuration;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\aRdbmsDestinationAction;
use ETL\aOptions;
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;
use ETL\DataEndpoint\iStructuredFile;
use Psr\Log\LoggerInterface;

class UpdateIngestor extends aRdbmsDestinationAction implements iAction
{
    /**
     * This action does not (yet) support multiple destination tables. If multiple
     * destination tables are present, store the first here and use it.
     *
     * @var \ETL\DbModel\Table
     */

    protected $etlDestinationTable = null;

    /** -----------------------------------------------------------------------------------------
     * @see iAction::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, LoggerInterface $logger = null)
    {
        $requiredKeys = array("definition_file");
        $this->verifyRequiredConfigKeys($requiredKeys, $options);

        parent::__construct($options, $etlConfig, $logger);

        if ( ! $options instanceof IngestorOptions ) {
            $this->logAndThrowException("Options is not an instance of IngestorOptions");
        }

    }  // __construct()

    /** -----------------------------------------------------------------------------------------
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

        if ( ! $this->sourceEndpoint instanceof iStructuredFile ) {
            $this->logAndThrowException(
                sprintf(
                    "Source endpoint %s does not implement %s",
                    get_class($this->sourceEndpoint),
                    "ETL\\DataEndpoint\\iStructuredFile"
                )
            );
        }

        // This action only supports 1 destination table so use the first one and log a warning if
        // there are multiple.

        reset($this->etlDestinationTableList);
        $this->etlDestinationTable = current($this->etlDestinationTableList);
        $etlTableKey = key($this->etlDestinationTableList);
        if ( count($this->etlDestinationTableList) > 1 ) {
            $this->logger->warning(
                sprintf(
                    "%s does not support multiple ETL destination tables, using first table with key: '%s'",
                    $this,
                    $etlTableKey
                )
            );
        }

        // Verify that we have a properly "update" property

        $requiredKeys = array(
            'update' => 'object'
        );

        $messages = null;
        if ( ! \xd_utilities\verify_object_property_types($this->parsedDefinitionFile, $requiredKeys, $messages) ) {
            $this->logAndThrowException(sprintf("Definition file error: %s", implode(', ', $messages)));
        }

        $requiredKeys = array(
            'set'   => 'array',
            'where' => 'array'
        );

        if ( ! \xd_utilities\verify_object_property_types($this->parsedDefinitionFile->update, $requiredKeys, $messages) ) {
            $this->logAndThrowException(
                sprintf("Error verifying definition file 'update' section: %s", implode(', ', $messages))
            );
        }

        // Merge source data fields with update set and where fields and ensure that they exist in
        // the table definition

        $updateColumns = array_merge(
            $this->parsedDefinitionFile->update->set,
            $this->parsedDefinitionFile->update->where
        );

        $missingColumnNames = array_diff(
            array_unique($updateColumns),
            $this->etlDestinationTable->getColumnNames()
        );

        if ( 0 != count($missingColumnNames) ) {
            $this->logAndThrowException(
                sprintf(
                    "The following columns from the update configuration were not found in table '%s': %s",
                    $this->etlDestinationTable->getFullName(),
                    implode(", ", $missingColumnNames)
                )
            );
        }

        $this->initialized = true;

        return true;

    }  // initialize()

    /** -----------------------------------------------------------------------------------------
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

        $tableName = $this->etlDestinationTable->name;
        $schema = $this->etlDestinationTable->schema;

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

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $this->etlDestinationTable->getFullName(),
            implode(
                ', ',
                array_map(function ($s) {
                    return "$s = ?";
                }, $this->parsedDefinitionFile->update->set)
            ),
            implode(
                ' AND ',
                array_map(function ($w) {
                    return "$w = ?";
                }, $this->parsedDefinitionFile->update->where)
            )
        );

        $this->logger->debug("Update query\n$sql");

        // The order and number of the fields must match the placeholders update statement

        $queryDataFields = array_merge(
            $this->parsedDefinitionFile->update->set,
            $this->parsedDefinitionFile->update->where
        );

        // Verify that all of the required fields are present in the data

        $firstRecord = $this->sourceEndpoint->parse();
        if ( ! is_array($firstRecord) ) {
            $this->logAndThrowException("The current implementation of %s only supports array records", $this);
        }

        $missing = array_diff($queryDataFields, $this->sourceEndpoint->getRecordFieldNames());
        if ( 0 != count($missing) ) {
            $this->logAndThrowException(
                sprintf(
                    "These fields are required by the update but are not present in the source data: %s",
                    implode(', ', $missing)
                )
            );
        }

        if ( ! $etlOverseerOptions->isDryrun() ) {
            try {
                $updateStatement = $this->destinationHandle->prepare($sql);

                foreach ( $this->sourceEndpoint as $record ) {

                    $row = array_map(
                        function ($field) use ($record) {
                            return $record[$field];
                        },
                        $queryDataFields
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

        $this->logger->notice(
            '',
            [
                'action' => (string)$this,
                'start_time' => $time_start,
                'end_time' => $time_end,
                'elapsed_time' => round($time, 5),
                'records_loaded' => $numRecordsProcessed,
                'records_updated' => $numRecordsUpdated
            ]
        );
    }  // execute()
}  // class StructuredFileIngestor
