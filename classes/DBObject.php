<?php

/**
 * Class DBObject
 *
 * Provides basic functionality for objects that represent rows in database
 * tables. It does this via providing a few conveniences for working with the
 * results of sql queries ( string keyed array ) and populating the object
 * properties from these results. It also provides dynamic 'getter' and 'setter'
 * support for calls that follow the form 'getCamelCasePropertyName()' and
 * 'setCamelCasePropertyName($propertyName)' the property name is assumed to be
 * in the form: lcfirst(CamelCase(column_name)) => columnName.
 *
 * @author Ryan Rathsam <ryanrath@buffalo.edu>
 */
class DBObject implements \ArrayAccess, JsonSerializable {

    protected $PROP_MAP = array();

    /**
     * Default Constructor
     *
     * @param array $options the options used to configure this instance.
     **/
    public function __construct($options = array())
    {
        $properties = $this->PROP_MAP;

        foreach ($properties as $property => $value) {
            if (array_key_exists($property, $options)) {
                $this->$value = $options[$property];
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $this->propertyExists($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        if ($this->propertyExists($offset)) {
            $property = $this->PROP_MAP[$offset];
            return $this->$property;
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        if ($this->propertyExists($offset)) {
            $property = $this->PROP_MAP[$offset];
            $this->$property = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        if ($this->propertyExists($offset)) {
            $property = $this->PROP_MAP[$offset];
            unset($this->$property);
        }
    }

    /**
     * Attempt to determine whether or not this object has a property $name
     *
     * @param mixed $name the name of the property to check exists.
     * @return bool
     */
    protected function propertyExists($name)
    {
        $property = isset($this->PROP_MAP[$name]) ?  $this->PROP_MAP[$name] : null;
        return array_key_exists($property, get_object_vars($this));
    }

    /**
     * @inheritDoc
     **/
    public function __call($name, $arguments)
    {
        /* The following block of code dynamically generates 'getters' and
         * 'setters' based on the properties the class currently supports.
         * This frees child classes from needing to clutter their class space
         * with boiler plate functions.
         */

        $var = lcfirst(substr($name, 3));
        if ((strncasecmp($name, 'get', 3) === 0) &&
            property_exists($this, $var)
        ) {
            return $this->$var;
        } else if ((strncasecmp($name, 'set', 3) === 0) &&
            (property_exists($this, $var))
        ) {
            $this->$var = $arguments[0];
        }
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return array_reduce($this->PROP_MAP, function ($carry, $item) {
            $key = array_search($item, $this->PROP_MAP);
            $carry[$key] = $this->$item;
            return $carry;
        }, array());
    }
}
