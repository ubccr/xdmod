<?php
/* ==========================================================================================
 * Options (with defaults) supported by all ingestors.  Options specifically defined in this class
 * are available and may have verification performed when they are set.  Additional options may be
 * added if defined in the configuration, but it will be up the the individual ingestors whether or
 * not to use them.  We use an array along with the __set() magic method for optimum flexibility.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-10-15
 *
 * @see aOptions
 * ==========================================================================================
 */

namespace ETL\Ingestor;

use ETL\aOptions;
use \Exception;

class IngestorOptions extends aOptions
{

    /* ------------------------------------------------------------------------------------------
     * Constructor. Optionally initialize the options using key/value pairs from an associative array
     *
     * @param $options An optional associative array used to initialize the values of this object
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(array $options = null)
    {
        // Add required parameters with local overriding current

        $localRequiredOptions = array("utility", "source", "destination", "definition_file");
        $this->requiredOptions = array_merge($this->requiredOptions, $localRequiredOptions);

        // Add options with local overriding current

        $localOptions = array(

            // Name of the factory class for creating objects of this type to be set by the extending
            // class. ** Must include the namespace of the factory. **
            "factory" => "\\ETL\\Ingestor",

            // Utility DataEndpoint object
            "utility" => null,

            // Source DataEndpoint object
            "source" => null,

            // Destination DataEndpoint object
            "destination" => null,

            // By default use an un-buffered query so we don't run out of memory storing the result of large
            // ingestion queries.
            "buffered_query" => false,

            // Perform query optimizations, if posible, when both the source and destination endpoints are
            // the same database.
            "optimize_query" => true,

            // Disabling keys (if the engine supports it) may improve performance on bulk inserts
            // depending on the size of the table, the number of keys modified, and the method
            // used. This has become less important since moving to INSERT INTO ON DUPLICATE KEY
            // UPDATE from REPLACE INTO since the latter deleted and re-inserted the entire row
            // rather than updating fields in place. The option exists for flexibility. See
            // http://dev.mysql.com/doc/refman/5.7/en/alter-table.html
            "disable_keys" => false,

            // A list of the only resources that should be included for this action. This is mainly
            // used for actions that are resource-specific, but it is up to the action to heed this
            // setting.
            "include_only_resource_codes" => null,

            // A list of resources that should be excluded for this action. This is mainly used for
            // actions that are resource-specific, but it is up to the action to heed this setting.
            "exclude_resource_codes" => null,

            // Should the source data endpoint be ignored?
            //
            // This is useful for when the default source endpoint is a type
            // the ingestor can handle but the ingestor should not use any
            // source endpoint.
            //
            // This is only used by ingestors that don't require a
            // source data endpoint.
            "ignore_source" => false,

            // The ingestor uses INSERT INTO ON DUPLICATE KEY UPDATE by default because it performs
            // roughly 40% better than REPLACE INTO when updating rows that already exist in the
            // database. Setting this option to TRUE will force the ingestor to use LOAD DATA
            // INFILE...REPLACE INTO instead.
            "force_load_data_infile_replace_into" => false
            );

        $this->options = array_merge($this->options, $localOptions);

        // Apply any defaults passed in via the constructor

        parent::__construct($options);

    } //  __construct()

    /* ------------------------------------------------------------------------------------------
     * @see aOptions::verifyProperty()
     * ------------------------------------------------------------------------------------------
     */

    protected function verifyProperty($property, $value)
    {
        // Perform input verificaiton and possibly transformation

        switch ( $property ) {

            case 'buffered_query':
            case 'optimize_query':
            case 'disable_keys':
            case 'ignore_source':
            case 'force_load_data_infile_replace_into':
                $origValue = $value;
                $value = \xd_utilities\filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ( null === $value ) {
                    $msg = get_class($this) . ": '$property' must be a boolean (type = " . gettype($origValue) . ")";
                    throw new Exception($msg);
                }
                break;

            case 'include_only_resource_codes':
            case 'exclude_resource_codes':
                $value = ( is_array($value) ? $value : array($value) );
                foreach ( $value as $v ) {
                    if ( ! is_string($v) ) {
                        $msg = get_class($this) . ": resource code must be a string or array of strings (type = " . gettype($v) . ")";
                        throw new Exception($msg);
                    }
                }
                break;

            case 'utility':
            case 'source':
            case 'destination':
                if ( ! is_string($value) ) {
                    $msg = get_class($this) . ": '$property' must be a string (type = " . gettype($value) . ")";
                    throw new Exception($msg);
                }
                break;

            default:
                $value = parent::verifyProperty($property, $value);
                break;
        }

        return $value;

    }  // verifyProperty()

    /* ------------------------------------------------------------------------------------------
     * @see ETL\aOptions::__set()
     * ------------------------------------------------------------------------------------------
     */

    public function __set($property, $value)
    {
        $value = $this->verifyProperty($property, $value);
        $this->verifyRequiredProperty($property, $value);

        $this->options[$property] = $value;
        return $this;
    }  // __set()

}  // class IngestorOptions
