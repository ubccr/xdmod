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
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\aRdbmsDestinationAction;
use ETL\aOptions;
use ETL\DataEndpoint\iStructuredFile;
use Log;

class StructuredFileIngestor extends aIngestor implements iAction
{
    /**
     * The custom insert values component is an object that allows us to specify a
     * subquery to use when inserting data rather than the raw source value. If the
     * destination column is present as a key in the object, the key's value will be used.
     *
     * @var array|null
     */

    protected $customInsertValuesComponents = null;

    /** -----------------------------------------------------------------------------------------
     * @see iAction::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);
    }  // __construct()

    /** -----------------------------------------------------------------------------------------
     * @see iAction::initialize()
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

        if ( ! $this->sourceEndpoint instanceof iStructuredFile ) {
            $this->logAndThrowException(
                sprintf(
                    "Source endpoint %s does not implement %s",
                    get_class($this->sourceEndpoint),
                    "ETL\\DataEndpoint\\iStructuredFile"
                )
            );
        }

        $this->initialized = true;

        return true;

    }  // initialize()

    /** -----------------------------------------------------------------------------------------
     * @see aIngestor::_execute()
     * ------------------------------------------------------------------------------------------
     */

    // @codingStandardsIgnoreLine
    protected function _execute()
    {
        $numRecords = 0;
        $insertStatements = array();

        // We will need to get the record fields from the source data. This happens after
        // the first record is parsed.

        $this->sourceEndpoint->parse();
        $recordFieldNames = $this->sourceEndpoint->getRecordFieldNames();
        $this->logger->debug(
            sprintf("Requested %d record fields: %s", count($recordFieldNames), implode(', ', $recordFieldNames))
        );

        if ( 0 == count($recordFieldNames) ) {
            return $numRecords;
        }

        $this->parseDestinationFieldMap($recordFieldNames);

        // The custom_insert_values_components option is an object that allows us to
        // specify a subquery to use when inserting data rather than the raw source
        // value. If the destination column is present as a key in the object, use the
        // subquery, otherwise use "?" as a placeholder. Note that the raw value will be
        // provided to the subquery and it should contain a single "?" placeholder.
        //
        // NOTE: Null values will not overwrite non-null values in the database.
        // This is done to handle destinations that can be populated by
        // multiple sources with varying levels of detail.

        $customInsertValuesComponents = $this->customInsertValuesComponents;

        // The destination field map may specify that the same source field is mapped to
        // multiple destination fields and the order that the source record fields is
        // returned may be different from the order the fields were specified in the
        // map. Maintain a mapping between source fields and the position (index) that
        // they were specified in the map so we cam properly build the SQL parameter list.

        $sourceFieldIndexes = array();

        foreach ( $this->etlDestinationTableList as $etlTableKey => $etlTable ) {
            $destFieldToSourceFieldMap = $this->destinationFieldMappings[$etlTableKey];
            $destinationFields = array_keys($destFieldToSourceFieldMap);
            $sourceFieldIndexes[$etlTableKey] = array_values($destFieldToSourceFieldMap);

            $valuesComponents = array_map(
                function ($destField) use ($customInsertValuesComponents) {
                    return ( property_exists($customInsertValuesComponents, $destField)
                             ? $customInsertValuesComponents->$destField
                             : '?' );
                },
                $destinationFields
            );

            // Generate one statement per destination table

            $sql = sprintf(
                'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
                $etlTable->getFullName(),
                implode(', ', $destinationFields),
                implode(', ', $valuesComponents),
                implode(', ', array_map(
                    function ($destField) {
                        return "$destField = COALESCE(VALUES($destField), $destField)";
                    },
                    array_keys($destFieldToSourceFieldMap)
                ))
            );

            try {
                $this->logger->debug("Insert SQL: $sql");
                if ( ! $this->getEtlOverseerOptions()->isDryrun() ) {
                    $insertStatements[$etlTableKey] = $this->destinationHandle->prepare($sql);
                }
            } catch (PDOException $e) {
                $this->logAndThrowException(
                    "Error preparing insert statement for table key '$etlTableKey'",
                    array('exception' => $e, 'endpoint' => $this)
                );
            }
        }

        if ( $this->getEtlOverseerOptions()->isDryrun() ) {
            return $numRecords;
        }

        // Insert each source record. Note that the source record may be an array or an
        // object and must be Traversable.

        foreach ( $this->sourceEndpoint as $sourceRecord ) {
            foreach ( $this->destinationFieldMappings as $etlTableKey => $destFieldToSourceFieldMap ) {

                $parameters = array();

                // Build up the parameter list for the query. Note that the same source
                // value may be used multiple times. The records returned from a
                // StructuredFile endpoint will be Traversable as ($key, $value) pairs,
                // however this does not mean that we can assume they can be treated as
                // arrays (e.g., $sourceRecord[$sourceField]) because they may be objects
                // or store data in private members that are exposed by the Iterator
                // interface.

                foreach ($sourceRecord as $sourceField => $sourceValue) {
                    // Find all indexes that match the current source field
                    $indexes = array_keys(array_intersect($sourceFieldIndexes[$etlTableKey], array($sourceField)));
                    foreach ( $indexes as $i ) {
                        $parameters[$i] = $sourceValue;
                    }
                }

                try {
                    $insertStatements[$etlTableKey]->execute($parameters);
                } catch (PDOException $e) {
                    $this->logAndThrowException(
                        "Error inserting data into table key '$etlTableKey' for record " . ( $numRecords + 1),
                        array('exception' => $e, 'endpoint' => $this)
                    );
                }
            }
            $numRecords++;
        }

        return $numRecords;

    }  // _execute()

    /** -----------------------------------------------------------------------------------------
     * @see aIngestor::performPreExecuteTasks
     * ------------------------------------------------------------------------------------------
     */

    protected function performPreExecuteTasks()
    {
        parent::performPreExecuteTasks();

        // If any custom SQL fragments for insertion were specified, use them.

        if ( isset($this->parsedDefinitionFile->custom_insert_values_components) ) {
            $this->customInsertValuesComponents = $this->parsedDefinitionFile->custom_insert_values_components;
            if ( ! is_object($this->customInsertValuesComponents) ) {
                $this->logAndThrowException(
                    sprintf(
                        "custom_insert_values_components must be an object, %s given",
                        gettype($customInsertValuesComponents)
                    )
                );
            }
        } else {
            $this->customInsertValuesComponents = new stdClass();
        }

        return true;
    }
}  // class StructuredFileIngestor
