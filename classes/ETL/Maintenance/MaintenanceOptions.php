<?php
/* ==========================================================================================
 * Options (with defaults) supported by all maintenance actions.  Options specifically defined in
 * this class are available and may have verification performed when they are set.  Additional
 * options may be added if defined in the configuration, but it will be up the the individual
 * actions whether or not to use them.  We use an array along with the __set() magic method for
 * optimum flexibility.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-10-15
 *
 * @see aOptions
 * ==========================================================================================
 */

namespace ETL\Maintenance;

use ETL\aOptions;
use Exception;

class MaintenanceOptions extends aOptions
{
    /* ------------------------------------------------------------------------------------------
     * Constructor. Optionally initialize the options using key/value pairs from an associative array
     *
     * @param $options An optional associative array used to initialize the values of this object
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(array $options = null)
    {
        // Add options with local overriding current

        $localOptions = array(
            // Name of the factory class for creating objects of this type. ** Must include the namespace of
            // the factory **
            "factory" => "\\ETL\\Maintenance"
        );

        $this->options = array_merge($this->options, $localOptions);

        // Apply any defaults passed in via the constructor

        parent::__construct($options);

    } //  __construct()

    /* ------------------------------------------------------------------------------------------
     * @see ETL\aOptions::__set()
     * ------------------------------------------------------------------------------------------
     */

    public function __set($property, $value)
    {
        // Perform input verificaiton.

        switch ( $property ) {

            case 'enabled':
            case 'stop_on_exception':
            case 'truncate_destination':
                $origValue = $value;
                $value = \xd_utilities\filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ( null === $value ) {
                    $msg = get_class($this) . ": '$property' must be a boolean (type = " . gettype($origValue) . ")";
                    throw new Exception($msg);
                }
                break;

            case 'paths':
                if ( ! is_object($value) ) {
                    $msg = get_class($this) . ": paths must be an object";
                    throw new Exception($msg);
                }
                break;

            case 'aggregation_units':
                $value = ( is_array($value) ? $value : array($value) );
                foreach ( $value as $v ) {
                    if ( ! is_string($v) ) {
                        $msg = get_class($this) . ": '$property' must be a string or array of strings (type = " . gettype($v) . ")";
                        throw new Exception($msg);
                    }
                }
                break;

            default:
                break;
        }

        $this->verifyProperty($property, $value);

        $this->options[$property] = $value;
        return $this;
    }  // __set()
}  // class MaintenanceOptions
