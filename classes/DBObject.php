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
class DBObject {


    public function __construct($options)
    {
        /* We retrieve all of the properties defined by this class, check to see
         * check to see if a snake_case version of the properties exist in the
         * options supplied by the user. If one is found, then set this objects
         * property equal to the user supplied value. This provides a ridiculously
         * easy way to go from table rows -> populated classes.
         */

        $properties = get_object_vars($this);

        foreach($properties as $property => $value) {
            $name = $this->fromCamelCase($property);
            if (array_key_exists($name, $options)) {
                $this->$property = $options[$name];
            }
        }
    }

    /**
     * Accepts a string formatted in CamelCase and coverts the string to
     * snake_case.
     *
     * @param $input string the value to be converted
     *
     * @return string converted to snake_case
     */
    protected function fromCamelCase($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    /**
     * @inheritDoc
     */
    function __call($name, $arguments)
    {
        /* The following block of code dynamically generates 'getters' and
         * 'setters' based on the properties the class currently supports.
         * This frees child classes from needing to clutter their class space
         * with boiler plate functions.
         */

        $var = lcfirst(substr($name, 3));
        if ((strncasecmp($name, 'get', 3) === 0) &&
            (property_exists($this, $var))) {
            return $this->$var;
        } else if ((strncasecmp($name, 'set', 3) === 0) &&
            (property_exists($this, $var))) {
            $this->$var = $arguments[0];
        }

    }
}
