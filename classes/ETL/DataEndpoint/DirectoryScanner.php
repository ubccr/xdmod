<?php
/** =========================================================================================
 * Directory Scanner Data Endpoint. The Directory Scanner is a wrapper around Structured
 * File endpoints that recursively scans a directory for files and instantiates Structured
 * File endpoint each file in a directory (or subdirectory) matching a set of optionally
 * specified criteria. It supports file name pattern matching and last modified dates. The
 * Directory Scanner implements the Iterator interface adepnd iteration spans the union of
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

class DirectoryScanner extends aDataEndpoint implements iStructuredFile, iComplexDataRecords
{
    /** -----------------------------------------------------------------------------------------
     * The ENDPOINT_NAME constant defines the name for this endpoint that should be used
     * in configuration files. It also allows us to implement auto-discovery.
     *
     * @const string
     */

    const ENDPOINT_NAME = 'directoryscanner';

    /** -----------------------------------------------------------------------------------------
     * The directory path that we will be scanning. This should be a fully qualified path.
     *
     * @var string
     * ------------------------------------------------------------------------------------------
     */

    protected $path = null;

    /** -----------------------------------------------------------------------------------------
     * An optional PCRE that files must match to be identified by the scanner. This
     * applies to the file portion of the path only.
     *
     * @var string | null
     * ------------------------------------------------------------------------------------------
     */

    protected $filePattern = null;

    /** -----------------------------------------------------------------------------------------
     * An optional PCRE that directories must match to be identified by the scanner.
     *
     * @var string | null
     * ------------------------------------------------------------------------------------------
     */

    protected $directoryPattern = null;

    /** -----------------------------------------------------------------------------------------
     * The maximum depth that we will recurse into the directory hierarchy. -1 indicates
     * no limit. The depth is calculated relative to the original path. For example, if the path
     * is /data/lives/here then all files in /data/lives/here are considered a depth of 1, files
     * in /data/lives/here/raw are a depth of 2, etc.
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
     * Multiple methods may be used to determine the last modified date of a file and these are
     * implicitly determined based on parameters specified.  This variable overrides that behavior
     * and explicitly specifies the methods to use.
     *
     * file - Use the last modified timestamp of the file, either via a regex on the filename or by
     *   calling stat() on the file itself. (default)
     * directory - Use the last modified timestamp of the directory
     *
     * @var array | null
     * ------------------------------------------------------------------------------------------
     */
    protected $lastModifiedMethods = null;

    /** -----------------------------------------------------------------------------------------
     * A regular expression used to determine the last modified time based on the filename.  The
     * matching string is converted to a timestamp using strtotime(). If specified, implies that the
     * last modification time will be taken from the filename rather than calling stat().
     *
     * @var string | null
     * ------------------------------------------------------------------------------------------
     */

    protected $lastModifiedFileRegex = null;

    /** -----------------------------------------------------------------------------------------
     * A regular expression used to determine the last modified time based on the directory. If the
     * directory matches the regex then the last modified times are compared to the timestamp
     * generated from the match.  If the timestamp falls within the specified last modified time
     * then the directory is traversed, otherwise it is not. Directories that do not match the regex
     * at all are also traversed, otherwise we would never get past the top level directory.
     *
     * The date string specified in the directory does not need to be contiguous (e.g., it may be
     * separated by slashes), but it must be able to be parsed by strtotime(). For example,
     * "2012-01" is properly parsed but "201201" returns 2019-01-17 and "2012/01" returns
     * 1970-01-01. To reconstruct a non-contiguous date, a parenthesized regex is used and the
     * individual sub-patterns are reconstructed according to $lastModifiedDirFormat.
     *
     * Example directories with matching patterns are:
     *
     * BASEDIR/YYYY/MM/HOSTNAME/YYYY-MM-DD
     * $lastModifiedDirRegex: /[0-9]{4}-[0-9]{2}-[0-9]{2}/
     * $lastModifiedDirFormat: null
     *
     * BASEDIR/HOSTNAME/YYYY/MM/DD
     * $lastModifiedDirRegex: /[0-9]{4}\/[0-9]{2}\/[0-9]{2}/
     * $lastModifiedDirFormat: null
     *
     * BASEDIR/HOSTNAME/YYYYMM
     * $lastModifiedDirRegex: /([0-9]{4})([0-9]{2})/
     * $lastModifiedDirRegexReformat: '$1-$2'
     *
     * @var string | null
     * ------------------------------------------------------------------------------------------
     */

    protected $lastModifiedDirRegex = null;

    /** -----------------------------------------------------------------------------------------
     * When a parenthesized regex is specified in $lastModifiedDirRegex, the format needed to
     * re-construct a timestamp based on the captured parenthesized sub-expressions can be specified
     * here. If no sub-expressions are provided or captured then this value is ignored. $1 refers to
     * the first captured sub-expression, $2 the second, and so on. These are replaced in the format
     * specified here.
     *
     * For example, given the directory "vortex/20180103" with regex
     * "/([0-9]{4})([0-9]{2})([0-9]{2})/" and format "$1-$2-$3" the resulting timestamp would be
     * "2018-01-03".
     *
     * @var string | null
     * ------------------------------------------------------------------------------------------
     */

    protected $lastModifiedDirRegexReformat = null;

    /** -----------------------------------------------------------------------------------------
     * A handler template that will be used to instantiate the handler for each file matched
     * by the directory scanner. The file name will be injected into the template.
     *
     * @var object | null
     * ------------------------------------------------------------------------------------------
     */

    protected $handlerTemplate = null;

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
     * @var \Iterator
     * ------------------------------------------------------------------------------------------
     */

    private $currentFileIterator = null;

    /** -----------------------------------------------------------------------------------------
     * The name of the first file that was parsed. This allows us to reset the iterator witout
     * re-parsing the first file.
     *
     * @var string
     * ------------------------------------------------------------------------------------------
     */

    private $firstFilename = null;

    /** -----------------------------------------------------------------------------------------
     * The iterator for the first file that was parsed. This allows us to reset the iterator witout
     * re-parsing the first file.
     *
     * @var \Iterator
     * ------------------------------------------------------------------------------------------
     */

    private $firstFileIterator = null;

    /**
     * The first record parsed from the first file.
     *
     * @var mixed
     */

    private $firstRecord = null;

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

        $requiredKeys = array('path', 'handler');
        $this->verifyRequiredConfigKeys($requiredKeys, $options);

        $messages = array();
        $propertyTypes = array(
            'path'                => 'string',
            'handler'             => 'object',
            'file_pattern'        => 'string',
            'directory_pattern'   => 'string',
            'recursion_depth'     => 'int',
            'last_modified_start' => 'string',
            'last_modified_end'   => 'string',
            'last_modified_methods'   => 'string',
            'last_modified_file_regex' => 'string',
            'last_modified_dir_regex'  => 'string',
            'last_modified_dir_regex_reformat' => 'string'
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
                        if ( isset($options->paths->data_dir) ) {
                            $this->logger->info(
                                sprintf("Qualifying relative path %s with %s", $this->path, $options->paths->data_dir)
                            );
                            $this->path = \xd_utilities\qualify_path($this->path, $options->paths->data_dir);
                        }
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

                case 'last_modified_methods':
                    $this->lastModifiedMethods = array_map('trim', explode(',', $value));
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

                case 'last_modified_file_regex':
                    if ( false === @preg_match($value, "") ) {
                        $error = error_get_last();
                        $this->logAndThrowException(
                            sprintf(
                                "Error setting %s (%s): %s",
                                $property,
                                $value,
                                ( null !== $error ? $error['message'] : 'Unknown error' )
                            )
                        );
                    } else {
                        $this->lastModifiedFileRegex = $value;
                    }
                    break;

                case 'last_modified_dir_regex':
                    if ( false === @preg_match($value, "") ) {
                        $error = error_get_last();
                        $this->logAndThrowException(
                            sprintf(
                                "Error setting %s (%s): %s",
                                $property,
                                $value,
                                ( null !== $error ? $error['message'] : 'Unknown error' )
                            )
                        );
                    } else {
                        $this->lastModifiedDirRegex = $value;
                    }
                    break;

                case 'last_modified_dir_regex_reformat':
                    $this->lastModifiedDirRegexReformat = $value;
                    break;

                case 'handler':
                    $validEndpoints = \ETL\DataEndpoint::getDataEndpointNames();
                    if ( ! isset($value->type) ) {
                        $this->logAndThrowException("Handler does not specify endpoint type");
                    } elseif ( ! in_array($value->type, $validEndpoints) ) {
                        $this->logAndThrowException(
                            sprintf(
                                "Unknown handler type '%s'. Valid types are: %s",
                                $value->type,
                                implode(', ', $validEndpoints)
                            )
                        );
                    } else {
                        $this->handlerTemplate = $value;

                        // If the paths block has been set in the options, add it to the handler template
                        // so it can take advantage of the paths passed down from the configuration.

                        if ( isset($options->paths) ) {
                            $this->handlerTemplate->paths = $options->paths;
                        }
                    }
                    break;

                default:
                    break;
            }
        }

        // Implictly discover the methods to use when determining the last modified time of a file
        // based on the last modified parameters specified. The default is 'file', unless a
        // directory regex is specified. Any values specified in last_modified_methods overrides
        // this.

        if (
            null === $this->lastModifiedMethods &&
            (null !== $this->lastModifiedStartTimestamp || null !== $this->lastModifiedEndTimestamp)
        ) {
            if ( null !== $this->lastModifiedDirRegex ) {
                $this->lastModifiedMethods = array('directory');
            } else {
                $this->lastModifiedMethods = array('file');
            }
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
     * @return int|null The minumum last modified timestamp for a file.
     * ------------------------------------------------------------------------------------------
     */

    public function getLastModifiedStartTime()
    {
        return $this->lastModifiedStartTimestamp;
    }  // getLastModifiedStartTime()

    /** -----------------------------------------------------------------------------------------
     * @return int|null The maximum last modified timestamp for a file.
     * ------------------------------------------------------------------------------------------
     */

    public function getLastModifiedEndTime()
    {
        return $this->lastModifiedEndTimestamp;
    }  // getLastModifiedEndTime()

    /** -----------------------------------------------------------------------------------------
     * @return string|null The regex used to determine the last modified time of a file based on
     * the filename.
     * ------------------------------------------------------------------------------------------
     */

    public function getLastModifiedFileRegex()
    {
        return $this->lastModifiedFileRegex;
    }  // getLastModifiedFileRegex()

    /** -----------------------------------------------------------------------------------------
     * @return object The handler template that will be used to create a configuration for the
     * file handlers.
     * ------------------------------------------------------------------------------------------
     */

    public function getHandlerTemplate()
    {
        return $this->handlerTemplate;
    }  // getHandlerTemplate()

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
            $directoryIterator = new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::FOLLOW_SYMLINKS);
            $iterator = $directoryIterator;
        } catch ( Exception $e ) {
            $this->logAndThrowException(
                sprintf("Error opening directory '%s': %s", $this->path, $e->getMessage())
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
            $dotDirFilterIterator = new \RecursiveCallbackFilterIterator(
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

        // If a directory regex and last modified times have been specified apply them here. Note
        // that we must use the RecursiveCallbackFilterIterator and not the CallbackFilterIterator
        // when applying it to the RecursiveDirectoryIterator.

        if (
            null !== $this->lastModifiedDirRegex &&
            null !== $this->lastModifiedMethods &&
            in_array('directory', $this->lastModifiedMethods) &&
            (null !== $this->lastModifiedStartTimestamp || null !== $this->lastModifiedEndTimestamp)
        ) {
            // PHP 5.3 does not allow us to reference the object in the callback
            $lmStartTs = $this->lastModifiedStartTimestamp;
            $lmEndTs = $this->lastModifiedEndTimestamp;
            $lmDirRegex = $this->lastModifiedDirRegex;
            $lmDirReformat = $this->lastModifiedDirRegexReformat;
            $logger = $this->logger;

            $this->logger->info(
                sprintf(
                    "Applying mtime directory filter: (start: %s, end: %s, dir_regex: %s%s)",
                    ( null === $lmStartTs ? "null" : $lmStartTs ),
                    ( null === $lmEndTs ? "null" : $lmEndTs ),
                    $lmDirRegex,
                    ( null !== $lmDirReformat ? ", dir_reformat: $lmDirReformat" : "" )
                )
            );

            $directoryRegexIterator = new \RecursiveCallbackFilterIterator(
                $iterator,
                function ($current, $key, $iterator) use ($lmStartTs, $lmEndTs, $lmDirRegex, $lmDirReformat, $logger) {

                    // Do not traverse any directories that match the last modified pattern and fall
                    // outside of the requested last modified range. Directories that do not match
                    // the last modified pattern must be traversed or we won't get past the root
                    // directory.  We must also traverse individual files or nothing will make it
                    // past this filter.
  
                    if ( $current->isDir() ) {
                        $logger->debug(sprintf("Examine directory: %s", $key));

                        $matches = array();
                        if ( 0 !== preg_match($lmDirRegex, $key, $matches) ) {

                            // Reconstruct the timestamp if we have captured sub-expressions and a
                            // re-format was specified

                            $numMatches = count($matches);
                            $tsString = $matches[0];
                            if ( $numMatches > 1 && null !== $lmDirReformat ) {
                                $tsString = $lmDirReformat;
                                for ($i=1; $i < $numMatches; $i++) {
                                    $tsString = str_replace(sprintf('$%d', $i), $matches[$i], $tsString);
                                }
                            }
                            
                            $logger->debug(sprintf("Reformatted '%s' to '%s' using '%s'", $matches[0], $tsString, $lmDirReformat));

                            if ( false === ($ts = strtotime($tsString)) ) {
                                $logger->warning(
                                    sprintf(
                                        "Skipping directory '%s'. Regex '%s' matches but could not be converted to a timestamp.",
                                        $key,
                                        $lmDirRegex
                                    )
                                );
                                return false;
                            }
 
                            if ( null !== $lmStartTs && null !== $lmEndTs ) {
                                return $ts >= $lmStartTs && $ts <= $lmEndTs;
                            } elseif ( null !== $lmStartTs ) {
                                return $ts >= $lmStartTs;
                            } elseif ( null !== $lmEndTs ) {
                                return $ts <= $lmEndTs;
                            }

                        } elseif ( $iterator->hasChildren() ) {
                            return true;
                        }
                    } else {
                        // In order to process files, the directory that the file resides in must
                        // match the directory pattern.
                        //
                        if ( 0 !== preg_match($lmDirRegex, $current->getPath()) ) {
                            return true;
                        } else {
                            $logger->debug(sprintf("Skipping file where path does not match directory regex: %s", $key));
                        }
                    }
                    return false;
                }
            );
            $iterator = $directoryRegexIterator;
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

                        // Return TRUE only if both directory and file patterns match.

                        if ( null !== $dirPattern ) {
                            if ( false === ($match = @preg_match($dirPattern, $current->getPath())) ) {
                                $err = error_get_last();
                                $this->logAndThrowException(
                                    sprintf(
                                        "Error matching directory pattern '%s': %s",
                                        $dirPattern,
                                        $err['message']
                                    )
                                );
                            } elseif ( ! $match ) {
                                return false;
                            }
                        }

                        if ( null !== $filePattern ) {
                            if ( false === ($match = @preg_match($filePattern, $current->getFilename())) ) {
                                $err = error_get_last();
                                $this->logAndThrowException(
                                    sprintf(
                                        "Error matching file pattern '%s': %s",
                                        $filePattern,
                                        $err['message']
                                    )
                                );
                            } elseif ( ! $match ) {
                                return false;
                            }
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

        if (
            null !== $this->lastModifiedMethods &&
            in_array('file', $this->lastModifiedMethods) &&
            (null !== $this->lastModifiedStartTimestamp || null !== $this->lastModifiedEndTimestamp)
        ) {

            // PHP 5.3 does not allow us to reference the object in the callback
            $lmStartTs = $this->lastModifiedStartTimestamp;
            $lmEndTs = $this->lastModifiedEndTimestamp;
            $lmRegex = $this->lastModifiedFileRegex;
            $logger = $this->logger;

            $this->logger->info(
                sprintf(
                    "Applying mtime filter: (start: %s, end: %s%s)",
                    ( null === $lmStartTs ? "null" : $lmStartTs ),
                    ( null === $lmEndTs ? "null" : $lmEndTs ),
                    ( null !== $lmRegex ? ", file_regex: $lmRegex" : "" )
                )
            );

            try {
                $callbackIterator = new \CallbackFilterIterator(
                    $iterator,
                    function ($current, $key, $iterator) use ($lmStartTs, $lmEndTs, $lmRegex, $logger) {

                        // If the last modified regex is specified, use that to determine the
                        // modification time based on the filename.

                        if ( null !== $lmRegex ) {
                            $matches = null;
                            $retval = preg_match($lmRegex, $current->getFilename(), $matches);
                            if ( 0 === $retval ) {
                                return false;
                            } else {
                                if ( false === ($ts = strtotime($matches[0])) ) {
                                    $logger->warning(
                                        sprintf(
                                            "Skipping file '%s'. Regex '%s' matches but could not be converted to a timestamp.",
                                            $current->getFilename(),
                                            $lmRegex
                                        )
                                    );
                                    return false;
                                }
                            }
                        } else {
                            $ts = $current->getMTime();
                        }

                        if ( null !== $lmStartTs && null !== $lmEndTs ) {
                            return $ts >= $lmStartTs && $ts <= $lmEndTs;
                        } elseif ( null !== $lmStartTs ) {
                            return $ts >= $lmStartTs;
                        } elseif ( null !== $lmEndTs ) {
                            return $ts <= $lmEndTs;
                        }
                    }
                );
                $iterator = $callbackIterator;
            } catch ( Exception $e ) {
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
     * @see current()
     * ------------------------------------------------------------------------------------------
     */

    public function current()
    {
        if ( null === $this->currentFileIterator ) {
            return false;
        } else {
            return $this->currentFileIterator->current();
        }
    }  // current()

    /** -----------------------------------------------------------------------------------------
     * Return a composite key made up of the current file and the record number that we
     * are processing in that file.
     *
     * @see Iterator::key()
     * @see key()
     * ------------------------------------------------------------------------------------------
     */

    public function key()
    {
        if ( null === $this->currentFileIterator ) {
            return null;
        } else {
            return sprintf("%s[%s]", $this->currentFilename, $this->currentFileIterator->key());
        }
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
        if ( null !== $this->currentFileIterator ) {
            $this->currentFileIterator->next();
        }
    }  // next()

    /** -----------------------------------------------------------------------------------------
     * Note that calling rewind() rewinds the entire directory scan, not the current file. After
     * rewinding the directory scan, reset any pointers and call valid() to ensure that we are
     * pointing at the first record in the first non-empty file. The side effect of this is that we
     * must re-parse the first non-empty file.
     *
     * @see Iterator::rewind()
     * ------------------------------------------------------------------------------------------
     */

    public function rewind()
    {
        $this->handle->rewind();
        $this->numFilesScanned = 0;
        $this->numRecordsParsed = 0;

        // If we have already parsed the first file, reset the current file to the first file so
        // we don't need to re-parse the file.

        if ( null !== $this->firstFileIterator ) {
            $this->currentFileIterator = $this->firstFileIterator;
            $this->currentFilename = $this->firstFilename;
            $this->currentFileIterator->rewind();
        } else {
            $this->currentFileIterator = null;
            $this->valid();
        }

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
     * @return boolean TRUE if the current position is valid, FALSE otherwise.
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

            $key = $this->handle->key();
            $this->logger->debug(sprintf("Initializing first file iterator: %s", $key));

            $this->initializeCurrentFileIterator($key);

            // Save the first file iterator so we don't need to re-parse the first file on rewind

            $this->firstFileIterator = $this->currentFileIterator;
            $this->firstFilename = $this->currentFilename;

        } elseif ( ! $this->currentFileIterator->valid() ) {

            // If there are no records available in the current file we will need to move on to the
            // next file. Since a file could be empty, move on to the next file if we initialize a
            // file that contains no valid records.

            $this->logger->debug("Current file iterator no longer valid, checking next iterator.");
            $this->handle->next();

            while ($this->handle->valid() && ! $this->initializeCurrentFileIterator($this->handle->key()) ) {
                $this->handle->next();
            }

        }

        return $this->currentFileIterator->valid();

    }  // valid()

    /** -----------------------------------------------------------------------------------------
     * @see Countable::count()
     * ------------------------------------------------------------------------------------------
     */

    public function count()
    {
        return $this->numRecordsParsed;
    }  // count()

    /* ------------------------------------------------------------------------------------------
     * @see iFile::getMode()
     * ------------------------------------------------------------------------------------------
     */

    public function getMode()
    {
        if ( null === $this->currentFileIterator ) {
            return null;
        } else {
            return $this->currentFileIterator->getMode();
        }
    } // getMode()

    /** -----------------------------------------------------------------------------------------
     * Set up the internal file iterator for the specified file. This will create a handler based on
     * the configuration and parse the file using that handler. If the file is empty, then the file
     * handler's valid() metbod will return FALSE.
     *
     * @param string $filename The filename that we are initializing
     *
     * @return boolean The value of valid() for the current file handler.
     * ------------------------------------------------------------------------------------------
     */

    private function initializeCurrentFileIterator($filename)
    {
        $this->logger->info(sprintf('Scanning file: %s', $filename));

        // NOTE: We are cloning the handler template because it is an object and will be passed
        // by reference otherwise and we do not want to modify the template itself.

        $handlerConfig = clone $this->handlerTemplate;

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
        $record = $fileHandler->parse();

        // Save the first record parsed from the first file so we can return it from parse()

        if ( null === $this->firstRecord ) {
            $this->firstRecord = $record;
        }

        $this->currentFileIterator = $fileHandler;
        $this->currentFilename = $filename;
        $this->numFilesScanned++;
        $this->numRecordsParsed += $fileHandler->count();

        $this->logger->info(sprintf('Found %d records', $fileHandler->count()));

        return $this->currentFileIterator->valid();

    }  // initializeCurrentFileIterator()

    /** -----------------------------------------------------------------------------------------
     * @see iDataEndpoint::__toString()
     * ------------------------------------------------------------------------------------------
     */

    public function __toString()
    {
        $handlerString = (
            null !== $this->handlerTemplate
            ? sprintf(', handler=%s', $this->handlerTemplate->type)
            : ""
        );

        return sprintf('%s (name=%s, path=%s%s)', get_class($this), $this->name, $this->path, $handlerString);
    }  // __toString()

    /** -----------------------------------------------------------------------------------------
     * If parse() has been called we can pull the value of record separator from the file handler,
     * otherwise check the handler template.
     *
     * @see iStructuredFile::getRecordSeparator()
     * ------------------------------------------------------------------------------------------
     */

    public function getRecordSeparator()
    {
        if ( null !== $this->currentFileIterator ) {
            return $this->currentFileIterator->getRecordSeparator();
        } else {
            return (
                isset($this->handlerTemplate->record_separator)
                ? $this->handlerTemplate->record_separator
                : null
            );
        }
    }  // getRecordSeparator()

    /** -----------------------------------------------------------------------------------------
     * If parse() has been called we can pull the value of field separatp from the file handler,
     * otherwise check the handler template.
     *
     * @see iStructuredFile::getFieldSeparator()
     * ------------------------------------------------------------------------------------------
     */

    public function getFieldSeparator()
    {
        if ( null !== $this->currentFileIterator ) {
            return $this->currentFileIterator->getFieldSeparator();
        } else {
            return (
                isset($this->handlerTemplate->field_separator)
                ? $this->handlerTemplate->field_separator
                : null
            );
        }
    }  // getFieldSeparator()

    /** -----------------------------------------------------------------------------------------
     * If parse() has been called we can pull the value of header record from the file handler,
     * otherwise check the handler template.
     *
     * @see iStructuredFile::hasHeaderRecord()
     * ------------------------------------------------------------------------------------------
     */

    public function hasHeaderRecord()
    {
        if ( null !== $this->currentFileIterator ) {
            return $this->currentFileIterator->getRecordFieldNames();
        } else {
            return (
                isset($this->handlerTemplate->header_record)
                ? $this->handlerTemplate->header_record
                : true
            );
        }
    }  // hasHeaderRecord()

    /** -----------------------------------------------------------------------------------------
     * If parse() has been called we can pull the field names from the file handler, otherwise
     * check the handler template.
     *
     * @see iStructuredFile::getRecordFieldNames()
     * ------------------------------------------------------------------------------------------
     */

    public function getRecordFieldNames()
    {
        if ( null !== $this->currentFileIterator ) {
            return $this->currentFileIterator->getRecordFieldNames();
        } else {
            return (
                isset($this->handlerTemplate->field_names)
                ? $this->handlerTemplate->field_names
                : null
            );
        }
    }  //getRecordFieldNames()

    /** -----------------------------------------------------------------------------------------
     * If parse() has been called we can pull the discovered field names from the file handler,
     * otherwise return NULL.
     *
     * @see iStructuredFile::getDiscoveredRecordFieldNames()
     * ------------------------------------------------------------------------------------------
     */

    public function getDiscoveredRecordFieldNames()
    {
        if ( null !== $this->currentFileIterator ) {
            return $this->currentFileIterator->getDiscoveredRecordFieldNames();
        } else {
            return null;
        }
    } // getDiscoveredRecordFieldNames()

    /** -----------------------------------------------------------------------------------------
     * If parse() has been called we can pull the attached filter list from the file handler,
     * otherwise return an empty array().
     *
     * @see iStructuredFile::getAttachedFilters()
     * ------------------------------------------------------------------------------------------
     */

    public function getAttachedFilters()
    {
        if ( null !== $this->currentFileIterator ) {
            return $this->currentFileIterator->getAttachedFilters();
        } else {
            return array();
        }
    }  // getAttachedFilters()

    /** -----------------------------------------------------------------------------------------
     * For a structured file endpoint, parse() must be called prior to iterating over the
     * data, but the DirectoryScanner endpoint will automatically handle this when it
     * initializes each file handler. This method is for compatibility with
     * iStructuredFile behavior.
     *
     * @see iStructuredFile::parse()
     * ------------------------------------------------------------------------------------------
     */

    public function parse()
    {
        // If current file iterator is NULL then initialize it

        if ( null === $this->currentFileIterator ) {
            $this->rewind();
            $this->valid();
        }

        return $this->firstRecord;

    }  // parse()

    /** -----------------------------------------------------------------------------------------
     * This class does not support complex data records directly, but relies on the handler.
     *
     * @see iStructuredFile::supportsComplexDataRecords()
     * ------------------------------------------------------------------------------------------
     */

    public function supportsComplexDataRecords()
    {
        // If current file iterator is NULL then initialize it

        if ( null === $this->currentFileIterator ) {
            $this->valid();
        }

        return (
            null === $this->currentFileIterator
            ? false
            : $this->currentFileIterator->supportsComplexDataRecords()
        );

    }  // supportsComplexDataRecords()

    /** -----------------------------------------------------------------------------------------
     * This class does not support complex data records directly, but relies on the handler so
     * pass all iComplexDataRecords methods through to the handler if it supports them.
     *
     * @see iComplexDataRecords::validateDestinationMapSourceFields()
     * ------------------------------------------------------------------------------------------
     */

    public function validateDestinationMapSourceFields(array $destinationTableMap)
    {
        if ( $this->supportsComplexDataRecords() ) {
            return $this->currentFileIterator->validateDestinationMapSourceFields(
                $destinationTableMap
            );
        } else {
            $this->logAndThrowException(
                sprintf(
                    "Handler endpoint '%s' does not support complex data records",
                    $this->currentFileIterator
                )
            );
        }
    }  // validateDestinationMapSourceFields()

    /** -----------------------------------------------------------------------------------------
     * This class does not support complex data records directly, but relies on the handler so
     * pass all iComplexDataRecords methods through to the handler if it supports them.
     *
     * @see iComplexDataRecords::isComplexSourceField()
     * ------------------------------------------------------------------------------------------
     */

    public function isComplexSourceField($sourceField)
    {
        if ( $this->supportsComplexDataRecords() ) {
            return $this->currentFileIterator->isComplexSourceField($sourceField);
        } else {
            $this->logAndThrowException(
                sprintf(
                    "Handler endpoint '%s' does not support complex data records",
                    $this->currentFileIterator
                )
            );
        }
    }  // isComplexSourceField()

    /** -----------------------------------------------------------------------------------------
     * This class does not support complex data records directly, but relies on the handler so
     * pass all iComplexDataRecords methods through to the handler if it supports them.
     *
     * @see iComplexDataRecords::evaluateComplexSourceField()
     * ------------------------------------------------------------------------------------------
     */

    public function evaluateComplexSourceField($sourceField, $record, $invalidRefValue = null)
    {
        if ( $this->supportsComplexDataRecords() ) {
            return $this->currentFileIterator->evaluateComplexSourceField(
                $sourceField,
                $record,
                $invalidRefValue
            );
        } else {
            $this->logAndThrowException(
                sprintf(
                    "Handler endpoint '%s' does not support complex data records",
                    $this->currentFileIterator
                )
            );
        }
    }  // evaluateComplexSourceField()
}  // class DirectoryScanner
