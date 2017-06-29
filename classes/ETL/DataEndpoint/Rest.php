<?php
/* ==========================================================================================
 * REST data endpoint
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @data 2015-11-12
 * ==========================================================================================
 */

// Access the config options parser

namespace ETL\DataEndpoint;

use ETL\DataEndpoint\DataEndpointOptions;
use Log;

class Rest extends aDataEndpoint implements iDataEndpoint
{
    // The ENDPOINT_NAME constant defines the name for this endpoint that should be used
    // in configuration files. It also allows us to implement auto-discovery.

    const ENDPOINT_NAME = 'rest';

    // The base url for this endpoint
    protected $baseUrl = null;

    // The base url for this endpoint
    protected $sleepMicroseconds = null;

    /* ------------------------------------------------------------------------------------------
     * @see iDataEndpoint::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(DataEndpointOptions $options, Log $logger = null)
    {
        parent::__construct($options, $logger);

        $requiredKeys = array("base_url");
        $this->verifyRequiredConfigKeys($requiredKeys, $options);

        $this->baseUrl = $options->base_url;

        if ( null !== $options->sleep_seconds && is_numeric($options->sleep_seconds) ) {
            $seconds = (float) $options->sleep_seconds;
            $this->sleepMicroseconds = $seconds * 1000000;
        }

        $this->key = md5(implode($this->keySeparator, array($this->type, $this->name, $this->baseUrl)));

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see aDataEndpoint::verify()
     * ------------------------------------------------------------------------------------------
     */

    public function verify($dryrun = false, $leaveConnected = false)
    {
        // The first time a connection is made the endpoint handle should be set.

        $this->connect();
        if ( ! $leaveConnected ) {
            $this->disconnect();
        }

        return true;
    }  // verify()

    /* ------------------------------------------------------------------------------------------
     * @see aDataEndpoint::connect()
     * ------------------------------------------------------------------------------------------
     */

    public function connect()
    {
        // The first time a connection is made the endpoint handle should be set.

        $this->handle = curl_init($this->baseUrl);
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);

        return $this->handle;
    }  // connect()

    /* ------------------------------------------------------------------------------------------
     * @see aDataEndpoint::disconnect()
     * ------------------------------------------------------------------------------------------
     */

    public function disconnect()
    {
        curl_close($this->handle);
        return true;
    }  // disconnect()

    /* ------------------------------------------------------------------------------------------
     * @return The base url from the options
     * ------------------------------------------------------------------------------------------
     */

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }  // getBaseUrl()

    /* ------------------------------------------------------------------------------------------
     * @return The number of microseconds to sleep between REST calls
     * ------------------------------------------------------------------------------------------
     */

    public function getSleepMicroseconds()
    {
        return $this->sleepMicroseconds;
    }  // getSleepMicrosecondsl()

    /* ------------------------------------------------------------------------------------------
     * @see iDataEndpoint::__toString()
     * ------------------------------------------------------------------------------------------
     */

    public function __toString()
    {
        return "{$this->name} (" . get_class($this) . ", base_url = {$this->baseUrl})";
    }  // __toString()
}  // class Rest
