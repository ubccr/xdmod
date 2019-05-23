<?php
/**
 * Options (with defaults) supported by all data endpoints.  Options specifically defined in this
 * class are available and may have verification performed when they are set.  Additional options
 * may be added if defined in the configuration, but it will be up the the individual endpoint types
 * whether or not to use them.  We use an array along with the __set() magic method for optimum
 * flexibility.
 *
 * @see aOptions
 */

namespace ETL\DataEndpoint;

use ETL\aOptions;
use Exception;

class DataEndpointOptions extends aOptions
{

    /**
     * Optionally initialize the options using key/value pairs from an associative array
     *
     * @param $options An optional associative array used to initialize the values of this object
     */

    public function __construct(array $options = null)
    {
        // Override aOptions::$requiredOptions to a subset that make sense for a data endpoint.

        $this->requiredOptions = array("name", "factory", "type");

        // Add options with local overriding current

        $localOptions = array(
            // Name of the factory class for creating objects of this type to be set by the extending
            // class. ** Must include the namespace of the factory. **
            "factory" => "\\ETL\\DataEndpoint",

            // Endpoint type (see aDataEndpoint)
            "type" => null,

            // Database schema
            "schema" => null,

            // By default, we don't want to create database schemas that don't exist. This should be
            // done for destinations but not sources.
            "create_schema_if_not_exists" => false
        );

        $this->options = array_merge($this->options, $localOptions);

        // Apply any defaults passed in via the constructor

        parent::__construct($options);

    }

    /**
     * Generic setter method for properties not otherwise covered.
     *
     * @param string $property The property being set.
     * @param mixed $value The value of the property being set.
     */

    public function __set($property, $value)
    {
        // Perform input verificaiton.

        switch ( $property ) {

            case 'paths':
                if ( ! is_object($value) ) {
                    $msg = get_class($this) . ": paths must be an object";
                    throw new Exception($msg);
                }
                break;

            default:
                break;
        }

        $this->verifyProperty($property, $value);

        $this->options[$property] = $value;
        return $this;
    }
}
