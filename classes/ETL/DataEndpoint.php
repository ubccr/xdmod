<?php
/* ==========================================================================================
 * Singleton factory method for instantiating DataEndpoint objects. All endpoints must
 * implement the iDataEndpoint interface.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-09-25
 * ==========================================================================================
 */

namespace ETL;

use ETL\DataEndpoint\DataEndpointOptions;
use ETL\DataEndpoint\iDataEndpoint;
use Exception;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class DataEndpoint
{
    /**
     * Namespace, relative to the current namespace, where data endpoint classes are
     * defined. This is used to automatically search for defined endpoints.
     *
     * @const string
     */

    const DATA_ENDPOINT_RELATIVE_NS = 'DataEndpoint';

    /**
     * Fully namespaced interface that all data endpoints must implement
     *
     * @const string
     */

    const DATA_ENDPOINT_REQUIRED_INTERFACE = 'ETL\\DataEndpoint\\iDataEndpoint';

    /**
     * The name of the constant expected to be defined in all data endpoint classes. This
     * is used to identify the name that will be used to refer to the endpoint in
     * configuration files.
     *
     * @const string
     */

    const ENDPOINT_NAME_CONSTANT = 'ENDPOINT_NAME';

    /**
     * Associative array where the keys are data endpoint names and the values are fully
     * namespaced class names that implement those endpoints. This will be null until it
     * is initialized.
     *
     * @var array | null
     */

    private static $endpointClassMap = null;

    /** -----------------------------------------------------------------------------------------
     * Private constructor ensures the singleton can't be instantiated.
     * ------------------------------------------------------------------------------------------
     */

    private function __construct()
    {
    }

    /** -----------------------------------------------------------------------------------------
     * Return the list of data endpoint names that are currently configured/supported.
     *
     * @return array A list of data endpoint names
     * ------------------------------------------------------------------------------------------
     */

    public static function getDataEndpointNames()
    {
        return array_keys(self::getDataEndpointInfo());
    }  // getDataEndpointNames()

    /** -----------------------------------------------------------------------------------------
     * Return an associative array where the keys are data endpoint names and the values
     * are fully namespaced class names that implement those endpoints.
     *
     * @return array A list of data endpoint names
     * ------------------------------------------------------------------------------------------
     */

    public static function getDataEndpointInfo()
    {
        self::discover();
        return self::$endpointClassMap;
    }  // getDataEndpointInfo()

    /** -----------------------------------------------------------------------------------------
     * Discover the list of currently supported data endpoints and construct a list
     * mapping their names to the classes that implement them.  All data endpoints must
     * implement the interface specified in self::DATA_ENDPOINT_REQUIRED_INTERFACE and
     * also define a constant referenced by self::ENDPOINT_NAME_CONSTANT that is set to
     * the name of the endpoint (e.g., const ENDPOINT_NAME = 'file.json').  By
     * automatically discovering the data endpoints we do not need to modify this file
     * when new endpoints are created.
     *
     * @param boolean $force Set to TRUE to force re-discovery of endpoints
     * @param LoggerInterface $logger A Monolog Logger object or null to use the null logger.
     * ------------------------------------------------------------------------------------------
     */

    public static function discover($force = false, LoggerInterface $logger = null)
    {
        if ( null !== self::$endpointClassMap && ! $force ) {
            return;
        }

        // As per PSR-4 (http://www.php-fig.org/psr/psr-4/) the contiguous sub-namespace
        // names after the "namespace prefix" correspond to a subdirectory within a "base
        // directory", in which the namespace separators represent directory separators.
        // This means that we can assume subdirectories under the directory where this
        // file resides represent sub-namespaces.

        // The endpoint directory is relative to the directory where this file is found
        $endpointDir =  dirname(__FILE__)
            . DIRECTORY_SEPARATOR
            . strtr(self::DATA_ENDPOINT_RELATIVE_NS, '\\', DIRECTORY_SEPARATOR);
        $endpointDirLength = strlen($endpointDir);

        // Recursively traverse the directory where the endpoints live and discover any
        // defined endpoints.

        $dirIterator = new \RecursiveDirectoryIterator($endpointDir);
        $flattenedIterator = new \RecursiveIteratorIterator($dirIterator);
        self::$endpointClassMap = array();

        // The iterator returns SplFileInfo objects where the keys are the path to the
        // file.

        foreach ( $flattenedIterator as $path => $fileInfo ) {

            if ( $flattenedIterator->isDot() ) {
                continue;
            }

            // Set up an array so we can programmatically construct the namespace based on
            // the path to the class file

            $constructedNamespace = array(__NAMESPACE__, self::DATA_ENDPOINT_RELATIVE_NS);

            // Construct any additional sub-namespaces for subdirectories discovered under
            // the endpoint namespace (e.g. the subdirectory DataEndpoint/Filters
            // translates to the namespace DataEndpoint\Filters.

            $subDirNs = strtr(substr($fileInfo->getPath(), $endpointDirLength + 1), DIRECTORY_SEPARATOR, '\\');
            if ( "" !== $subDirNs) {
                $constructedNamespace[] = $subDirNs;
            }

            // Discover the class name based on the file name. The file name is expected
            // to be named <class>.<extension>

            // SplFileInfo::getExtension() is not defined until PHP 5.3.6

            $filename = $fileInfo->getFilename();
            $extension = '';
            if ( false !== ($pos = strrpos($filename, '.')) ) {
                $extension = substr($filename, $pos + 1);
            }

            // Handle the case where there is no extension
            $length =  ( strlen($extension) > 0 ? -1 * (strlen($extension) + 1) : strlen($filename) );
            $className = substr($filename, 0, $length);
            $constructedNamespace[] = $className;

            $nsClass = implode('\\', $constructedNamespace);

            try {
                $r = new \ReflectionClass($nsClass);

                // Ensure that the class is not abstract and implements the required
                // interface

                if ( ! $r->isAbstract() && $r->implementsInterface(self::DATA_ENDPOINT_REQUIRED_INTERFACE) ) {

                    if ( $r->hasConstant(self::ENDPOINT_NAME_CONSTANT) ) {

                        $name = $r->getConstant(self::ENDPOINT_NAME_CONSTANT);
                        if ( ! array_key_exists($name, self::$endpointClassMap) ) {
                            self::$endpointClassMap[$name] = $r->getName();
                        } elseif ( null !== $logger ) {
                            $logger->warning(
                                sprintf(
                                    "%s Endpoint with name '%s' already exists, skipping",
                                    __CLASS__,
                                    $name
                                )
                            );
                        }

                    } elseif ( null !== $logger ) {

                        $logger->warning(
                            sprintf(
                                "%s Class '%s' does not define %s, skipping",
                                __CLASS__,
                                $r->getName(),
                                self::ENDPOINT_NAME_CONSTANT
                            )
                        );

                    }
                }
            } catch ( \ReflectionException $e ) {
                // The class does not exist
                continue;
            }
        }
    }  // discover()

    /** -----------------------------------------------------------------------------------------
     * Factory pattern to instantiate a DataEndpoint based on the options.
     *
     * @param DataEndpointOptions $options A DataEndpointOptions object containing options
     *   parsed from the ETL config.
     * @param LoggerInterface $logger A Monolog Logger object or null to use the null logger.
     *
     * @return iDataEndpoint A data endpoint object implementing the iDataEndpoint interface.
     *
     * @throws Exception If required options were not provided
     * @throws Exception If the requested class could not be found
     * @throws Exception If the instantiated class does not implement iDataEndpoint
     * ------------------------------------------------------------------------------------------
     */

    public static function factory(DataEndpointOptions $options, LoggerInterface $logger = null)
    {
        self::discover(false, $logger);
        $options->verify();

        // If the type is defined and has a mapping to an implementation, create a class for the type.

        if ( ! array_key_exists($options->type, self::$endpointClassMap) ) {
            $msg = sprintf("%s: Undefined data endpoint type: '%s'", __CLASS__, $options->type);
            if ( null !== $logger ) {
                $logger->error($msg);
            }
            throw new Exception($msg);
        }

        // Ensure that the class implements the interface
        $className = self::$endpointClassMap[$options->type];

        $endpoint = new $className($options, $logger);

        if ( ! $endpoint instanceof iDataEndpoint ) {
            $msg = sprintf("%s: %s does not implement iDataEndpoint", __CLASS__, $className);
            if ( null !== $logger ) {
                $logger->error($msg);
            }
            throw new Exception($msg);
        }

        return $endpoint;

    }  // factory()
}  // class DataEndpoint
