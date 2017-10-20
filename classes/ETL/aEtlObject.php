<?php
/* ==========================================================================================
 * Abstract base class providing common functionality for all ETL objects. This is not meant to be
 * instantiated directly, but to be subclassed.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-11-01
 *
 * @see iAction
 * ==========================================================================================
 */

namespace ETL;

use ETL\DataEndpoint\DataEndpointOptions;
use Log;
use Exception;
use PDOException;
use stdClass;

abstract class aEtlObject extends Loggable
{
    // All ELT objects can be named
    protected $name = null;

    // Flag indicating whether or not initialization was performed on this action.
    protected $initialized = false;

    /* ------------------------------------------------------------------------------------------
     * Basic initializations
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(Log $logger = null, $name = null)
    {
        parent::__construct($logger);
        $this->name = $name;
    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::getName()
     * ------------------------------------------------------------------------------------------
     */

    public function getName()
    {
        return $this->name;
    }  // getName()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::getName()
     * ------------------------------------------------------------------------------------------
     */

    public function setName($name)
    {

        if ( ! is_string($name) ) {
            $msg = "Entity name must be a string";
            $this->logAndThrowException($msg);
        }

        $this->name = $name;

        return $this;

    }  // setName()

    /* ------------------------------------------------------------------------------------------
     * @return TRUE if initialization has been performed on this action.
     * ------------------------------------------------------------------------------------------
     */

    public function isInitialized()
    {
        return $this->initialized;
    }  // isInitialized()

    /* ------------------------------------------------------------------------------------------
     * Verify any data for this object.
     *
     * @return TRUE if verification was successful
     *
     * @throw Exception If verification failed
     * ------------------------------------------------------------------------------------------
     */

    public function initialize()
    {
        if ( null === $this->name ) {
            $this->logAndThrowException("Name is not set");
        }
        return true;
    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * Verify that the required keys are present in the object (e.g., configuration object or
     * options object).
     *
     * @param $requiredKeys An array containing all required keys
     * @param $config A stdClass configuration object
     *
     * @return TRUE if no keys were missing
     *
     * @throw Exception if any required keys were not found in the configuration object
     * ------------------------------------------------------------------------------------------
     */

    protected function verifyRequiredConfigKeys(array $requiredKeys, stdClass $config)
    {
        $missing = array();

        foreach ( $requiredKeys as $key ) {
            if ( ! isset($config->$key) ) {
                $missing[] = $key;
            }
        }

        if ( 0 != count($missing) ) {
            $msg = "Config missing required keys (" . implode(", ", $missing) . ")";
            $this->logAndThrowException($msg);
        }

        return true;

    }  // verifyRequiredConfigKeys()

    /* ------------------------------------------------------------------------------------------
     * Parse a JSON table configuration file.
     *
     * @param $filename The file containing the table configuration
     * @param $name Optional name for the file. Useful for error reporting.
     *
     * @return This object to support method chaining.
     *
     * @throw Exception If the file is does not exist or is not readable
     * @throw Exception If there is an error parsing the file
     * ------------------------------------------------------------------------------------------
     */

    protected function parseJsonFile($filename, $name = null)
    {
        $name = ( null === $name ? "JSON file" : $name );
        $opt = new DataEndpointOptions(array('name' => $name,
                                             'path' => $filename,
                                             'type' => "jsonfile"));
        $jsonFile = DataEndpoint::factory($opt, $this->logger);
        return $jsonFile->parse();
    }  // parseJsonFile()

    /* ------------------------------------------------------------------------------------------
     * Generate a string representation of the endpoint. Typically the name, plus other pertinant
     * information as appropriate.
     *
     * @return A string representation of the endpoint
     * ------------------------------------------------------------------------------------------
     */

    public function __toString()
    {
        return $this->name . " (" . get_class($this) . ")";
    }  // __toString()
}  // abstract class aEtlObject
