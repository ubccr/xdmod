<?php
/* ==========================================================================================
 * JSON file data endpoint. Includes support for parsing a JSON file and returning the parsed
 * representation via the parse() method.
 * ==========================================================================================
 */

namespace ETL\DataEndpoint;

use Exception;

use ETL\DataEndpoint\DataEndpointOptions;
use \Log;

use JsonSchema\Validator;
use Symfony\Component\Process\ProcessBuilder;

class JsonFile extends StructuredFile implements iDataEndpoint
{
    /**
     * A JSON Schema describing each element in an array-based JSON file.
     *
     * This is null if no schema was provided.
     *
     * @var array|null
     */
    private $arrayElementSchema = null;

    /**
     * A set of options to use for filtering of data.
     *
     * This is specified as an object under the key 'filter'. It supports the
     * following options:
     *   - jq: A jq filter to run on the file.
     *
     * @var stdClass|null
     */
    private $filterOptions = null;

    /* ------------------------------------------------------------------------------------------
     * @see iDataEndpoint::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(DataEndpointOptions $options, Log $logger = null)
    {
        parent::__construct($options, $logger);

        $this->generateUniqueKey();

        if ($options->array_element_schema_path !== null) {
            $this->arrayElementSchema = $this->parseFile($options->applyBasePath(
                'paths->specs_dir',
                $options->array_element_schema_path
            ));
        }

        $this->filterOptions = $options->filter;
    }  // __construct()

    /**
     * @see StructuredFile::parse
     */
    public function parse($returnArray = false)
    {
        $filterOptions = $this->filterOptions;
        if ($filterOptions !== null) {
            // Set up running a filter process.
            if (property_exists($filterOptions, 'jq')) {
                // TODO: Get jq's path.
                $filterProcArgs = array(
                    'jq',
                    $filterOptions->jq,
                    $this->path,
                );
            } else {
                $this->logAndThrowException("No valid filter options specified for '{$this->path}'.");
            }

            try {
                // Run the filter process.
                $filterProc = ProcessBuilder::create($filterProcArgs)->getProcess();

                $filterProc->run();
                if (! $filterProc->isSuccessful()) {
                    $this->logAndThrowException('Filter Error: ' . $filterProc->getErrorOutput());
                }
            } catch (Exception $e) {
                $msg = "Filter Error (" . implode(", ", $filterProcArgs) . "): " . $e->getMessage();
                $this->logAndThrowException($msg);
            }

            // Parse the filter process's output.
            $data = $this->decodeJson(
                $filterProc->getOutput(),
                $returnArray,
                "'{$this->path}' (via filter)"
            );
        } else {
            $data = $this->parseFile($this->path, $returnArray);
        }
        return $data;
    } // parse()

    /* ------------------------------------------------------------------------------------------
     * @see aDataEndpoint::verify()
     * ------------------------------------------------------------------------------------------
     */

    public function verify($dryrun = false, $leaveConnected = false)
    {
        parent::verify($dryrun, $leaveConnected);

        // Parse to verify the integrity of the file. We could parse and save the data but then we'd
        // potentially be carrying around a lot of extra data.

        $data = $this->parse();

        // If a JSON Schema for the elements of an array was provided, use that
        // to verify that each element in the data conforms to the spec.
        if ($this->arrayElementSchema !== null) {
            if (! is_array($data)) {
                $this->logAndThrowException("JSON file '{$this->path}' is not array-based.");
            }

            $validator = new Validator();
            foreach ($data as $dataArrayIndex => $dataArrayElement) {
                $validator->check($dataArrayElement, $this->arrayElementSchema);
                if ($validator->isValid()) {
                    continue;
                }

                $validatorExceptionMsg = "JSON file '{$this->path}' had the following errors at index $dataArrayIndex:";
                foreach ($validator->getErrors() as $validatorError) {
                    $validatorExceptionMsg .= "\n    * ${validatorError['message']}";
                }
                $this->logAndThrowException($validatorExceptionMsg);
            }
        }
    }  // verify()

    /* ------------------------------------------------------------------------------------------
     * Quote the string for JSON.
     *
     * @see iDataEndpoint::quote()
     *
     * ------------------------------------------------------------------------------------------
     */

    public function quote($str)
    {
        // json_encode() will enclose the string in double-quotes in addition to escaping characters
        // in the string so remove them.
        return trim(json_encode($str), '"');
    }  // quote()

    /**
     * Decodes a given JSON string into a PHP object.
     *
     * @param  string $rawData The JSON to decode.
     * @param  boolean $returnArray (Optional) Controls whether JSON objects are
     *                              returned as associative arrays or objects.
     *                              (Defaults to false.)
     * @param  string $source  (Optional) The source of the JSON. This is used
     *                         for displaying friendlier errors.
     * @return mixed           The PHP object the JSON decoded into.
     *
     * @throws Exception The JSON could not be decoded.
     */
    private function decodeJson($rawData, $returnArray = false, $source = '')
    {
        $json = @json_decode($rawData, $returnArray);
        if (null === $json) {
            $sourceMsgComponent = (empty($source) ? '' : " from $source");
            $msg = "Error parsing JSON{$sourceMsgComponent}: " . $this->jsonLastErrorMsg(json_last_error());
            $this->logAndThrowException($msg);
        }
        return $json;
    }

    /* ------------------------------------------------------------------------------------------
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

    /* ------------------------------------------------------------------------------------------
     * Parse and decode a JSON file and return the parsed representation.
     *
     * @param string $path The path of the JSON file to parse.
     * @param boolean $returnArray (Optional) Controls whether JSON objects are
     *                             returned as associative arrays or objects.
     *                             (Defaults to false.)
     * @return An object generated from the parsed JSON file
     *
     * @throw Exception If the file could not be read.
     * @throw Exception If the file could not be parsed.
     * ------------------------------------------------------------------------------------------
     */
    private function parseFile($path, $returnArray = false)
    {
        $rawData = @file_get_contents($path);

        if (false === $rawData) {
            $error = error_get_last();
            $msg = "Error opening file '{$path}': " . $error['message'];
            $this->logAndThrowException($msg);
        }

        if (empty($rawData)) {
            $msg = "Empty file '{$path}'";
            $this->logAndThrowException($msg);
        }

        return $this->decodeJson($rawData, $returnArray, "'$path'");
    }
}  // class JsonFile
