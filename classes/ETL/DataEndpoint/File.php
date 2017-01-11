<?php
/* ==========================================================================================
 * File data endpoint.
 * ==========================================================================================
 */

namespace ETL\DataEndpoint;

use ETL\DataEndpoint\DataEndpointOptions;
use \Log;

class File extends aDataEndpoint implements iDataEndpoint
{

    // The path to the file.
    protected $path = null;

    // File mode. See http://php.net/manual/en/function.fopen.php
    protected $mode = null;

    // The default directory for files that do not specify a full path
    protected $data_dir = null;

    /* ------------------------------------------------------------------------------------------
     * @see iDataEndpoint::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(DataEndpointOptions $options, Log $logger = null)
    {
        parent::__construct($options, $logger);

        $requiredKeys = array("path");
        $this->verifyRequiredConfigKeys($requiredKeys, $options);

        // Default to read-only mode
        $this->mode = ( null === $options->mode || "" == $options->mode ? "r" : $options->mode );

        $this->key = md5(implode($this->keySeparator, array($this->type, $this->path, $this->mode)));

        $this->path = $options->applyBasePath("paths->data_dir", $options->path);
    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Set the value of the path for this endpoint.
     *
     * @param $path The path to the file for this endpoint
     *
     * @return This object to support method chaining
     * ------------------------------------------------------------------------------------------
     */

    public function setPath($path)
    {
        if (! is_string($path)) {
            $msg = "Path must be a string";
            $this->logAndThrowException($msg);
        }

        return $this;
    }  // setPath()

    /* ------------------------------------------------------------------------------------------
     * @return The path to this file
     * ------------------------------------------------------------------------------------------
     */

    public function getPath()
    {
        return $this->path;
    } // getPath()

    /* ------------------------------------------------------------------------------------------
     * @see aDataEndpoint::connect()
     * ------------------------------------------------------------------------------------------
     */

    public function connect()
    {
        // The first time a connection is made the endpoint handle should be set.

        $this->handle = @fopen($this->path, $this->mode);
        if (false === $this->handle) {
            $error = error_get_last();
            $msg = "Error opening file '{$this->path}': " . $error['message'];
            $this->logAndThrowException($msg);
        }

        return $this->handle;
    }  // connect()

    /* ------------------------------------------------------------------------------------------
     * @see aDataEndpoint::disconnect()
     * ------------------------------------------------------------------------------------------
     */

    public function disconnect()
    {
        if (null === $this->handle) {
            return true;
        }
    
        if (false === @fclose($this->handle)) {
            $error = error_get_last();
            $msg = "Error closing file '{$this->path}': " . $error['message'];
            $this->logAndThrowException($msg);
        }
    
        $this->handle = null;

        return true;
    }  // disconnect()

    /* ------------------------------------------------------------------------------------------
     * @see iDataEndpoint::verify()
     * ------------------------------------------------------------------------------------------
     */

    public function verify($dryrun = false, $leaveConnected = false)
    {
        parent::verify();

        $readModes = array("r", "r+", "w+", "a+", "x+", "c+");
        $writeModes = array("r+", "w", "w+", "a", "a+", "x", "x+", "c", "c+");

        if (! is_string($this->path)) {
            $msg =  "Path '" . $this->path . "' is not a string";
            $this->logAndThrowException($msg);
        }

        if (! in_array($this->mode, array_merge($readModes, $writeModes))) {
            $msg = "Unsupported mode '{$this->mode}'";
            $this->logAndThrowException($msg);
        }
        
        if (in_array($this->mode, $readModes) && ! is_readable($this->path)) {
            $msg = "File '{$this->path}' is not readable";
            $this->logAndThrowException($msg);
        }

        $fh = $this->connect();
        if (! $leaveConnected) {
            $this->disconnect();
        }

        return true;
    }  // verify()
}  // class File
