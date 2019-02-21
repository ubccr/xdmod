<?php
/* ==========================================================================================
 * Lightweight database model providing support for a set of pre-defined (property, value)
 * pairs and quoting. This class implements much of the functionality for handling
 * lightweight data objects. Properties are stored in an array and accessed using __get(),
 * __set(), and __isset().
 *
 * The general flow for creating and initializing an Entity object is shown below. Child
 * classes may override the methods shown to implement their own checks as needed.
 *
 * __construct()
 *     $this->initialize($config)
 *         $this->verifyRequiredProperties()
 *             foreach ( $config as $name => $value ) {
 *                 $this->__set($name, $value)
 *                     $this->namme = $this->filterAndVerifyValue($value);
 *             }
 *
 * The general steps to follow when extending this class are:
 *
 * 1. Define 2 private arrays, $localProperties and $localRequiredProperties, that specify
 *    the additional properties that a child object implements and requires.
 * 2. In the child constructor, call mergeProperties() to merge local properties into a
 *    master list prior to calling the parent constructor. This will allow all locally
 *    defined properties to be merged up the chain of extended objects and also preserve
 *    the default values for the properties.
 * 3. Override filterAndVerifyValue() in the child class to handle properties defined by
 *    that class.
 * 4. In some cases initialize() may need to be overriden. For example, to set an index
 *    name before calling parent::initialize() to set the values.
 * 5. Entity::__set() provides a mechanism to easily set values. If the child class
 *    implements any non-scalar data members the __set() will need to be overriden to
 *    handle that data. For example, a Query defines a "joins" property that is an array
 *    of Join objects.  Query::__set() should handle the creation of the Join objects and
 *    call parent::__set() for the scalar data. It may be useful to define additional
 *    methods such as addRecord() or addColumn() to append data to a property rather than
 *    overwriting it.
 * 6. Entity::__get() provides accessors to the data but it may be useful to define
 *    additional methods that allow you to address individual or named elements of
 *    associative arrays, for example.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-27
 * ==========================================================================================
 */

namespace ETL\DbModel;

use Log;
use stdClass;
use CCR\Loggable;
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;

class Entity extends Loggable
{
    // The list of required properties for this model. If extending classes define their
    // own required properties they should merge them in the constructor by calling
    // Entity::mergeProperties($localRequiredProperties).
    protected $requiredProperties = array();

    // Associative array of valid (property, initial value) pairs. Properties may be set
    // via __set(), initialize() or a custom method. . If extending classes define their
    // own properties they should merge them in the constructor by calling
    // Entity::mergeProperties($localProperties).
    protected $properties = array();

    // Associative array of valid (property, initial value) pairs. This contains the
    // original default values.
    private $defaultPropertyValues = array();

    // Character used to quote system identifiers. Mysql uses a backtick while postgres
    // and oracle use a single quote.
    protected $systemQuoteChar = '`';

    /* ------------------------------------------------------------------------------------------
     * The default type of the $config is a stdClass object. If a child class wishes to
     * implement a different type (such as a file) it is free to do so and then pass NULL
     * to this constructor.
     *
     * @see iEntity::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($config, $systemQuoteChar = null, Log $logger = null)
    {
        parent::__construct($logger);
        $this->setSystemQuoteChar($systemQuoteChar);

        // The configuration can be NULL (nothing is initialized), a string assumed to be
        // a path to a JSON file (the file is parsed), or a stdClass containing an
        // configuration object.

        if ( null !== $config ) {

            if ( is_string($config) ) {
                $config = $this->parseJsonFile($config, "Table Definition");
                // Support the table config directly or assigned to a "table_definition" key
                if ( isset($config->table_definition) ) {
                    $config = $config->table_definition;
                }
            }

            if ( is_object($config) ) {
                if ( ! $config instanceof stdClass ) {
                    $this->logAndThrowException(
                        sprintf("Config must be a stdClass object, '%s' given", get_class($config))
                    );
                }
                $this->initialize($config);
            }
        }

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Merge properties and required properties local to a child class into the master
     * property list. Also keep a list of the default values for all properties. This
     * should be called in the child class constructor to merge any local properties
     * defined.
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
        $this->defaultPropertyValues = array_merge($this->properties, $localProperties);
        return $this;
    }  // mergeProperties()

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::initialize()
     *
     * 1. Prior to initializing the properties, verify that all required properties are
     *    present in the $config object.
     * 2. The __set() method is used to actually set the value of each property, verified
     *    and filtered values, and ensure only supported properties will be set. This is
     *     enforced by __set().
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(stdClass $config)
    {
        $this->verifyRequiredProperties($config);

        foreach ( $config as $property => $value ) {
            // The actual assignment will be handled by __set() including a warning for
            // attempting to set an unsupported property
            $this->$property = $value;
        }

        return $this;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * Verify that all of the required properties are present in the configuration object.
     *
     * @param stdClass $config The configuration object that we are verifying
     *
     * @return TRUE if the configuration object was successfully verified, FALSE otherwise.
     *
     * @throw Exception if a the configuration object is missing a property
     * ------------------------------------------------------------------------------------------
     */

    protected function verifyRequiredProperties(stdClass $config)
    {
        $missing = array();

        foreach ( $this->requiredProperties as $required ) {
            if ( ! isset($config->$required) ) {
                $missing[] = $required;
            }
        }

        if ( 0 != count($missing) ) {
            $this->logAndThrowException(
                sprintf('Config missing required properties (%s)', implode(", ", $missing))
            );
        }

        return ( 0 == count($missing) );

    }  // verifyRequiredProperties()

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::verify()
     * ------------------------------------------------------------------------------------------
     */

    public function verify()
    {
        // Do nothing. Child classes should override this method as needed.
        return true;
    }  // verify()

    /* ------------------------------------------------------------------------------------------
     * Filter and verify values when they are set based on the property. Some properties
     * are true/false but may be specified as true, null, or YES depending on the input
     * source. Other properties may be empty strings when discovered from the database
     * which should be treated as null for our purposes. This is an empty method that
     * should be overriden by child classes to filter their locally defined values as
     * needed.
     *
     * In addition, errors should be thrown if values cannot be filtered to the correct
     * type.
     *
     * @param $property The property we are filtering
     * @param $value The value of the property as presented from the source
     *
     * @return The filtered value
     * ------------------------------------------------------------------------------------------
     */

    protected function filterAndVerifyValue($property, $value)
    {
        // Required properties cannot be NULL

        if ( in_array($property, $this->requiredProperties) && null === $value ) {
            $this->logAndThrowException(sprintf("Required property %s cannot be null", $property));
        } elseif ( null === $value ) {
            return $this->defaultPropertyValues[$property];
        }

        return $value;
    }  // filterAndVerifyValue()

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
     * @see iEntity::quote()
     * ------------------------------------------------------------------------------------------
     */

    public function quote($identifier)
    {
        // Don't quote the identifier if it's already been quoted

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
     * @see iEntity::toStdClass()
     *
     * Translate this entity into a simple stdClass object. An attempt will be made to
     * replicate the original configuration and any changes as closely as possible, but
     * child classes may need to extend this class to properly handle cComposite objects
     * or different representations of the data.
     * ------------------------------------------------------------------------------------------
     */

    public function toStdClass()
    {
        $data = new stdClass;

        // Should we implement a recursive method for handling arrays and objects here so
        // we don't need to define this method in composite children like Query and Table?

        foreach ( $this->properties as $property => $value) {
            $data->$property = $this->_toStdClass($value);
        }

        return $data;

    }  // toStdClass()

    /* ------------------------------------------------------------------------------------------
     * Perform a recursive conversion of a value to a stdClass. Any complex values (i.e.,
     * objects) should implement the iEntity interface so their toStdClass() methods can
     * be used to perform the conversion. If they do not, get_object_vars() will be used
     * instead.
     *
     * This method tries to preserve the data as it is represented in this and any child
     * classes, but a child class may need to extend this method to properly translate its
     * local data representation into a stdClass object capable of being used as a
     * configuration object for that class.
     *
     * @see iEntity::toStdClass()
     * ------------------------------------------------------------------------------------------
     */

    // @codingStandardsIgnoreLine
    private function _toStdClass($value)
    {
        // Note that an empty stdClass will be treated as an array and must be overriden
        // in a child class.

        if ( is_array($value) && count($value) > 0 ) {

            // Attempt to maintain objects or arrays. If all of the array keys are numeric
            // assume it is an array, otherwise treat it as an object.  Child classes may
            // need to extend this method if this doesn't represent the data correctly.

            $treatAsObject = array_reduce(
                array_keys($value),
                function ($carry, $item) {
                    return (is_string($item) && $carry);
                },
                true
            );

            $a = array();
            foreach ( $value as $k => $item ) {
                $a[$k] = $this->_toStdClass($item);
            }
             return ( $treatAsObject ? (object) $a : array_values($a) );
        } elseif ( is_object($value) ) {
            if ( $value instanceof iEntity ) {
                return $value->toStdClass();
            } else {
                // If Error, don't know how to convert the object
                $this->logger->trace(
                    sprintf("Object '%s' does not implement iEntity, using get_object_vars() to convert to stdClass", get_class($value))
                );
                return (object) get_object_vars($value);
            }
        } else {
            // This is a scalar value
            return $value;
        }

    }  // _toStdClass()

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::compare()
     * ------------------------------------------------------------------------------------------
     */

    public function compare(iEntity $cmp)
    {
        return ( $this === $cmp );
    }  // compare()

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::toJson()
     * ------------------------------------------------------------------------------------------
     */

    public function toJson()
    {
        return json_encode($this->toStdClass());
    }  // toJson()

    /* ------------------------------------------------------------------------------------------
     * Reset all of the properties of this object to their default (unconfigured)
     * values. This bypasses the usual checks enforced in filterAndVerifyValue() and
     * __set().
     *
     * @return This object
     * ------------------------------------------------------------------------------------------
     */

    public function resetPropertyValues()
    {
        foreach ( $this->defaultPropertyValues as $k => $v ) {
            $this->properties[$k] = $v;
        }

        return $this;

    }  // resetPropertyValues()

    /* ------------------------------------------------------------------------------------------
     * Log the reason that two entities failed comparison.
     *
     * @param string $property The property being compared.
     * @param string $srcValue The value of the source property.
     * @param string $compareValue The value being compared to the source value.
     * @param string|null $name Optional name of the entity being compared.
     * ------------------------------------------------------------------------------------------
     */
    protected function logCompareFailure($property, $srcValue, $compareValue, $name = null)
    {
        $classParts = explode('\\', get_class($this));
        $this->logger->debug(
            sprintf(
                // '%s%s: comparison for "%s" failed ("%s" != "%s")',
                '%s%s: values for "%s" differ ("%s" != "%s")',
                array_pop($classParts),  // Strip the namespace from the class name
                (null !== $name ? ' ' . $name : ''),
                $property,
                (null === $srcValue ? 'null' : $srcValue),
                (null === $compareValue ? 'null' : $compareValue)
            )
        );
    }

    /* ------------------------------------------------------------------------------------------
     * Generic setter method for scalar properties. This method will set simple properties
     * and perform input verification on individual properties, which works well for
     * simple objects. If a more complex operation is required to initialize a composite,
     * such as setting an array of objects that need to be instantiated then this method
     * should be overriden in the child class.
     *
     * NOTE: This method SETS the value of a property, it does not add to it. Use an
     * addXxx() method for that.
     *
     * NOTE: Setting a property to NULL essentially clears the value. If the cleared value
     * should be something other than NULL (an array, for instance) it should be handled
     * in a child class.
     *
     * @param $property The name of the property to set
     * @param $value The new value of the property
     *
     * @throw Exception If a required parameter is given a null or empty value
     * @throw Exception If a parameter fails verification
     * ------------------------------------------------------------------------------------------
     */

    public function __set($property, $value)
    {
        if ( array_key_exists($property, $this->properties) ) {
            $this->properties[$property] = $this->filterAndVerifyValue($property, $value);
        } else {
            $this->logger->warning(
                sprintf("%s: Attempt to set unsupported property: '%s'", get_class($this), $property)
            );
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
        if ( array_key_exists($property, $this->properties) ) {
            return $this->properties[$property];
        } else {
            $this->logger->warning(
                sprintf("%s: Attempt to access unsupported property: '%s'", get_class($this), $property)
            );
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
        return ( array_key_exists($property, $this->properties) && null !== $this->properties[$property] );
    }  // __isset()

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::__toString()
     * ------------------------------------------------------------------------------------------
     */

    public function __toString()
    {
        return get_class($this);
    }  // __toString()
}  // class Entity
