<?php
/* ==========================================================================================
 * JSON file data endpoint. Includes support for parsing a JSON file and returning the parsed
 * representation via the parse() method.
 * ==========================================================================================
 */

namespace ETL\DataEndpoint;

use Exception;
use Log;
use ETL\JsonPointer;
use ETL\DataEndpoint\DataEndpointOptions;
use ETL\DataEndpoint\Filter\ExternalProcess;

use JsonSchema\Validator;
use JsonSchema\SchemaStorage;
use JsonSchema\Constraints\Factory;

class JsonFile extends aStructuredFile implements iStructuredFile, iComplexDataRecords
{
    /** -----------------------------------------------------------------------------------------
     * The ENDPOINT_NAME constant defines the name for this endpoint that should be used
     * in configuration files. It also allows us to implement auto-discovery.
     *
     * @const string
     */

    const ENDPOINT_NAME = 'jsonfile';

    /** -----------------------------------------------------------------------------------------
     * @see iDataEndpoint::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(DataEndpointOptions $options, Log $logger = null)
    {
        parent::__construct($options, $logger);
    }  // __construct()

    /** -----------------------------------------------------------------------------------------
     * Quote the string for JSON.
     *
     * @see iDataEndpoint::quote()
     * ------------------------------------------------------------------------------------------
     */

    public function quote($str)
    {
        // json_encode() will enclose the string in double-quotes in addition to escaping characters
        // in the string so remove them.
        return trim(json_encode($str), '"');
    }  // quote()

    /** -----------------------------------------------------------------------------------------
     * Decodes a JSON string into a PHP object and add it to the record list.
     *
     * @see aStructuredFile::decodeRecord()
     * ------------------------------------------------------------------------------------------
     */

    protected function decodeRecord($data)
    {
        $decoded = @json_decode($data);

        if ( null === $decoded ) {
            $this->logAndThrowException(
                sprintf(
                    "Error decoding JSON from file '%s': %s\n%s",
                    $this->path,
                    $this->jsonLastErrorMsg(json_last_error()),
                    $data
                )
            );
        }

        // If we parsed an empty array or object do not include it as a record.

        if (
            (is_array($decoded) && 0 == count($decoded)) ||
            (is_object($decoded) && 0 == count(get_object_vars($decoded)))
        ) {
            return true;
        }

        // If we have decoded an array of records (either arrays or objects) then merge
        // them onto the record list. Be careful that we have not decoded a single record
        // that is an array, as this should simply be appended on to the end of the record
        // list.

        if ( is_array($decoded) && (is_array(current($decoded)) || is_object(current($decoded))) ) {
            $this->recordList = array_merge($this->recordList, $decoded);
        } else {
            $this->recordList[] = $decoded;
        }

        return true;
    }  // decodeRecord()

    /** -----------------------------------------------------------------------------------------
     * @see aStructuredFile::verifyData()
     * ------------------------------------------------------------------------------------------
     */

    protected function verifyData()
    {
        if ( null === $this->recordSchemaPath ) {
            return true;
        }

        $this->logger->debug("Validating data against schema " . $this->recordSchemaPath);

        $schemaData = @file_get_contents($this->recordSchemaPath);

        if ( false === $schemaData ) {
            $err = err_get_last();
            $this->logAndThrowException(
                sprintf("Error reading JSON schema '%s': %s", $this->recordSchemaPath, $err['message'])
            );
        }

        $schemaObject = @json_decode($schemaData);

        if ( null === $schemaObject ) {
            $this->logAndThrowException(
                sprintf(
                    "Error decoding JSON schema '%s': %s",
                    $this->recordSchemaPath,
                    $this->jsonLastErrorMsg(json_last_error())
                )
            );
        }

        $validator = new Validator();
        $messages = array();
        $recordIndex = 0;

        foreach ($this->recordList as $record) {
            $recordIndex++;
            $validator->check($record, $schemaObject);

            if ( $validator->isValid() ) {
                continue;
            }

            $errors = array();
            foreach ($validator->getErrors() as $err) {
                $errors[] = $err['message'];
            }
            $messages[] = sprintf("Record %d: %s", $recordIndex, implode(', ', $errors));
            $validator->reset();  // Without reset error messages accumulate
        }

        if ( 0 != count($messages) ) {
            $this->logAndThrowException(
                sprintf("Error validating JSON '%s': %s", $this->path, implode('; ', $messages))
            );
        }

        return true;

    }  // verifyData()

    /** -----------------------------------------------------------------------------------------
     * @see aStructuredFile::discoverRecordFieldNames()
     * ------------------------------------------------------------------------------------------
     */

    protected function discoverRecordFieldNames()
    {
        // If there are no records in the file then we don't need to set the discovered
        // field names.

        if ( 0 == count($this->recordList) ) {
            return;
        }

        // Determine the record names based on the structure of the JSON that we are
        // parsing.

        reset($this->recordList);
        $record = current($this->recordList);

        if ( is_array($record) ) {

            if ( $this->hasHeaderRecord ) {

                // If we have a header record skip the first record and use its values as
                // the field names

                $this->discoveredRecordFieldNames = array_shift($this->recordList);

            } elseif ( 0 !== count($this->requestedRecordFieldNames) ) {

                // If there is no header record and the requested field names have been
                // provided, use them as the discovered field names.  If a subsequent
                // record contains fewer fields return NULL values for those fields, if a
                // subsequent record contains more fields ignore them.

                $this->discoveredRecordFieldNames = $this->requestedRecordFieldNames;

            } else {
                $this->logAndThrowException("Record field names must be specified for JSON array records");
            }

        } elseif ( is_object($record) ) {

            // Pull the record field names from the object keys

            $this->discoveredRecordFieldNames = array_keys(get_object_vars($record));

        } else {
            $this->logAndThrowException(
                sprintf("Unsupported record type in %s. Got %s, expected array or object", $this->path, gettype($record))
            );
        }

        // If no field names were requested, return all discovered fields

        if ( 0 == count($this->requestedRecordFieldNames) ) {
            $this->requestedRecordFieldNames = $this->discoveredRecordFieldNames;
        }

    }  // setRecordFieldNames()

    /** -----------------------------------------------------------------------------------------
     * @see aStructuredFile::createReturnRecord()
     * ------------------------------------------------------------------------------------------
     */

    protected function createReturnRecord($record)
    {
        $arrayRecord = parent::createReturnRecord($record);

        // If the original record is a stdClass object, be sure to maintain its type.

        if ( is_object($record) ) {
            return (object) $arrayRecord;
        } else {
            return $arrayRecord;
        }
    }  // createReturnRecord()

    /** -----------------------------------------------------------------------------------------
     * Validate the source record fields in the destination field map.
     * aRdbmsDestinationAction::verifyDestinationMapSourceFields() has a hook to allow the
     * verification of the source record fields to be handled by the source endpoint if it
     * implements iValidateDestinationMapSourceFields.  Since a JSON file may contain a complex
     * object with nested objects and arrays we allow the destination field map to specify JSON
     * pointers in addition to the source field names themselves.
     *
     * NOTE: We will only check that a JSON pointer is correctly formatted, not that it correctly
     * addresses the data since we do not have access to the records at this time.
     *
     * @see iComplexDataRecords::validateDestinationMapSourceFields()
     * ------------------------------------------------------------------------------------------
     */

    public function validateDestinationMapSourceFields(array $destinationTableMap)
    {
        $sourceRecordFields = $this->getRecordFieldNames();
        $missing = array();

        foreach ( $destinationTableMap as $destField => $sourceField ) {
            // If the source field matches a field in the source record or it is a valid JSON
            // pointer and the first tiken matches a field in the source record.
            if (
                in_array($sourceField, $sourceRecordFields) ||
                (
                    JsonPointer::isValidPointer($sourceField)
                    && in_array(JsonPointer::getFirstToken($sourceField), $sourceRecordFields)
                )
            ) {
                continue;
            }

            $missing[$destField] = $sourceField;
        }

        return $missing;

    }  // validateDestinationMapSourceFields()

    /** -----------------------------------------------------------------------------------------
     * @see iComplexDataRecords::isComplexSourceField()
     * ------------------------------------------------------------------------------------------
     */

    public function isComplexSourceField($sourceField)
    {
        return JsonPointer::isValidPointer($sourceField);
    }  // isComplexSourceField()

    /** -----------------------------------------------------------------------------------------
     * @see iComplexDataRecords::evaluateComplexSourceField()
     * ------------------------------------------------------------------------------------------
     */

    public function evaluateComplexSourceField($sourceField, $record, $invalidRefValue = null)
    {
        try {
            return JsonPointer::extractFragment($record, $sourceField);
        } catch ( Exception $e ) {
            return $invalidRefValue;
        }
    }  // evaluateComplexSourceField()

    /** -----------------------------------------------------------------------------------------
     * Implementation of json_last_error_msg() for pre PHP 5.5 systems.
     *
     * @param $errorCode The error code returned by json_last_error()
     *
     * @return A human-readable error message
     * ------------------------------------------------------------------------------------------
     */

    private function jsonLastErrorMsg($errorCode)
    {
        $message = "";

        switch ( $errorCode ) {
            case JSON_ERROR_NONE:
                $message = "No error has occurred";
                break;
            case JSON_ERROR_DEPTH:
                $message = "The maximum stack depth has been exceeded";
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $message = "Invalid or malformed JSON";
                break;
            case JSON_ERROR_CTRL_CHAR:
                $message = "  Control character error, possibly incorrectly encoded";
                break;
            case JSON_ERROR_SYNTAX:
                $message = "Syntax error";
                break;
            case JSON_ERROR_UTF8:
                $message = "Malformed UTF-8 characters, possibly incorrectly encoded";
                break;
            case JSON_ERROR_RECURSION:
                $message = "One or more recursive references in the value to be encoded";
                break;
            case JSON_ERROR_INF_OR_NAN:
                $message = "One or more NAN or INF values in the value to be encoded";
                break;
            case JSON_ERROR_UNSUPPORTED_TYPE:
                $message = "A value of a type that cannot be encoded was given";
                break;
            default:
                $message = "Unknown error";
                break;
        }

        return $message;

    }  // jsonLastErrorMsg()
}  // class JsonFile
