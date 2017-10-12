<?php
/* ------------------------------------------------------------------------------------------
 * @author Steve Gallo
 * @date 2015-09-25
 *
 * Singleton factory method for instantiating ingestors. An ingestor must implement the iIngestor
 * interface.
 * ------------------------------------------------------------------------------------------
 */

namespace ETL;

use ETL\Configuration\EtlConfiguration;
use ETL\Ingestor\IngestorOptions;
use Exception;
use Log;

class Ingestor
{
    // Default namespace for ingestors, can be overriden in the ETL configuration file
    private static $defaultIngestorNs = "ETL\\Ingestor\\";

    /* ------------------------------------------------------------------------------------------
     * Private constructor ensures the singleton can't be instantiated.
     * ------------------------------------------------------------------------------------------
     */

    private function __construct()
    {
    }

    /* ------------------------------------------------------------------------------------------
     * Factory pattern to instantiate an Ingestor based on the options and ETL configuration.
     *
     * @param $options An IngestorOptions object containing options parsed from the ETL config.
     * @param $etlConfig The entire ETL configuration object.
     *
     * @return An ingestor object implementing the iAction interface.
     *
     * @throws Exception If required options were not provided
     * @throws Exception If the requested class could not be found
     * @throws Exception If the instantiated class does not implement iAction
     * ------------------------------------------------------------------------------------------
     */

    public static function factory(IngestorOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        $options->verify();

        $className = $options->class;

        // If the class name does not include a namespace designation, use the namespace from the
        // ingestor configuration or the default namespace if not specified.

        if ( false === strstr($className, '\\') ) {
            if ( $options->namespace ) {
                $className = $options->namespace .
                    ( strpos($options->namespace, '\\') != strlen($options->namespace) - 1 ? "\\" : "" ) .
                    $className;
            } else {
                $className = self::$defaultIngestorNs . $className;
            }
        }

        if ( class_exists($className) ) {
            $ingestor = new $className($options, $etlConfig, $logger);
        } else {
            $msg = __CLASS__ . ": Error creating ingestor '{$options->name}', class '$className' not found";
            if ( null !== $logger ) {
                $logger->err($msg);
            }
            throw new Exception($msg);
        }

        if ( ! $ingestor instanceof iAction ) {
            $msg = __CLASS__ . ": $className does not implenment interface iAction";
            if ( null !== $logger ) {
                $logger->err($msg);
            }
            throw new Exception($msg);
        }

        return $ingestor;

    }  // factory()
}  // class Ingestor
