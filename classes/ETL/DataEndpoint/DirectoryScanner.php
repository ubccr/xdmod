<?php
/** =========================================================================================
 * Directory Scanner Data Endpoint. The Directory Scanner is a wrapper around Structured
 * File endpoints that recursively scans a directory for files and instantiates Structured
 * File endpoint each file in a directory (or subdirectory) matching a set of optionally
 * specified criteria. It supports file name pattern matching and last modified dates. The
 * Directory Scanner implements the Iterator interface and iteration spans the union of
 * the records in each of the files. For example, if 2 files are found in a directory then
 * the Directory Scanner iterator will span all of the records in both files.
 *
 * @author Steven M. Gallo <smgallo@buffalo.edu>
 * @date 2017-05-31
 * ==========================================================================================
 */

namespace ETL\DataEndpoint;

use ETL\DataEndpoint\DataEndpointOptions;
use ETL\DataEndpoint\StructuredFile;
use Exception;
use Log;

class DirectoryScanner extends aDataEndpoint implements iDataEndpoint, \Iterator
{
    /** -----------------------------------------------------------------------------------------
     * The ENDPOINT_NAME constant defines the name for this endpoint that should be used
     * in configuration files. It also allows us to implement auto-discovery.
     *
     * @const string
     */

    const ENDPOINT_NAME = 'directoryscanner';

    /** -----------------------------------------------------------------------------------------
     * Numeric key to use for the default file extension handler. This should be the only
     * numeric key used.
     *
     * @var integer
     * ------------------------------------------------------------------------------------------
     */

    const DEFAULT_HANDLER_KEY = 0;

    /** -----------------------------------------------------------------------------------------
     * The directory path that we will be scanning. This should be a fully qualified path.
     *
     * @var string
     * ------------------------------------------------------------------------------------------
     */

    protected $path = null;

    /** -----------------------------------------------------------------------------------------
     * An optional regex that files must match to be identified by the scanner. This
     * applies to the file portion of the path only.
     *
     * @var string | null
     * ------------------------------------------------------------------------------------------
     */

    protected $filePattern = null;

    /** -----------------------------------------------------------------------------------------
     * An optional regex that directories must match to be identified by the scanner.
     *
     * @var string | null
     * ------------------------------------------------------------------------------------------
     */

    protected $directoryPattern = null;

    /** -----------------------------------------------------------------------------------------
     * The maximum depth that we will recurse into the directory hierarchy. -1 indicates
     * no limit.
     *
     * @var integer | null
     * ------------------------------------------------------------------------------------------
     */

    protected $maxRecursionDepth = null;

    /** -----------------------------------------------------------------------------------------
     * Only files modified on or after this time will be examined. Stored as a unix
     * timestamp, NULL indicates no restriction.
     *
     * @var int | null
     * ------------------------------------------------------------------------------------------
     */

    protected $lastModifiedStartTimestamp = null;

    /** -----------------------------------------------------------------------------------------
     * Only files modified on or before this time will be examined. Stored as a unix
     * timestamp, NULL indicates to restriction.
     *
     * @var int | null
     * ------------------------------------------------------------------------------------------
     */

    protected $lastModifiedEndTimestamp = null;

    /** -----------------------------------------------------------------------------------------
     * An array containing handler templates for various file types, which will be
     * augmented with the file name and path when the handler is instantiated. If a single
     * handler with no extension specified then it will be used for all files. If multiple
     * handlers are specified it is required that they also specify a file extension
     * (string) to determine which hanbdler gets applied to apply to a particular
     * file. When multiple handlers are specified, an extension of NULL indicates a
     * catch-all handler.
     *
     * @var array
     * ------------------------------------------------------------------------------------------
     */

    protected $handlerTemplateList = array();

    /** -----------------------------------------------------------------------------------------
     * The name of the current file that we are parsing.
     *
     * @var string
     * ------------------------------------------------------------------------------------------
     */

    private $currentFilename = null;

    /** -----------------------------------------------------------------------------------------
     * The iterator for the current file that we are parsing.
     *
     * @var Iterator
     * ------------------------------------------------------------------------------------------
     */

    private $currentFileIterator = null;

    /** -----------------------------------------------------------------------------------------
     * The number of files that have been scanned so far. Note that an empty file
     * containing no records is considered scanned.
     *
     * @var integer
     * ------------------------------------------------------------------------------------------
     */

    private $numFilesScanned = 0;

    /** -----------------------------------------------------------------------------------------
     * The number of records parsed in all of the files scanned so far. This does not mean
     * that the records were traversed.
     *
     * @var integer
     * ------------------------------------------------------------------------------------------
     */

    private $numRecordsParsed = 0;

    /* ------------------------------------------------------------------------------------------
     * @see iDataEndpoint::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(DataEndpointOptions $options, Log $logger = null)
    {
        parent::__construct($options, $logger);

        $requiredKeys = array('path', 'handlers');
        $this->verifyRequiredConfigKeys($requiredKeys, $options);

        $messages = array();
        $propertyTypes = array(
            'path'                => 'string',
            'handlers'            => 'array',
            'file_pattern'        => 'string',
            'directory_pattern'   => 'string',
            'recursion_depth'     => 'int',
            'last_modified_start' => 'string',
            'last_modified_end'   => 'string'
        );

        if ( ! \xd_utilities\verify_object_property_types($options, $propertyTypes, $messages, true) ) {
            $this->logAndThrowException("Error verifying options: " . implode(", ", $messages));
        }

        // Save values from the options to local properties

        foreach ( $options as $property => $value ) {

            // Skip null values and use property defaults

            if ( null === $value ) {
                continue;
            }

            switch($property) {
                case 'path':
                    $this->path = $value;
                    if ( 0 !== strpos($value, '/') ) {
                        $this->logger->info(
                            sprintf("%s: Relative path provided, absolute path recommended", $this)
                        );
                    }
                    break;

                case 'file_pattern':
                    $this->filePattern = $value;
                    break;

                case 'directory_pattern':
                    $this->directoryPattern = $value;
                    break;

                case 'recursion_depth':
                    $this->maxRecursionDepth = $value;
                    break;

                case 'last_modified_start':
                    if ( false === ( $ts = strtotime($value) ) ) {
                        $this->logAndThrowException(
                            sprintf("Error setting %s: Invalid date value '%s'", $property, $value)
                        );
                    } else {
                        $this->lastModifiedStartTimestamp = $ts;
                    }
                    break;

                case 'last_modified_end':
                    if ( false === ( $ts = strtotime($value) ) ) {
                        $this->logAndThrowException(
                            sprintf("Error setting %s: Invalid date value '%s'", $property, $value)
                        );
                    } else {
                        $this->lastModifiedEndTimestamp = $ts;
                    }
                    break;

                default:
                    break;
            }
        }

        $numHandlers = count($options->handlers);

        if ( 0 == $numHandlers ) {
            $this->logAndThrowException("At least 1 handler must be specified");
        }

        $numDefaultHandlers = 0;

        foreach ( $options->handlers as $handler ) {
            if ( isset($handler->extension) ) {
                // Normalize the extension to not contain a period
                $ext = $handler->extension;
                if ( 0 === strrpos($ext, '.') ) {
                    $ext = substr($ext, 1);
                }
                $this->logger->debug(
                    sprintf("%s: Adding handler for file extension '%s'", $this, $ext)
                );
                $this->handlerTemplateList[$ext] = $handler;
            } else {
                // Assign a default handler.
                $this->logger->debug(
                    sprintf("%s: Adding default file handler", $this)
                );
                $this->handlerTemplateList[self::DEFAULT_HANDLER_KEY] = $handler;
                $numDefaultHandlers++;
            }
        }

        if ( $numDefaultHandlers > 1 ) {
            $this->logAndThrowException(
                sprintf("%d default handlers specified, only 1 is allowed", $numDefaultHandlers)
            );
        }

        $this->key = md5(implode($this->keySeparator, array($this->type, $this->path, $this->name)));

    }  // __construct()

    /** -----------------------------------------------------------------------------------------
     * @return string The directory path that we will be scanning.
     * ------------------------------------------------------------------------------------------
     */

    public function getPath()
    {
        return $this->path;
    } // getPath()

    /** -----------------------------------------------------------------------------------------
     * @return string|null An optional regex that files must match to be identified by the
     * scanner.
     * ------------------------------------------------------------------------------------------
     */

    public function getFilePattern()
    {
        return $this->filePattern;
    } // getFilePattern()

    /** -----------------------------------------------------------------------------------------
     * @return string|null An optional regex that directories must match to be identified
     * by the scanner.
     * ------------------------------------------------------------------------------------------
     */

    public function getDirectoryPattern()
    {
        return $this->directoryPattern;
    }  // getDirectoryPattern()

    /** -----------------------------------------------------------------------------------------
     * @return integer The maximum depth that we will recurse into the directory
     * hierarchy. -1 indicates no limit.
     * ------------------------------------------------------------------------------------------
     */

    public function getMaxRecursionDepth()
    {
        return $this->maxRecursionDepth;
    }  // getMaxRecursionDepth()

    /** -----------------------------------------------------------------------------------------
     * @return string|null The minumum last modified time for a file.
     * ------------------------------------------------------------------------------------------
     */

    public function getLastModifiedStartTime()
    {
        return $this->lastModifiedStartTimestamp;
    }  // getLastModifiedStartTime()

    /** -----------------------------------------------------------------------------------------
     * @return string|null The maximum last modified time for a file.
     * ------------------------------------------------------------------------------------------
     */

    public function getLastModifiedEndTime()
    {
        return $this->lastModifiedEndTimestamp;
    }  // getLastModifiedEndTime()

    /** -----------------------------------------------------------------------------------------
     * @return array The list of handler templates. If more than one items is in the list
     * the keys will be strings represneting the file extensions matching the template
     * with an index of 0 for the catch-all handler.
     * ------------------------------------------------------------------------------------------
     */

    public function getHandlerTemplateList()
    {
        return $this->handlerTemplateList;
    }  // getHandlerTemplateList()

    /** -----------------------------------------------------------------------------------------
     * @return integer The number of files scanned so far.
     * ------------------------------------------------------------------------------------------
     */

    public function getNumFilesScanned()
    {
        return $this->numFilesScanned;
    }  // getNumFilesScanned()

    /** -----------------------------------------------------------------------------------------
     * @return integer The total number of records parsed in all files scanned so far.
     * ------------------------------------------------------------------------------------------
     */

    public function getNumRecordsParsed()
    {
        return $this->numRecordsParsed;
    }  // getNumRecordsParsed()

    /** -----------------------------------------------------------------------------------------
     * Connecting for the DirectoryScanner includes applying any filters specified in the
     * configuration.
     *
     * @see iDataEndpoint::connect()
     * ------------------------------------------------------------------------------------------
     */

    public function connect()
    {
        // The first time a connection is made the endpoint handle should be set.

        if ( null !== $this->handle ) {
            return $this->handle;
        }

        // The PHP docs on SPL iterators at http://php.net/manual/en/spl.iterators.php
        // are sparse so these are some notes on usage of RecursiveDirectoryIterator,
        // RecursiveIteratorIterator, and filtering.
        //
        // A note on recursive iterators: If a directory doesn't match the filter
        // (i.e., it returns FALSE) then the directory will not be recursed
        // into. Rather than using RecursiveCallbackFilterIterator it may be better to
        // use CallbackFilterIterator after RecursiveIteratorIterator depending on the
        // situation.
        //
        // We want to be able to filter on the path and file separately so we are using an
        // instance of CallbackFilterIterator to do this instead of RegexIterator.
        //
        // RecursiveDirectoryIterator provides a mechanism to recursively iterate over
        // directories and the files that they contain. It does not iterate beyond the
        // *root* directory automatically. For this we need to roll our own or use
        // RecursiveIteratorIterator.
        //
        // RecursiveRegexIterator can be attached to the RecursiveDirectoryIterator to
        // filter paths. Note that if a directory doesn't match the regex it will not
        // be recursed into so this is not well suited for regexes that should be
        // applied to files.
        //
        // RecursiveCallbackFilterIterator needs to operate on a RecursiveIterator so
        // it cannot be applied to a RecursiveIteratorIterator, use
        // CallbackFilterIterator on a RecursiveIteratorIterator.
        //
        // RecursiveIteratorIterator operates on classes implementing
        // RecursiveIterator and will handle the recursion into the children.  The
        // mode can be specified as LEAVES_ONLY (default), SELF_FIRST, CHILD_FIRST.
        // Note that LEAVES_ONLY will include "." and ".." directories so these will
        // need to be filtered out AFTER applying this iterator.
        //
        // RegexIterator can be attached to RecursiveIteratorIterator to filter the
        // paths that it returns, but note that the regex applies to the full path,
        // not just the name of the file itself.
        //
        // Note that we want to be able to filter on the path and file separately and are using
        // the CallbackFilterIterator to do this instead of RegexIterator.

        // We are conditionally creating multiple iterators that will consume earlier
        // iterators. Keep track of the current iterator.

        $iterator = null;

        $this->logger->debug(
            sprintf("Connecting directory scanner to %s", $this->path)
        );

        try {
            $directoryIterator = new \RecursiveDirectoryIterator($this->path);
            $iterator = $directoryIterator;
        } catch ( Exception $e ) {
            $this->logAndThrowException(
                sprintf("Error opening directory '%s': %s", $this->path, $e->getMessage())
            );
        }

        // Apply the recursion iterator that will traverse the directory iterator

        try {
            $flattenedIterator = new \RecursiveIteratorIterator($iterator);

            if ( null !== $this->maxRecursionDepth ) {
                $this->logger->debug(
                    sprintf("Set max recursion depth: %d", $this->maxRecursionDepth)
                );
                $flattenedIterator->setMaxDepth($this->maxRecursionDepth);
            }

            $iterator = $flattenedIterator;
        }  catch ( Exception $e ) {
            $this->logAndThrowException(
                sprintf("Error creating RecursiveIteratorIterator: %s", $e->getMessage())
            );
        }

        // Filter out directories "." and "..". This and other filters could be
        // included in a single CallbackFilterIterator bit I've decided to keep them
        // split out for readability, debugging, and error reporting.

        // For the CallbackFilter classes, the types of the callback parameters depend
        // on the flags passed to RecursiveDirectoryIterator::__construct(). In our
        // case, $current is a SplFileInfo object and the key is the fill path to the
        // file.

        try {
            $dotDirFilterIterator = new \CallbackFilterIterator(
                $iterator,
                function ($current, $key, $iterator) {
                    return ( ! $iterator->isDot() );
                }
            );
            $iterator = $dotDirFilterIterator;
        }  catch ( Exception $e ) {
            $this->logAndThrowException(
                sprintf("Error applying dot directory filters: %s", $e->getMessage())
            );
        }

        // We do not want to return directories as part of the traversal so we need to
        // apply directory/file patterns and other checks AFTER traversing the
        // directory tree. Otherwise, directories may be inadvertantly filtered and
        // the files missed.

        if ( null !== $this->directoryPattern || null !== $this->filePattern ) {

            // PHP 5.3 does not allow us to reference the object in the callback
            $dirPattern = $this->directoryPattern;
            $filePattern = $this->filePattern;

            $this->logger->info(
                sprintf(
                    "Applying pattern filters: (directory: %s, file: %s)",
                    ( null === $dirPattern ? "null" : $dirPattern ),
                    ( null === $filePattern ? "null" : $filePattern )
                )
            );

            try {
                $patternCallbackIterator = new \CallbackFilterIterator(
                    $iterator,
                    function ($current, $key, $iterator) use ($dirPattern, $filePattern) {
                        if (
                            null !== $dirPattern
                            && ! preg_match($dirPattern, $current->getPath())
                        ) {
                            return false;
                        }

                        if (
                            null !== $filePattern
                            && ! preg_match($filePattern, $current->getFilename())
                        ) {
                            return false;
                        }

                        return true;
                    }
                );
                $iterator = $patternCallbackIterator;
            }  catch ( Exception $e ) {
                $this->logAndThrowException(
                    sprintf(
                        "Error applying pattern filters (directory: %s, file: %s): %s",
                        ( null === $this->directoryPattern ? "null" : $this->directoryPattern ),
                        ( null === $this->filePattern ? "null" : $this->filePattern ),
                        $e->getMessage()
                    )
                );
            }
        }

        if ( null !== $this->lastModifiedStartTimestamp || null !== $this->lastModifiedEndTimestamp ) {

            // PHP 5.3 does not allow us to reference the object in the callback
            $lmStartTs = $this->lastModifiedStartTimestamp;
            $lmEndTs = $this->lastModifiedEndTimestamp;

            $this->logger->info(
                sprintf(
                    "Applying mtime filter: (start: %s, end: %s)",
                    ( null === $lmStartTs ? "null" : $lmStartTs ),
                    ( null === $lmEndTs ? "null" : $lmEndTs )
                )
            );

            try {
                $callbackIterator = new \CallbackFilterIterator(
                    $iterator,
                    function ($current, $key, $iterator) use ($lmStartTs, $lmEndTs) {
                        if ( null !== $lmStartTs && null !== $lmEndTs ) {
                            return $current->getMTime() >= $lmStartTs && $current->getMTime() <= $lmEndTs;
                        } elseif ( null !== $lmStartTs ) {
                            return $current->getMTime() >= $lmStartTs;
                        } elseif ( null !== $lmEndTs ) {
                            return $current->getMTime() <= $lmEndTs;
                        } else {
                            return false;
                        }
                    }
                );
                $iterator = $callbackIterator;
            }  catch ( Exception $e ) {
                $this->logAndThrowException(
                    sprintf(
                        "Error applying last modified filter (start: %s, end: %s): %s",
                        ( null === $this->lastModifiedStartTimestamp ? "null" : $this->lastModifiedStartTimestamp ),
                        ( null === $this->lastModifiedEndTimestamp ? "null" : $this->lastModifiedEndTimestamp ),
                        $e->getMessage()
                    )
                );
            }
        }

        $this->handle = $iterator;

        // Rewind the handle so it is ready to use.
        $this->handle->rewind();

        return $this->handle;

    }  // connect()

    /** -----------------------------------------------------------------------------------------
     * @see iDataEndpoint::disconnect()
     * ------------------------------------------------------------------------------------------
     */

    public function disconnect()
    {
        $this->handle = null;
        return true;
    }  // disconnect()

    /** -----------------------------------------------------------------------------------------
     * Note that this class is essentially an iterator over a set of other iterators. The
     * $leaveConnected parameter does not apply in this context and is ignored.
     *
     * @see iDataEndpoint::verify()
     * ------------------------------------------------------------------------------------------
     */

    public function verify($dryrun = false, $leaveConnected = false)
    {
        if ( ! is_dir($this->path) ) {
            $this->logAndThrowException("Path '" . $this->path . "' is not a directory");
        }

        if ( ! is_readable($this->path) ) {
            $this->logAndThrowException("Path '" . $this->path . "' is not readable");
        }

        $dir = @opendir($this->path);

        if ( false === $dir ) {
            $error = error_get_last();
            $this->logAndThrowException(
                sprintf("Error opening directory '%s': %s", $this->path, $error['message'])
            );
        } else {
            closedir($dir);
        }

        return true;
    }  // verify()

    /** -----------------------------------------------------------------------------------------
     * Note that current() can't return FALSE if the internal pointer is not valid because FALSE may
     * be a valid value for the iterator, This is why valid() MUST be called before current().
     *
     * @see Iterator::current()
     * ------------------------------------------------------------------------------------------
     */

    public function current()
    {
        return $this->currentFileIterator->current();
    }  // current()

    /** -----------------------------------------------------------------------------------------
     * Return a composite key made up of the current file and the record number that we
     * are processing in that file.
     *
     * @see Iterator::key()
     * ------------------------------------------------------------------------------------------
     */

    public function key()
    {
        return sprintf("%s[%s]", $this->currentFilename, $this->currentFileIterator->key());
    }  // key()

    /** -----------------------------------------------------------------------------------------
     * Move to the next record in the current file iterator. Only move on to the next file in the
     * directory scan once we iterate over all records in the file.
     *
     * @see Iterator::next()
     * ------------------------------------------------------------------------------------------
     */

    public function next()
    {
        $this->currentFileIterator->next();
    }  // next()

    /** -----------------------------------------------------------------------------------------
     * Note that calling rewind() rewinds the entire directory scan, not the current file. After
     * rewinding the directory scan, reset any pointers and call valid() to ensure that we are
     * pointing at the first record in the first non-empty file.
     *
     * @see Iterator::rewind()
     * ------------------------------------------------------------------------------------------
     */

    public function rewind()
    {
        $this->handle->rewind();
        $this->numFilesScanned = 0;
        $this->currentFileIterator = null;
        $this->valid();
    }  // rewind()

    /** -----------------------------------------------------------------------------------------
     * This function, along with initializeCurrentFileIterator(), is the meat of the scanner. The
     * pseudocode for a foreach loop is:
     *
     * $iterator->rewind();
     * while ( $iterator->valid() ) {
     *     $iteraor->current(), $iterator->key();
     *     $iterator->next();
     * }
     *
     * Since we are using 2 iterators (the list of files and the list of records in each of those
     * files), a valid position means that we have a valid file and a valid record in that file. If
     * we have reached the end of the record list for a particular file (and a file could be empty
     * and contain no records) then we need to move on to the next file in the list. Only after we
     * have processed all files and all records in the final file should valid() return FALSE.
     *
     * @return boolen TRUE if the current position is valid, FALSE otherwise.
     *
     * @see Iterator::valid()
     * ------------------------------------------------------------------------------------------
     */

    public function valid()
    {
        // Ensure the handle is valid since there may be no files matching the specified criteria or
        // we could be at the end of the file list.

        if ( ! $this->handle->valid() ) {
            return false;
        }

        // If this is the first time we are traversing the directory tree, be sure to initialize the
        // referene to the current file iterator.

        if ( null === $this->currentFileIterator ) {

            // By default the key is the path and the value is an SplFileInfo object.
            // http://php.net/manual/en/class.splfileinfo.php

            return $this->initializeCurrentFileIterator($this->handle->key());

        } else {

            // If there are records available in the current file, return TRUE. If not, we will need
            // to move on to the next file.

            if ( $this->currentFileIterator->valid() ) {
                return true;
            } else {

                // Since a file could be empty, move on to the next file if we initialize a file
                // that contains no valid records.

                $this->handle->next();

                while ($this->handle->valid() && ! $this->initializeCurrentFileIterator($this->handle->key()) ) {
                    $this->handle->next();
                }

                return $this->currentFileIterator->valid();
            }
        }

    }  // valid()

    /** -----------------------------------------------------------------------------------------
     * Set up the internal file iterator for the specified file. This will create a handler based on
     * the configuration and parse the file using that handler. If the file is empty, then the file
     * handler's valid() metbod will return FALSE.
     *
     * @param string $filename The filename that we are initializing
     *
     * @returns boolean The value of valid() for the current file handler.
     * ------------------------------------------------------------------------------------------
     */

    private function initializeCurrentFileIterator($filename)
    {
        // SplFileInfo::getExtension() is not defined until PHP 5.3.6

        $extension = '';
        if ( false !== ($pos = strrpos($filename, '.')) ) {
            $extension = substr($filename, $pos + 1);
        }

        $this->logger->debug(
            sprintf('Scanning file: %s (%s)', $filename, $extension)
        );

        // Use the file extension and use it to look up a handler. If no extension was found, use
        // the default handler. NOTE: We are cloning the handler template because it is an object
        // and will be passed by reference otherwise.

        if ( array_key_exists($extension, $this->handlerTemplateList) ) {
            $handlerConfig = clone $this->handlerTemplateList[$extension];
        } else {
            $handlerConfig = clone $this->handlerTemplateList[self::DEFAULT_HANDLER_KEY];
        }

        // Inject the current file data into the handler config and parse the file

        $handlerConfig->name = basename($filename);
        $handlerConfig->path = $filename;
        $options = new DataEndpointOptions((array) $handlerConfig);
        $fileHandler = \ETL\DataEndpoint::factory($options, $this->logger);

        if ( ! $fileHandler instanceof iStructuredFile ) {
            $this->logAndThrowException(
                sprintf("%s does not implement iStructuredFile", $fileHandler)
            );
        }

        $fileHandler->verify();
        $fileHandler->parse();

        $this->currentFileIterator = $fileHandler;
        $this->currentFilename = $filename;
        $this->numFilesScanned++;
        $this->numRecordsParsed += $fileHandler->count();

        return $this->currentFileIterator->valid();

    }  // initializeCurrentFileIterator()

    /** -----------------------------------------------------------------------------------------
     * @see iDataEndpoint::__toString()
     * ------------------------------------------------------------------------------------------
     */

    public function __toString()
    {
        return sprintf('%s (name=%s, path=%s)', get_class($this), $this->name, $this->path);
    }  // __toString()
}  // class DirectoryScanner
