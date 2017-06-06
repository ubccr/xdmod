<?php
/* ==========================================================================================
 * Abstract class to define properties and implement methods required by all structured
 * files. Methods supporting the Iterator and Countable interfaces that iStructuredFile
 * extends are defined here as well.
 *
 * @see Iterator
 * @see Countable
 * ==========================================================================================
 */

namespace ETL\DataEndpoint;

use Log;

abstract class aStructuredFile extends File
{
    /**
     * The default number of bytes for file read operations.
     */
    const DEFAULT_READ_BYTES = 4096;

    /**
     * Optional path to a schema describing each record the structured file.
     *
     * This is null if no schema was provided.
     *
     * @var array|null
     */
    protected $recordSchemaPath = null;

    /**
     * The list of filters that have been attached to the file handle
     *
     * @var array|null
     */
    protected $filterList = array();

    /**
     * The list of filters definition objects used to create filters.
     *
     * @var array|null
     */
    protected $filterDefinitions = null;

    /**
     * The list of records read from the input file
     *
     * @var array
     */
    protected $recordList = array();

    /**
     * The iterator position in the record list.
     *
     * @var int
     */
    private $recordListPosition = 0;

    /**
     * Character used to separate records in the input file, defaults to NULL.
     *
     * @var string
     */
    protected $recordSeparator = null;

    /**
     * Character used to separate fields in the record, defaults to NULL.
     *
     * @var string
     */
    protected $fieldSeparator = null;

    /** -----------------------------------------------------------------------------------------
     * @see iDataEndpoint::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(DataEndpointOptions $options, Log $logger = null)
    {
        parent::__construct($options, $logger);

        $this->generateUniqueKey();

        $messages = array();
        $propertyTypes = array(
            'record_schema_path' => 'string',
            'filters'            => 'array',
            'record_separator'   => 'string',
            'field_separator'    => 'string'
        );

        if ( ! \xd_utilities\verify_object_property_types($options, $propertyTypes, $messages, true) ) {
            $this->logAndThrowException("Error verifying options: " . implode(", ", $messages));
        }

        if ( isset($options->record_schema_path) ) {
            $this->recordSchemaPath = $options->record_schema_path;

            if ( isset($options->paths->schema_dir) ) {
                $this->recordSchemaPath = \xd_utilities\resolve_path(\xd_utilities\qualify_path(
                    $this->recordSchemaPath,
                    $options->paths->schema_dir
                ));
            }

            if ( ! is_readable($this->recordSchemaPath) ) {
                $this->logAndThrowException(
                    sprintf("Schema file '%s' is not readable", $this->recordSchemaPath)
                );
            }
        }

        if ( isset($options->record_separator) ) {
            $this->recordSeparator = $options->record_separator;
        }

        if ( isset($options->filters) ) {
            $this->filterDefinitions = $options->filters;
        }

    }  // __construct()

    /** -----------------------------------------------------------------------------------------
     * @see iStructuredFile::parse()
     * ------------------------------------------------------------------------------------------
     */

    public function parse()
    {
        $this->logger->info("Parsing " . $this->path);
        $this->attachFilters();
        $numBytesRead = $this->parseFile($this->path);
        $this->verifyData();

        $this->rewind();
        return $this->current();

    } // parse()

    /* ------------------------------------------------------------------------------------------
     * Add any filters that have been configured for this endpoint. PHP's stream filters
     * will be used to implement filtering and filters will be attached directly to the
     * file handle. As data is read from the file it is passed through the list of filters
     * using a bucket-brigade and accessed normally using fread() or other file functions.
     * See http://php.net/manual/en/ref.stream.php
     * ------------------------------------------------------------------------------------------
     */

    protected function attachFilters()
    {
        if ( null === $this->filterDefinitions ) {
            return;
        }

        // Register supported filters. Be sure to use the namespace in the class name.
        $filterName = \ETL\DataEndpoint\Filter\ExternalProcess::NAME;
        stream_filter_register($filterName, 'ETL\DataEndpoint\Filter\ExternalProcess');
        $this->logger->debug("Registering filter: $filterName");

        $fd = $this->connect();

        foreach ( $this->filterDefinitions as $config ) {

            if ( ! is_object($config) ) {
                $this->logger->warning(sprintf(
                    "Filter config must be an object, '%s' given. Skipping.",
                    gettype($config)
                ));
                continue;
            }

            $messages = array();
            $properties = array('name' => 'string', 'type' => 'string');
            if ( ! \xd_utilities\verify_object_property_types($config, $properties, $messages) ) {
                $this->logAndThrowException("Filter missing required properties: " . implode(', ', $messages));
            }

            // Include the logger for better error logging
            $config->logger = $this->logger;

            switch ($config->type) {
                case 'external':
                    $properties = array('path' => 'string');
                    if ( ! \xd_utilities\verify_object_property_types($config, $properties, $messages) ) {
                        $this->logger->warning(
                            sprintf("Skipping filter '%s': %s", $config->name, implode(", ", $messages))
                        );
                        continue;
                    }
                    $filterName = 'xdmod.external_process';
                    $resource = @stream_filter_prepend($fd, $filterName, STREAM_FILTER_READ, $config);
                    $this->logger->debug(sprintf("Adding filter %s to stream", $filterName));

                    if ( false === $resource ) {
                        $error = error_get_last();
                        $this->logAndThrowException("Error adding stream filter: " . $error['message']);
                    }
                    $this->filterList[$config->name] = $config;
                    break;
                default:
                    $this->logger->warning(
                        sprintf("Unsupported filter type '%s', skipping", $config->type)
                    );
                    break;
            }
        }

    }  // attachFilters()

    /** -----------------------------------------------------------------------------------------
     * Parse and decode a data file and return the parsed representation. We parse (and
     * optionally filter) the entire file at once and place decoded records into the
     * record list. Reading is not allowed until after parsing is complete. In the future,
     * we can consider processing the data in a separate thread/process and reading it
     * back as a stream so we can allow reading before the all data is fully processed.
     *
     * @param string $path The path of the file to parse.
     *
     * @return The number of bytes read
     *
     * @throw Exception If the file could not be read.
     * @throw Exception If the file could not be parsed.
     * ------------------------------------------------------------------------------------------
     */

    protected function parseFile($path)
    {
        $buffer = '';
        $totalBytesRead = 0;

        // Reset the record list and it's iterator pointer

        $this->recordList = array();
        $this->rewind();

        $fd = $this->connect();

        // If there is no record separator set we can read the file through to the end,
        // otherwise we need to check for the record separator and handle individual
        // records appropriately.

        if ( null === $this->recordSeparator ) {
            while ( ! feof($fd) ) {
                $data = fread($fd, self::DEFAULT_READ_BYTES);
                if ( false !== $data ) {
                    $buffer .= $data;
                    $totalBytesRead += strlen($data);
                }
            }
        } else {
            while ( ! feof($fd) ) {

                $data = fread($fd, self::DEFAULT_READ_BYTES);

                // The read operation may not return any data...
                if ( '' == $data ) {
                    continue;
                }

                // If this is a new chunk of data we may need to continue processing a
                // record from the previous chunk.

                $newDataChunk = true;
                $numBytesRead = strlen($data);
                $totalBytesRead += $numBytesRead;

                // Handle the following cases after reading a chunk of data:
                //
                // 1. The data does not contain any record separator (RS)
                // 2. The data starts with the RS
                // 3. The data ends with the RS
                // 4. The data contains one or more RS

                // #2 The data begins with a record separator. Decode any data already in
                // the buffer as strtok() will not explicitly identify this case.

                if ( $this->recordSeparator == $data[0] && '' != $buffer ) {
                    $this->decodeRecord($buffer);
                    $buffer = '';
                }

                // Tokenize the string into records based on the record separator. The
                // data may contain 0 or more instances of the record separator. strtok()
                // will not explictly identify a RS at the start or end of the data. If
                // there is no RS a single record will be returned.

                $record = strtok($data, $this->recordSeparator);
                while ( false !== $record ) {

                    // Don't flush the buffer if we had a partial record from a previous
                    // fread() operation.

                    if ( '' != $buffer && ! $newDataChunk ) {
                        $this->decodeRecord($buffer);
                        $buffer = '';
                    }

                    $buffer .= $record;
                    $record = strtok($this->recordSeparator);
                    $newDataChunk = false;
                }

                // #3 We've processed all of the records and the data ends with a record
                // separator. Decode the current buffer (and continue to read data until
                // we find a separator or hit EOF). Note that the buffer could be empty if
                // the data contained only record separators.

                if ( $this->recordSeparator == $data[$numBytesRead - 1] && '' != $buffer ) {
                    $this->decodeRecord($buffer);
                    $buffer = '';
                }

            }
        }

        // Decode anything remaining in the buffer

        if ( 0 != strlen($buffer) ) {
            $this->decodeRecord($buffer);
        }
        $this->disconnect();

        $this->logger->debug("Parsed " . count($this->recordList) . " records");

        return $totalBytesRead;

    }  // parseFile()

    /** -----------------------------------------------------------------------------------------
     * @see iStructuredFile::getRecordSeparator()
     * ------------------------------------------------------------------------------------------
     */

    public function getRecordSeparator()
    {
        return $this->recordSeparator;
    }

    /** -----------------------------------------------------------------------------------------
     * @see iStructuredFile::getFieldSeparator()
     * ------------------------------------------------------------------------------------------
     */

    public function getFieldSeparator()
    {
        return $this->fieldSeparator;
    }

    /** -----------------------------------------------------------------------------------------
     * @see iStructuredFile::getRecordFieldNames()
     * ------------------------------------------------------------------------------------------
     */

    public function getRecordFieldNames()
    {
        return array();
    }

    /** -----------------------------------------------------------------------------------------
     * @see iStructuredFile::getAttachedFilters()
     * ------------------------------------------------------------------------------------------
     */

    public function getAttachedFilters()
    {
        return $this->filterList;
    }

    /** -----------------------------------------------------------------------------------------
     * @see iStructuredFile::getRecordsDefinition()
     * ------------------------------------------------------------------------------------------
     */

    public function getRecordsDefinition()
    {
        return array();
    }

    /** -----------------------------------------------------------------------------------------
     * @see Iterator::current()
     * ------------------------------------------------------------------------------------------
     */

    public function current()
    {
        if ( ! $this->valid() ) {
            return false;
        }
        return $this->recordList[$this->recordListPosition];
    }

    /** -----------------------------------------------------------------------------------------
     * @see Iterator::current()
     * ------------------------------------------------------------------------------------------
     */

    public function key()
    {
        return $this->recordListPosition;
    }

    /** -----------------------------------------------------------------------------------------
     * @see Iterator::current()
     * ------------------------------------------------------------------------------------------
     */

    public function next()
    {
        $this->recordListPosition++;
    }

    /** -----------------------------------------------------------------------------------------
     * @see Iterator::current()
     * ------------------------------------------------------------------------------------------
     */

    public function rewind()
    {
        $this->recordListPosition = 0;
    }

    /** -----------------------------------------------------------------------------------------
     * @see Iterator::current()
     * ------------------------------------------------------------------------------------------
     */

    public function valid()
    {
        return isset($this->recordList[$this->recordListPosition]);
    }

    /** -----------------------------------------------------------------------------------------
     * @see Countable::count()
     * ------------------------------------------------------------------------------------------
     */

    public function count()
    {
        return count($this->recordList);
    }

    /** -----------------------------------------------------------------------------------------
     * Decodes a data string into a PHP object and add it to the record list.
     *
     * @param  string $data The data string to decode.
     *
     * @return bool TRUE on success
     * @throws Exception if there was an error decoding the data.
     * ------------------------------------------------------------------------------------------
     */

    abstract protected function decodeRecord($data);

    /** -----------------------------------------------------------------------------------------
     * If a record schema was specified, verify that each record conforms to that schema.
     *
     * @return TRUE on success
     * @throw Exception If there was a validation error
     * ------------------------------------------------------------------------------------------
     */

    abstract protected function verifyData();
}  // abstract class aStructuredFile
