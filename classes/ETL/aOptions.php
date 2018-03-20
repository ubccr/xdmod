<?php

/** =========================================================================================
 * Abstract class for defining general option sets to the ETL process. All option sets
 * have an optional list of required options and the list of options themselves. Options
 * may have any name and value and options that are listed as required may not have a
 * value that is NULL or an empty string. Implementing the Iterator interface allows us to
 * treat the $options array of this class as public propertes and iterate over them.
 *
 * A verification method checks for the existnace of all required options and a generic getter
 * method returns a requested option, or NULL if it does not exist. It is expected that extending
 * classes will implement a setter method that can perform any required validation on options
 * specific to that implementation.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-10-15
 * ==========================================================================================
 */

namespace ETL;

use Exception;

// Extending stdClass allows us to use aOptions with when a general class is used, such as verifying
// required keys in an aOptions class or a standard parsed JSON object.

abstract class aOptions extends \stdClass implements \Iterator
{
    // The list of required options. These options cannot be set to NULL or an empty string.
    protected $requiredOptions = array(
        "name",
        "class",
        "factory",
        "enabled",
        "paths"
        );

    // Associative array used to store the options. Supported options are given a default value and
    // possible verificaiton in __set() but additional options can be added as well.
    protected $options = array(
        // The name of this ingestor
        "name" => null,

        // PHP class name that implements this ingestor
        "class" => null,

        // The PHP namespace for instantiating an action. Useful for testing.
        "namespace" => null,

        // Name of the factory class for creating objects of this type to be set by the extending class
        // ** Must include the namespace of the factory. **
        "factory" => null,

        // Optional description for this ingestor
        "description" => null,

        // TRUE if this aggregator is enabled
        "enabled" => true,

        // Object containing path information for the various directories used by the ETL process (table
        // configs, data, etc.)
        "paths" => null,

        // File containing definitions of the ETL destination table, source query, etc. May be null
        // if an action does not require it.
        "definition_file" => null,

        // By default, do not truncate the destination data
        "truncate_destination" => false,

        // Should an exception thrown by this action stop the ETL process?
        "stop_on_exception" => true
        );

    /* ------------------------------------------------------------------------------------------
     * Constructor. Optionally initialize the options using key/value pairs from an associative array
     *
     * @param $options An optional associative array used to initialize the values of this object
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(array $options = null)
    {
        if ( null === $options ) {
            return;
        }

        foreach ( $options as $key => $value ) {
            // Don't set using the $options array directly, be sure to use __set() to allow extending
            // classes to perform input verificaiton
            $this->$key = $value;
        }

    } //  __construct()

    /* ------------------------------------------------------------------------------------------
     * Verify that any required options are present.  We do this here rather than in multiple places
     * lower in the code.
     * ------------------------------------------------------------------------------------------
     */

    public function verify()
    {
        $missingOptions = array();

        // Verify requred options

        foreach ( $this->requiredOptions as $opt ) {
            if ( ! array_key_exists($opt, $this->options)
                 || null === $this->options[$opt]
                 || "" === $this->options[$opt] ) {
                $missingOptions[] = $opt;
            }
        }

        if ( 0 != count($missingOptions) ) {
            $msg = get_class($this) . ": Required options not provided: " . implode(", ", $missingOptions);
            throw new Exception($msg);
        }

        return true;
    }  // verify()

    /* ------------------------------------------------------------------------------------------
     * On set, verify that a specific property is the expected type and return the verified (and
     * possibly transformed) value.
     *
     * @param $property The name of the property to check
     * @param $value The value of the property
     *
     * @return $value, possibly transforemed to meet type requirements
     *
     * @throw Exception if the value is invalid
     * ------------------------------------------------------------------------------------------
     */

    protected function verifyProperty($property, $value)
    {

        switch ( $property ) {

            case 'enabled':
            case 'truncate_destination':
            case 'stop_on_exception':
                $origValue = $value;
                $value = \xd_utilities\filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ( null === $value ) {
                    $msg = get_class($this) . ": '$property' must be a boolean (type = " . gettype($origValue) . ")";
                    throw new Exception($msg);
                }
                break;

            case 'name':
            case 'definition_file':
                if ( ! is_string($value) ) {
                    $msg = get_class($this) . ": '$property' must be a string (type = " . gettype($value) . ")";
                    throw new Exception($msg);
                }
                break;

            case 'paths':
                if ( ! is_object($value) ) {
                    $msg = get_class($this) . ": '$property' must be an object (type = " . gettype($value) . ")";
                    throw new Exception($msg);
                }
                break;

            default:
                break;
        }

        return $value;

    }  // verifyProperty()

    /* ------------------------------------------------------------------------------------------
     * Verify that a required option has been set.
     *
     * @param $property The name of the property to set
     * @param $value The new value of the property
     *
     * @return TRUE if the value is valid
     *
     * @throw Exception if the value is invalid
     * ------------------------------------------------------------------------------------------
     */

    protected function verifyRequiredProperty($property, $value)
    {
        if ( ! in_array($property, $this->requiredOptions) ) {
            return;
        }

        // Required parameters are not allowed to be empty.

        if ( null === $value || "" === $value ) {
            $msg = get_class($this) . ": '$property' is a required parameter and cannot be empty or NULL";
            throw new Exception($msg);
        }

        return true;
    }  // verifyRequiredProperty()

    /* ------------------------------------------------------------------------------------------
     * Generic setter method for properties. Some input verification is performed.
     *
     * @param $property The name of the property to set
     * @param $value The new value of the property
     *
     * @throw Exception If a required parameter is given a null or empty value
     * @throw Exception If a parameter fails verification
     * @return The object
     * ------------------------------------------------------------------------------------------
     */

    abstract public function __set($property, $value);

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

    /** -----------------------------------------------------------------------------------------
     * @see Iterator::current()
     * ------------------------------------------------------------------------------------------
     */

    public function current()
    {
        if ( ! $this->valid() ) {
            return false;
        }
        return current($this->options);
    }  // current()

    /** -----------------------------------------------------------------------------------------
     * @see Iterator::key()
     * ------------------------------------------------------------------------------------------
     */

    public function key()
    {
        return key($this->options);
    }  // key()

    /** -----------------------------------------------------------------------------------------
     * @see Iterator::next()
     * ------------------------------------------------------------------------------------------
     */

    public function next()
    {
        next($this->options);
    }  // next()

    /** -----------------------------------------------------------------------------------------
     * @see Iterator::rewind()
     * ------------------------------------------------------------------------------------------
     */

    public function rewind()
    {
        reset($this->options);
    }  // rewind()

    /** -----------------------------------------------------------------------------------------
     * @see Iterator::valid()
     * ------------------------------------------------------------------------------------------
     */

    public function valid()
    {
        // Note that we can't check for values that are FALSE because that is a valid
        // data value.
        return null !== key($this->options);
    }  // valid()
}  // class aOptions
