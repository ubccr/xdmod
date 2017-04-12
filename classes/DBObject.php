<?php

/**
 * Class DBObject
 *
 * The intent of this class is to provide an easy way for child classes, which
 * are meant to represent the data contained within one row of a table,
 * an easy way of interacting with a PDO result set in which the rows have been
 * returned as arrays. In particular, this allows the knowledge of what is
 * expected / contained in these tables / classes to be defined at particular
 * point in time (i.e. git commit ) as opposed to spread throughout the code
 * utilizing these objects. It also allows the utilizing code to interact with
 * the class and its associated properties / functions as opposed to a simple
 * array.
 *
 * On a more technical note, it provides dynamic 'getter' and 'setter'
 * support for calls that follow the form 'getCamelCasePropertyName()' and
 * 'setCamelCasePropertyName($propertyName)' the property name is assumed to be
 * in the form: lcfirst(CamelCase(column_name)) => columnName.
 *
 * And for those who enjoy working with their classes in an array type manner.
 * ArrayAccess has been implemented such that 'offsetGet' corresponds to
 * 'getCamelCasePropertyName()', 'offsetSet' corresponds to
 * 'setCamelCasePropertyName($propertyName)' and 'offsetExists($offset)'
 * ensures that the '$offset' is defined in the $PROP_MAP and that there
 * is a property currently defined with a name that that matches '$offset';
 *
 * @author Ryan Rathsam <ryanrath@buffalo.edu>
 */
class DBObject implements \ArrayAccess {

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
}
