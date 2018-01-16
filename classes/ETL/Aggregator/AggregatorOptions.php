<?php
/* ==========================================================================================
 * Options (with defaults) supported by all aggregators.  Options specifically defined in this class
 * are available and may have verification performed when they are set.  Additional options may be
 * added if defined in the configuration, but it will be up the the individual aggregators whether
 * or not to use them.  We use an array along with the __set() magic method for optimum flexibility.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-10-15
 *
 * @see aOptions
 * ==========================================================================================
 */

namespace ETL\Aggregator;

use ETL\aOptions;
use ETL\DataEndpoint\iDataEndpoint;
use Exception;

class AggregatorOptions extends aOptions
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

        $localRequiredOptions = array("utility", "source", "destination", "definition_file", "aggregation_units");
        $this->requiredOptions = array_merge($this->requiredOptions, $localRequiredOptions);

        // Add options with local overriding current

        $localOptions = array(

            // Name of the factory class for creating objects of this type to be set by the extending
            // class. ** Must include the namespace of the factory. **
            "factory" => "\\ETL\\Aggregator",

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

            // Should we attempt to disable index keys if the engine supports it? This may improve
            // performance on bulk inserts but may not for aggregation. The option exists for flexibility.
            // See http://dev.mysql.com/doc/refman/5.7/en/alter-table.html
            "disable_keys" => false,

            // Perform an ANALYZE or OPTIMIZE TABLE following ingestion
            "analyze_table" => true,

            // A list of the only resources that should be included for this action. This is mainly
            // used for actions that are resource-specific, but it is up to the action to heed this
            // setting.
            "include_only_resource_codes" => null,

            // A list of resources that should be excluded for this action. This is mainly used for
            // actions that are resource-specific, but it is up to the action to heed this setting.
            "exclude_resource_codes" => null,

            // An array of aggregation units supported by this ingestor
            "aggregation_units" => null,

            // Prefix used to generate the table name along with the aggregation unit
            "table_prefix" => null,

            // Experimental batching of aggregation periods for performance testing

            // This flag must be set to TRUE to enable batching of aggregation periods
            "experimental_enable_batch_aggregation" => false,
            // Number of periods (day, month, quarter, year) per batch
            "experimental_batch_aggregation_periods_per_batch" => 10,
            // Threshold: The minimum number of periods required to enable batch aggregation
            "experimental_batch_aggregation_min_num_periods" => 25,
            // Threshold: The maximum number of days per batch allowed before batching isn't beneficial
            "experimental_batch_aggregation_max_num_days_per_batch" => 300

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
        switch ( $property ) {

            case 'buffered_query':
            case 'optimize_query':
            case 'disable_keys':
                $origValue = $value;
                $value = \xd_utilities\filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ( null === $value ) {
                    $msg = get_class($this) . ": '$property' must be a boolean (type = " . gettype($origValue) . ")";
                    throw new Exception($msg);
                }
                break;

            case 'aggregation_units':
            case 'include_only_resource_codes':
            case 'exclude_resource_codes':
                $value = ( is_array($value) ? $value : array($value) );
                foreach ( $value as $v ) {
                    if ( ! is_string($v) ) {
                        $msg = get_class($this) . ": '$property' must be a string or array of strings (type = " . gettype($v) . ")";
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
        // Perform input verificaiton and possibly transformation

        $value = $this->verifyProperty($property, $value);
        $this->verifyRequiredProperty($property, $value);

        $this->options[$property] = $value;
        return $this;
    }  // __set()
}  // class AggregatorOptions
