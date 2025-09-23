<?php
/* ------------------------------------------------------------------------------------------
 * @author Steve Gallo
 * @date 2016-01-07
 *
 * Singleton factory method for instantiating generic actions. An action must implement the iAction
 * interface.
 * ------------------------------------------------------------------------------------------
 */

namespace ETL;

use ETL\Configuration\EtlConfiguration;
use ETL\Maintenance\MaintenanceOptions;
use Exception;
use Psr\Log\LoggerInterface;

class Maintenance
{
    // Default namespace for ingestors, can be overriden in the ETL configuration file
    private static $defaultIngestorNs = "ETL\\Maintenance\\";

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

    public static function factory(MaintenanceOptions $options, EtlConfiguration $etlConfig, LoggerInterface $logger = null)
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
            $action = new $className($options, $etlConfig, $logger);
        } else {
            $msg = __CLASS__ . ": Error creating action '{$options->name}', class '$className' not found";
            if ( null !== $logger ) {
                $logger->error($msg);
            }
            throw new Exception($msg);
        }

        if ( ! $action instanceof iAction ) {
            $msg = __CLASS__ . ": $className does not implenment interface iAction";
            if ( null !== $logger ) {
                $logger->error($msg);
            }
            throw new Exception($msg);
        }

        return $action;

    }  // factory()
}  // class Maintenance
