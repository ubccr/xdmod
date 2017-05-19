<?php
/* ==========================================================================================
 * JSON file data endpoint. Includes support for parsing a JSON file and returning the parsed
 * representation via the parse() method.
 * ==========================================================================================
 */

namespace ETL\DataEndpoint;

use Exception;
use Log;
use ETL\DataEndpoint\DataEndpointOptions;
use ETL\DataEndpoint\Filter\ExternalProcess;

use JsonSchema\Validator;
use JsonSchema\SchemaStorage;
use JsonSchema\Constraints\Factory;

class JsonFile extends aStructuredFile implements iStructuredFile
{
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

        if ( is_array($decoded) ) {
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
