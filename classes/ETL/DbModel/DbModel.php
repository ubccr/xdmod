<?php
/* ==========================================================================================
 * Lightweight database model providing support for a set of pre-defined (property, value)
 * pairs and quoting.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-27
 * ==========================================================================================
 */

namespace ETL\DbModel;

use Log;
use stdClass;
use ETL\Loggable;
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;

class Entity extends Loggable
{
    // The list of required properties for this model.
    protected $requiredProperties = array();

    // Associative array of valid (property, initial value) pairs. Properties may be set via __set(),
    // initialize() or a custom method.
    protected $properties = array();

    // Character used to quote system identifiers. Mysql uses a backtick while postgres
    // and oracle use a single quote. This is set as a static variable so we can use it in
    // static scope in Table::discover()

    protected $systemQuoteChar = '`';

    /* ------------------------------------------------------------------------------------------
     * Construct a database entity object.
     *
     * @param string $systemQuoteChar The character used to quote database system identifiers
     * @param Log $logger PEAR Log object for system logging
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($systemQuoteChar = null, Log $logger = null)
    {
        parent::__construct($logger);
        $this->setSystemQuoteChar($systemQuoteChar);
    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Merge properties and required properties local to a child class into the master property list
     *
     * @param array $localRequiredProperties Required properties from a child class
     * @param array $localProperties Locally defined properties from a child class
     *
     * @return This object
     * ------------------------------------------------------------------------------------------
     */
    protected function mergeProperties(array $localRequiredProperties, array $localProperties)
    {
        $this->requiredProperties = array_merge($this->requiredProperties, $localRequiredProperties);
        $this->properties = array_merge($this->properties, $localProperties);
        return $this;
    }  // mergeProperties()

    /* ------------------------------------------------------------------------------------------
     * Initialize the object properties from a stdClass object. Only supported properties
     * will be set.
     *
     * @param stdClass $config An object containing (property, value) pairs to set.
     *
     * @return This object
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(stdClass $config)
    {
        foreach ( $config as $property => $value ) {

            /*
            if ( ! array_key_exists($property, $this->options) ) {
                $this->logAndThrowException("Property is not supported: '$property'");
            }
            */

            $this->$property = $value;

        }

        return $this;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * @return The character used to quote system identifiers
     * ------------------------------------------------------------------------------------------
     */

    public function getSystemQuoteChar()
    {
        return $this->systemQuoteChar;
    }  // getSystemQuoteChar()

    /* ------------------------------------------------------------------------------------------
     * @param $char The character used to quote system identifiers (may be empty)
     * ------------------------------------------------------------------------------------------
     */

    public function setSystemQuoteChar($char)
    {
        if ( null !== $char && ! is_string($char) ) {
            $this->logAndThrowException("System quote character must be a string");
        }

        $this->systemQuoteChar = $char;
        return $this;

    }  // setSystemQuoteChar()

    /* ------------------------------------------------------------------------------------------
     * Wrap a system identifier in quotes appropriate for the endpint if it is not already
     * quoted. For example, MySQL uses a backtick (`) to quote identifiers while Oracle
     * and Postgres using double quotes (").
     *
     * @param $identifier A system identifier (schema, table, column name)
     *
     * @return The identifier quoted appropriately for the endpoint
     * ------------------------------------------------------------------------------------------
     */

    public function quote($identifier)
    {
        if (0 === strpos($identifier, $this->systemQuoteChar)
            && (strlen($identifier) - 1) === strrpos($identifier, $this->systemQuoteChar) ) {
            return $identifier;
        } else {
        return $this->systemQuoteChar . $identifier . $this->systemQuoteChar;
        }
    }  // quote()

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
     * Generic setter method for properties. Some input verification is performed.
     *
     * @param $property The name of the property to set
     * @param $value The new value of the property
     *
     * @throw Exception If a required parameter is given a null or empty value
     * @throw Exception If a parameter fails verification
     * @return The object to suppo
     * ------------------------------------------------------------------------------------------
     */

    public function __set($property, $value)
    {
        if ( array_key_exists($property, $this->options) ) {
            $this->options[$property] = $value;
        } else {
            $this->logAndThrowException("Attempt to set unsupported property: '$property'");
        }
    }  // __set()

    /* ------------------------------------------------------------------------------------------
     * Generic getter method for properties not otherwise covered.
     *
     * @param $property The name of the property to retrieve
     *
     * @return The property, or NULL if the property doesn't exist.
     * ------------------------------------------------------------------------------------------
     */

    public function __get($property)
    {
        if ( array_key_exists($property, $this->options) ) {
            return $this->options[$property];
        }

        return null;
    }  // __get()

    /* ------------------------------------------------------------------------------------------
     * Return TRUE if the property exists and is not NULL.
     *
     * @param $property The name of the property to retrieve
     *
     * @return TRUE if the property exists and is not NULL, FALSE otherwise.
     * ------------------------------------------------------------------------------------------
     */

    public function __isset($property)
    {
        return ( array_key_exists($property, $this->options) && null !== $this->options[$property] );
    }  // __isset()
}  // class Entity
