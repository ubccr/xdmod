<?php
/* ==========================================================================================
 * Singleton factory method for instantiating DataEndpoint objects. All endpoints must implement the
 * iDataEndpoint interface.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-09-25
 * ==========================================================================================
 */

namespace ETL;

use ETL\DataEndpoint\DataEndpointOptions;
use ETL\DataEndpoint\iDataEndpoint;
use \Exception;
use \Log;

class DataEndpoint
{

    // Default namespace for ingestors, can be overriden in the ETL configuration file
    private static $defaultNs = "ETL\\DataEndpoint\\";

    // List of defined data endpoint types. These are defined as constants so they can be used in the
    // ETL configuration file.

    const TYPE_MYSQL = "mysql";
    const TYPE_POSTGRES = "postgres";
    const TYPE_ORACLE = "oracle";
    const TYPE_FILE = "file";
    const TYPE_JSONFILE = "jsonfile";
    const TYPE_DIRECTORY_SCANNER = "directoryscanner";
    const TYPE_REST = "rest";

    private static $supportedTypes = array(
        self::TYPE_MYSQL,
        self::TYPE_ORACLE,
        self::TYPE_POSTGRES,
        self::TYPE_FILE,
        self::TYPE_JSONFILE,
        self::TYPE_DIRECTORY_SCANNER,
        self::TYPE_REST
    );

    private static $classmap = array(
        self::TYPE_MYSQL => 'ETL\DataEndpoint\Mysql',
        self::TYPE_POSTGRES => 'ETL\DataEndpoint\Postgres',
        self::TYPE_ORACLE => 'ETL\DataEndpoint\Oracle',
        self::TYPE_FILE => 'ETL\DataEndpoint\File',
        self::TYPE_JSONFILE => 'ETL\DataEndpoint\JsonFile',
        self::TYPE_DIRECTORY_SCANNER => 'ETL\DataEndpoint\DirectoryScanner',
        self::TYPE_REST => 'ETL\DataEndpoint\Rest'
    );

    /* ------------------------------------------------------------------------------------------
     * Private constructor ensures the singleton can't be instantiated.
     * ------------------------------------------------------------------------------------------
     */

    private function __construct()
    {
    }

    /* ------------------------------------------------------------------------------------------
     * Factory pattern to instantiate a DataEndpoint based on the options.
     *
     * @param $options A DataEndpointOptions object containing options parsed from the ETL config.
     *
     * @return A data endpoint object implementing the iDataEndpoint interface.
     *
     * @throws Exception If required options were not provided
     * @throws Exception If the requested class could not be found
     * @throws Exception If the instantiated class does not implement iDataEndpoint
     * ------------------------------------------------------------------------------------------
     */

    public static function factory(DataEndpointOptions $options, Log $logger = null)
    {
        $options->verify();

        // If the type is defined and has a mapping to an implementation, create a class for the type.

        if ( ! array_key_exists($options->type, self::$classmap) ) {
            $msg = __CLASS__ . ": Undefined data endpoint type: '{$options->type}'";
            if ( null !== $logger ) {
                $logger->err($msg);
            }
            throw new Exception($msg);
        }

        // Ensure that the class implements the interface
        $className = self::$classmap[$options->type];

        $endpoint = new $className($options, $logger);

        if ( ! $endpoint instanceof iDataEndpoint ) {
            $msg = __CLASS__ . ": $className does not implement iDataEndpoint";
            if ( null !== $logger ) {
                $logger->err($msg);
            }
            throw new Exception($msg);
        }

        return $endpoint;

    }  // factory()
}  // class DataEndpoint
