<?php
/**
 * File data endpoint implements a method for reading raw data files.
 */

namespace ETL\DataEndpoint;

use ETL\DataEndpoint\DataEndpointOptions;
use Log;

class File extends aDataEndpoint implements iDataEndpoint
{

    /**
     * @const string Defines the name for this endpoint that should be used in configuration
     * files. It also allows us to implement auto-discovery.
     */
    const ENDPOINT_NAME = 'file';

    /**
     * @var string The path to the file.
     */
    protected $path = null;

    /**
     * @var string File mode. See http://php.net/manual/en/function.fopen.php
     */
    protected $mode = 'r';

    /**
     * @see iDataEndpoint::__construct()
     */

    public function __construct(DataEndpointOptions $options, Log $logger = null)
    {
        parent::__construct($options, $logger);

        $requiredKeys = array("path");
        $this->verifyRequiredConfigKeys($requiredKeys, $options);

        $messages = array();
        $propertyTypes = array(
            'path' => 'string',
            'mode' => 'string'
        );

        if ( ! \xd_utilities\verify_object_property_types($options, $propertyTypes, $messages, true) ) {
            $this->logAndThrowException("Error verifying options: " . implode(", ", $messages));
        }

        if ( isset($options->mode) ) {
            $this->mode = $options->mode;
        }

        $this->path = $options->path;

        if ( isset($options->paths->data_dir) ) {
            $this->path = \xd_utilities\qualify_path($options->path, $options->paths->data_dir);
        }

        $this->generateUniqueKey();
    }

    /**
     * @see aDataEndpoint::generateUniqueKey()
     */

    protected function generateUniqueKey()
    {
        $this->key = md5(implode($this->keySeparator, array($this->type, $this->path, $this->mode)));
    }

    /**
     * @see iFile::getPath()
     */

    public function getPath()
    {
        return $this->path;
    }

    /**
     * @see iFile::getMode()
     */

    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @see iDataEndpoint::connect()
     */

    public function connect()
    {
        // The first time a connection is made the endpoint handle should be set.

        if ( null === $this->handle ) {

            $this->handle = @fopen($this->path, $this->mode);

            if ( false === $this->handle ) {
                $this->handle = null;
                $error = error_get_last();
                $this->logAndThrowException(
                    sprintf("Error opening file '%s': %s", $this->path, $error['message'])
                );
            }

        }

        return $this->handle;

    }

    /**
     * @see iDataEndpoint::disconnect()
     */

    public function disconnect()
    {
        if ( null !== $this->handle ) {

            if ( false === @fclose($this->handle) ) {
                $error = error_get_last();
                $msg = "Error closing file '{$this->path}': " . $error['message'];
                $this->logAndThrowException(
                    sprintf("Error closing file '%s': %s", $this->path, $error['message'])
                );
            }

            $this->handle = null;

        }

        return true;

    }

    /**
     * @see iDataEndpoint::verify()
     */

    public function verify($dryrun = false, $leaveConnected = false)
    {
        $readModes = array("r", "r+", "w+", "a+", "x+", "c+");
        $writeModes = array("r+", "w", "w+", "a", "a+", "x", "x+", "c", "c+");

        if ( ! in_array($this->mode, array_merge($readModes, $writeModes)) ) {
            $this->logAndThrowException("Unsupported mode '" . $this->mode . "'");
        }

        if ( ! is_file($this->path) ) {
            $this->logAndThrowException("Path '" . $this->path . "' is not a file");
        }

        if ( in_array($this->mode, $readModes) && ! is_readable($this->path) ) {
            $this->logAndThrowException("Path '" . $this->path . "' is not readable");
        }

        $fh = $this->connect();

        if ( ! $leaveConnected ) {
            $this->disconnect();
        }

        return true;
    }

    /**
     * @see iDataEndpoint::__toString()
     */

    public function __toString()
    {
        return sprintf('%s (name=%s, path=%s)', get_class($this), $this->name, $this->path);
    }
}
