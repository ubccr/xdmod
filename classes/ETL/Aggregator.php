<?php
/* ==========================================================================================
 * Singleton factory method for instantiating aggregator action objects. An aggregator must
 * implement the iAction interface.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-09-25
 * ==========================================================================================
 */

namespace ETL;

use ETL\Configuration\EtlConfiguration;
use ETL\Aggregator\AggregatorOptions;
use Exception;
use Psr\Log\LoggerInterface;

class Aggregator
{
    // Default namespace for ingestors, can be overriden in the ETL configuration file
    private static $defaultAggregatorNs = "ETL\\Aggregator\\";

    /* ------------------------------------------------------------------------------------------
     * Private constructor ensures the singleton can't be instantiated.
     * ------------------------------------------------------------------------------------------
     */

    private function __construct()
    {
    }

    /* ------------------------------------------------------------------------------------------
     * Factory pattern to instantiate an Aggregator based on the options and ETL configuration.
     *
     * @param $options An AggregatorOptions object containing options parsed from the ETL config.
     * @param $etlConfig The entire ETL configuration object.
     *
     * @return An aggregator object implementing the iAction interface.
     *
     * @throws Exception If required options were not provided
     * @throws Exception If the requested class could not be found
     * @throws Exception If the instantiated class does not implement iAction
     * ------------------------------------------------------------------------------------------
     */

    public static function factory(AggregatorOptions $options, EtlConfiguration $etlConfig, LoggerInterface $logger = null)
    {
        $options->verify();

        $className = $options->class;

        // If the class name does not include a namespace designation, use the namespace from the
        // aggregator configuration or the default namespace if not specified.

        if ( false === strstr($className, '\\') ) {
            if ( $options->namespace ) {
                $className = $options->namespace .
                    ( strpos($options->namespace, '\\') != strlen($options->namespace) - 1 ? "\\" : "" ) .
                    $className;
            } else {
                $className = self::$defaultAggregatorNs . $className;
            }
        }

        if ( class_exists($className) ) {
            $aggregator = new $className($options, $etlConfig, $logger);
        } else {
            $msg = __CLASS__ . ": Error creating aggregator '{$options->name}', class '$className' not found";
            if ( null !== $logger ) {
                $logger->error($msg);
            }
            throw new Exception($msg);
        }

        if ( ! $aggregator instanceof iAction ) {
            $msg = __CLASS__ . ": $className does not implenment action interface iAction";
            if ( null !== $logger ) {
                $logger->error($msg);
            }
            throw new Exception($msg);
        }

        return $aggregator;

    }  // factory()
}  // class Aggregator
