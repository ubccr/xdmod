<?php
/**
 * REST data endpoint
 */

// Access the config options parser

namespace ETL\DataEndpoint;

use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;
use Psr\Log\LoggerInterface;

class Rest extends aDataEndpoint implements iDataEndpoint
{
    /**
     * @const string Defines the name for this endpoint that should be used in configuration files.
     * It also allows us to implement auto-discovery.
     */

    const ENDPOINT_NAME = 'rest';

    /**
     * @var string The base url for this endpoint
     */
    protected $baseUrl = null;

    /**
     * @var integer The number of microseconds to sleep between REST requests.
     */
    protected $sleepMicroseconds = null;

    /**
     * @var Mysql Utility Mysql endpoint for REST parameters
     */
    protected $restParameterEndpoint = null;

    /**
     * @var integer The number of records to pull for the request.
     */
    protected $limit = null;

    /**
     * @var integer The number of records to skip for the request.
     */
    protected $offset = null;

    /**
     * @var integer The page number for the request records.
     */
    protected $page = null;

    /**
     * @see iDataEndpoint::__construct()
     */

    public function __construct(DataEndpointOptions $options, LoggerInterface $logger = null)
    {
        parent::__construct($options, $logger);

        $requiredKeys = array("base_url");
        $this->verifyRequiredConfigKeys($requiredKeys, $options);


        $this->baseUrl = $options->base_url;

        if ( null !== $options->sleep_seconds ) {
            if (! is_numeric($options->sleep_seconds) ) {
                $this->logAndThrowException("Sleep seconds is expected to be numeric, instead got " . gettype($options->sleep_seconds) );
            }
            $seconds = (float) $options->sleep_seconds;
            $this->sleepMicroseconds = $seconds * 1000000;
        }

        if ( null !== $options->parameter_endpoint ) {
            if ( ! is_object($options->parameter_endpoint) ) {
                $this->logAndThrowException("Parameter utility endpoint is expected to be object, instead got " . gettype($options->parameter_endpoint) );
            }

            $param_endpoint_options = new DataEndpointOptions();
            foreach ( $options->parameter_endpoint as $key => $value ) {
                $param_endpoint_options->$key = $value;
            }

            $this->restParameterEndpoint = DataEndpoint::factory($param_endpoint_options, $this->logger);
        }

        if (null !== $options->limit) {
            if (! is_numeric($options->limit) ) {
                $this->logAndThrowException("Request 'limit' is expected to be numeric, instead got " . gettype($options->limit) );
            }
            if ($options->limit <= 0) {
                $this->logAndThrowException("Request 'limit' must be greater than 0." . $options->limit );
            }
            $this->limit = $options->limit;
        }

        if (null !== $options->offset) {
            if (! is_numeric($options->offset) ) {
                $this->logAndThrowException("Request 'offset' is expected to be numeric, instead got " . gettype($options->offset) );
            }
            $this->offset = $options->offset;
        }

        if (null !== $options->page) {
            if (! is_numeric($options->page) ) {
                $this->logAndThrowException("Request 'page' is expected to be numeric, instead got " . gettype($options->page) );
            }
            $this->page = $options->page;
        }

        $this->generateUniqueKey();
    }

    /**
     * @see aDataEndpoint::generateUniqueKey()
     */

    protected function generateUniqueKey()
    {
        $this->key = md5(implode($this->keySeparator, array($this->type, $this->name, $this->baseUrl)));
    }

    /**
     * @see aDataEndpoint::verify()
     */

    public function verify($dryrun = false, $leaveConnected = false)
    {
        // The first time a connection is made the endpoint handle should be set.

        $this->connect();
        if ( ! $leaveConnected ) {
            $this->disconnect();
        }

        return true;
    }

    /**
     * @see aDataEndpoint::connect()
     */

    public function connect()
    {
        // The first time a connection is made the endpoint handle should be set.

        $this->handle = curl_init($this->baseUrl);
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);

        return $this->handle;
    }

    /**
     * @see aDataEndpoint::disconnect()
     */

    public function disconnect()
    {
        curl_close($this->handle);
        return true;
    }

    /**
     * @return The base url from the options
     */

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return The base url from the options
     */

    public function getRestParameterEndpoint()
    {
        return $this->restParameterEndpoint;
    }

    /**
     * @return The number of microseconds to sleep between REST calls
     */

    public function getSleepMicroseconds()
    {
        return $this->sleepMicroseconds;
    }

    /**
     * @return The number of records to pull for the request.
     */

    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return The number of records to skip for the request.
     */

    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return The number of records to skip for the request.
     */

    public function getPage()
    {
        return $this->page;
    }

    /**
     * @see iDataEndpoint::__toString()
     */

    public function __toString()
    {
        return "{$this->name} (" . get_class($this) . ", base_url = {$this->baseUrl})";
    }
}
