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

use Psr\Log\LoggerInterface;
use stdClass;
use Exception;
use PDOException;

use ETL\iAction;
use ETL\aOptions;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\Utilities;
use ETL\aRdbmsDestinationAction;
use ETL\DataEndpoint\iStructuredFile;

class StructuredFileIngestor extends aIngestor implements iAction
{
    /** -----------------------------------------------------------------------------------------
     * The custom insert values component is an object that allows us to specify a
     * subquery to use when inserting data rather than the raw source value. If the
     * destination column is present as a key in the object, the key's value will be used.
     *
     * @var array|null
     * ------------------------------------------------------------------------------------------
     */

    protected $customInsertValuesComponents = null;

    /** -----------------------------------------------------------------------------------------
     * @see iAction::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, LoggerInterface $logger = null)
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

        // We will need to get the record fields from the source data. This happens after the first
        // record is parsed.

        $firstRecord = $this->sourceEndpoint->parse();

        // If there are no records we can bail out. Otherwise we may not even be able to infer the
        // source field names.

        if ( false === $firstRecord || 0 == $this->sourceEndpoint->count() ) {
            $this->logger->info(
                sprintf("Source endpoint %s returned 0 records, skipping.", $this->sourceEndpoint)
            );
            return $numRecords;
        }

        $recordFieldNames = $this->sourceEndpoint->getRecordFieldNames();

        $this->logger->debug(
            sprintf("Requested %d record fields: %s", count($recordFieldNames), implode(', ', $recordFieldNames))
        );

        if ( 0 == count($recordFieldNames) ) {
            return $numRecords;
        }

        $this->parseDestinationFieldMap($recordFieldNames, $this->sourceEndpoint);

        // The custom_insert_values_components option is an object that allows us to specify a
        // subquery to use when inserting data rather than the raw source value. If the destination
        // column is present as a key in the object, use the subquery, otherwise use "?" as a
        // placeholder. Note that the raw value will be provided to the subquery and it should
        // contain a single "?" placeholder.
        //
        // NOTE: Null values will not overwrite non-null values in the database. This is done to
        // handle destinations that can be populated by multiple sources with varying levels of
        // detail.

        $customInsertValuesComponents = $this->customInsertValuesComponents;

        // The destination field map may specify that the same source field is mapped to multiple
        // destination fields and the order that the source record fields are returned may be
        // different from the order the fields were specified in the map. For each destination
        // table, maintain a mapping between the field position in the map (index) and the source
        // fields so we cam properly build the SQL parameter list in the proper order. At the same
        // time generate other data structures that will be needed later.

        $destinationFieldIdToSourceFieldMap = array();

        // Templates for source field values containing pre-determined values such as variables or
        // macros
        $sourceFieldToValueMapTemplate = array();

        // Scalar source fields that map to source fields
        $simpleSourceFields = array();

        // Complex source fields that must be evaluated by the source endpoint
        $complexSourceFields = array();

        // Variables or macros that will be substituted
        $variableSourceFields = array();

        // Iterate over the destination field mappings rather than the destination table list because it
        // is possible that a table definition is provided but no data is mapped to it.

        $this->logger->debug("Processing destination field map");

        foreach ( $this->destinationFieldMappings as $etlTableKey => $destFieldToSourceFieldMap ) {
            $destinationFields = array_keys($destFieldToSourceFieldMap);

            // Create a mapping from the source fields to the all of the destination field indexes
            // they correspond to. At the same time, split the source fileds into lists of simple
            // and complex fields.

            $simpleSourceFields[$etlTableKey] = array();
            $complexSourceFields[$etlTableKey] = array();
            $variableSourceFields[$etlTableKey] = array();
            $destinationFieldIdToSourceFieldMap[$etlTableKey] = array();

            foreach ( array_values($destFieldToSourceFieldMap) as $index => $sourceField ){
                $destinationFieldIdToSourceFieldMap[$etlTableKey][$index] = $sourceField;
                if (
                    $this->sourceEndpoint->supportsComplexDataRecords()
                    && $this->sourceEndpoint->isComplexSourceField($sourceField)
                ) {
                    $complexSourceFields[$etlTableKey][] = $sourceField;
                } elseif ( Utilities::containsVariable($sourceField) ) {
                    $variableSourceFields[$etlTableKey][] = $sourceField;
                } else {
                    $simpleSourceFields[$etlTableKey][] = $sourceField;
                }
            }

            $valuesComponents = array_map(
                function ($destField) use ($customInsertValuesComponents) {
                    return ( property_exists($customInsertValuesComponents, $destField)
                             ? $customInsertValuesComponents->$destField
                             : '?' );
                },
                $destinationFields
            );

            // Generate one SQL statement per destination table. Quote the field names to handle
            // SQL reserved words.

            $destinationFields = $this->quoteIdentifierNames($destinationFields);

            $sql = sprintf(
                'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
                $this->etlDestinationTableList[$etlTableKey]->getFullName(),
                implode(', ', $destinationFields),
                implode(', ', $valuesComponents),
                implode(', ', array_map(
                    function ($destField) {
                        return "$destField = COALESCE(VALUES($destField), $destField)";
                    },
                    $destinationFields
                ))
            );

            try {
                $this->logger->debug(
                    sprintf("Insert SQL for table key '%s':\n%s", $etlTableKey, $sql)
                );
                if ( ! $this->getEtlOverseerOptions()->isDryrun() ) {
                    $insertStatements[$etlTableKey] = $this->destinationHandle->prepare($sql);
                }
            } catch (PDOException $e) {
                $this->logAndThrowException(
                    "Error preparing insert statement for table key '$etlTableKey'",
                    array('exception' => $e, 'endpoint' => $this)
                );
            }

            // If there are source fields that are variables or macros, evaluate them once here and
            // save them to a reusable template.

            $sourceFieldToValueMapTemplate[$etlTableKey] = array();

            if ( 0 != count($variableSourceFields[$etlTableKey]) ) {
                foreach ( $variableSourceFields[$etlTableKey] as $variable ) {
                    $sourceFieldToValueMapTemplate[$etlTableKey][$variable] =
                        $this->variableStore->substitute($variable);
                }
            }
        }

        if ( $this->getEtlOverseerOptions()->isDryrun() ) {
            return $numRecords;
        }

        // When the destination field map is auto-generated, only scalar source fields are used. If
        // the source data is complex (e.g., JSON) we may end up with some complex fields in the
        // source record (e.g., JSON objects as stdClass). Obviously, these cannot be used in the
        // SQL parameter list but checking each field of each source record will reduce ingest
        // performance.  Perform a on the first record to provide some sanity checking.

        $invalidSourceValues = array();

        foreach ( $this->destinationFieldMappings as $etlTableKey => $destFieldToSourceFieldMap ) {
            $parameters = $this->generateParametersFromSourceRecord(
                $firstRecord,
                $destinationFieldIdToSourceFieldMap[$etlTableKey],
                $sourceFieldToValueMapTemplate[$etlTableKey],
                $simpleSourceFields[$etlTableKey],
                $complexSourceFields[$etlTableKey]
            );

            // Verify the parameters are scalars

            foreach ( $parameters as $index => $value ) {
                if ( null !== $value && ! is_scalar($value) ) {
                    $sourceField = $destinationFieldIdToSourceFieldMap[$etlTableKey][$index];
                    $invalidSourceValues[$etlTableKey][$sourceField] = $value;
                }
            }
        }

        if ( 0 != count($invalidSourceValues) ) {
            $this->logger->err(sprintf("First record:%s%s", PHP_EOL, print_r($firstRecord, true)));
            $this->logAndThrowException(
                sprintf(
                    "Source record contains non-scalar values that cannot be used as SQL params. %s",
                    implode('; ', array_map(
                        function ($table, $invalidValues) {
                            return sprintf(
                                "Table '%s': %s",
                                $table,
                                implode(', ', array_map(
                                    function ($k, $v) {
                                        return sprintf("field '%s' = %s", $k, gettype($v));
                                    },
                                    array_keys($invalidValues),
                                    $invalidValues
                                ))
                            );
                        },
                        array_keys($invalidSourceValues),
                        $invalidSourceValues
                    ))
                )
            );
        }

        // Insert each source record. Note that the source record may be an array or an
        // object and must be Traversable.

        $warnings = array();

        foreach ( $this->sourceEndpoint as $sourceRecord ) {

            // The same source record may be used in multiple tables.

            foreach ( $this->destinationFieldMappings as $etlTableKey => $destFieldToSourceFieldMap ) {

                $parameters = $this->generateParametersFromSourceRecord(
                    $sourceRecord,
                    $destinationFieldIdToSourceFieldMap[$etlTableKey],
                    $sourceFieldToValueMapTemplate[$etlTableKey],
                    $simpleSourceFields[$etlTableKey],
                    $complexSourceFields[$etlTableKey]
                );

                try {
                    // Some values of parameters are complex objects and cannot be! This is due to
                    // the auto-generated field map.
                    $insertStatements[$etlTableKey]->execute($parameters);
                } catch (PDOException $e) {
                    $this->logger->debug(print_r($sourceRecord, true));
                    $this->logAndThrowException(
                        sprintf(
                            "Error inserting data into table key '%s' for record %s.",
                            $etlTableKey,
                            $this->sourceEndpoint->key()
                        ),
                        array('exception' => $e, 'endpoint' => $this)
                    );
                }

                $warning = $this->destinationHandle->query("SHOW WARNINGS");

                if ( count($warning) > 0 ) {
                    $warnings[$etlTableKey] = array_key_exists($etlTableKey, $warnings) ? array_merge($warnings[$etlTableKey], $warning) : $warning;
                }
            }
            $numRecords++;
        }

        foreach ( $warnings as $table => $message) {
            $this->logSqlWarnings($message, $this->etlDestinationTableList[$table]->getFullName());
        }

        return $numRecords;

    }  // _execute()

    /**
     * Build up a parameter list suitable for an SQL query. The parameters must be in the proper
     * order as expected by the field list of the query (this mapping information is stored in
     * $destinationFieldIdToSourceFieldMap). Note that the same source value may be used multiple
     * times in the query.
     *
     * @param $sourceRecord The current record from the source endpoint (must be Traversable but
     *   may not explicitly implement Traversable such as an array or stdClass)
     * @param array $destinationFieldIdToSourceFieldMap A mapping between the parameter position
     *   (index) in the SQL statement and the source fields so we cam properly build the SQL
     *   parameter list in the correct order.
     * @param array $sourceTemplate Templates for source field values containing pre-determined
     *   values such as variables or macros.
     * @param array $simpleSourceFields Scalar source fields that map to source fields.
     * @param array $complexSourceFields Complex source fields that must be evaluated by the source
     *   endpoint
     *
     * @return array A list of values to use as SQL parameters in the proper order corresponding
     *   to the SQL query parameters.
     */

    private function generateParametersFromSourceRecord(
        $sourceRecord,
        array $destinationFieldIdToSourceFieldMap,
        array $sourceTemplate,
        array $simpleSourceFields,
        array $complexSourceFields
    ) {
        $sourceFieldToValueMap = $sourceTemplate;

        // Build up the parameter list for the query. Note that the same source value may be
        // used multiple times.

        foreach ($sourceRecord as $sourceField => $sourceValue) {
            if ( in_array($sourceField, $simpleSourceFields) ) {
                $sourceFieldToValueMap[$sourceField] = $sourceValue;
            }
        }

        // If this source endpoint does not support complex fields this loop won't be
        // processed because no fields will have been identified as complex.

        foreach ( $complexSourceFields as $sourceField ) {
            $sourceFieldToValueMap[$sourceField] =
                $this->sourceEndpoint->evaluateComplexSourceField($sourceField, $sourceRecord);
        }

        // Map the values from the source record to the correct order in the parameter list

        $parameters = array();
        foreach ( $destinationFieldIdToSourceFieldMap as $index => $sourceField ) {
            $parameters[$index] = $sourceFieldToValueMap[$sourceField];
        }

        return $parameters;

    }  // generateParametersFromSourceRecord()

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
                        gettype($this->customInsertValuesComponents)
                    )
                );
            }
        } else {
            $this->customInsertValuesComponents = new stdClass();
        }

        return true;
    }
}  // class StructuredFileIngestor
