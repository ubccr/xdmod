<?php
/* ==========================================================================================

 * REST service ingestor. Supports querying a REST endpoing and following previous/next urls in the
 * response and generatig requests based on the results of a query to the data warehouse.
 * Transformation and verificaiton of parameters and results is supported.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-02-05
 * ------------------------------------------------------------------------------------------
 */

namespace ETL\Ingestor;

use ETL\DataEndpoint\Rest;
use ETL\DataEndpoint\aRdbmsEndpoint;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\DbModel\Query;
use ETL\aOptions;
use ETL\iAction;
use ETL\VariableStore;

use PDO;
use Exception;
use Psr\Log\LoggerInterface;

class RestIngestor extends StructuredFileIngestor implements iAction
{
    // Parsed configuration options for REST request handling
    protected $restRequestConfig = null;

    // Parsed configuration options for REST response handling
    protected $restResponseConfig = null;

    // Column names for the destination table
    protected $destinationTableColumnNames = null;

    // Optional source query for additional parameters
    protected $etlSourceQuery = null;
    protected $etlSourceQueryResult = null;

    // Optional parameters for the rest call
    protected $restParameters = array();

    // The current url, useful for debugging
    protected $currentUrl = null;

    // This action does not (yet) support multiple destination tables. If multiple destination
    // tables are present, store the first here and use it.
    protected $etlDestinationTable = null;

    // Rest parameter helper endpoint
    protected $parameterEndpoint = null;

    // Rest results directory
    protected $restIngestDir = null;

    /* ------------------------------------------------------------------------------------------
     * Set up data endpoints and other options.
     *
     * @param IngestorOptions $options Options specific to this Ingestor
     * @param EtlConfiguration $etlConfig Parsed configuration options for this ETL
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(aOptions $options, EtlConfiguration $etlConfig, LoggerInterface $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see iAction::initialize()
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(EtlOverseerOptions $etlOverseerOptions = null)
    {
        if ( $this->isInitialized() ) {
            return;
        }

        $this->initialized = false;

        parent::initialize($etlOverseerOptions);

        $this->restIngestDir = $this->sourceEndpoint->getPath();

        if ( ! $this->utilityEndpoint instanceof Rest ) {
            $this->logAndThrowException(
                sprintf(
                    "Utility endpoint %s does not implement ETL\\DataEndpoint\\Rest",
                    get_class($this->utilityEndpoint)
                )
            );
        }

        $this->utilityEndpoint->connect();

        // If the source query is specified in the definition file use it to obtain parameters for the
        // rest call. For each record returned by the source query, add the returned columnms to the
        // parameter list and generate one rest call. THIS OVERRIDES THE NEXT/PREV KEYS IN THE RESPONSE!

        if ( null === $this->etlSourceQuery && isset($this->parsedDefinitionFile->source_query) ) {
            $this->logger->debug("Create ETL source query object");
            $this->parameterEndpoint = $this->utilityEndpoint->getRestParameterEndpoint();
            $this->parameterEndpoint->connect();
            $this->etlSourceQuery = new Query(
                $this->parsedDefinitionFile->source_query,
                $this->parameterEndpoint->getSystemQuoteChar(),
                $this->logger
            );

            // If supported by the source query, set the date ranges here.

            $this->getEtlOverseerOptions()->applyOverseerRestrictions($this->etlSourceQuery, $this->parameterEndpoint, $this);

        }  // if ( null === $this->etlSourceQuery && isset($this->parsedDefinitionFile->source_query) )

        // Set up some default values for the REST response config. These can be overriden.

        $defaultRestResponseConfig = (object) array(
            // Optional top-level entry point into the result. NSF api uses "response".
            "response" => null,
        );

        if ( null !== $this->restRequestConfig && ! is_object($this->restRequestConfig) ) {
            $this->logAndThrowException("REST request config must be an object");
        } elseif ( null !== $this->restResponseConfig && ! is_object($this->restResponseConfig) ) {
            $this->logAndThrowException("REST response config must be an object");
        }

        // Rest response
        if ( null === $this->restResponseConfig && isset($this->parsedDefinitionFile->rest_response) ) {
            $this->restResponseConfig = (object) array_merge(
                (array) $defaultRestResponseConfig,
                (array) $this->parsedDefinitionFile->rest_response
            );
        } elseif ( ! isset($this->parsedDefinitionFile->rest_response) ) {
            $this->logAndThrowException("rest_response key not found in definition file");
        }

        // Rest Request
        if ( null === $this->restRequestConfig && isset($this->parsedDefinitionFile->rest_request) ) {
            $this->restRequestConfig = $this->parsedDefinitionFile->rest_request;
        } elseif ( ! isset($this->parsedDefinitionFile->rest_response) ) {
            $this->logAndThrowException("rest_request key not found in definition file");
        }

        // --------------------------------------------------------------------------------
        // The values for the request parameter and result field map

        if ( isset($this->restRequestConfig->parameters) ) {
            foreach ( $this->restRequestConfig->parameters as $parameter => &$value ) {
                if ( ! is_object($value) ) {
                    continue;
                }
                if ( ! isset($value->value) ) {
                    $this->logger->warning("{$this} Parameter '$parameter' object does not specify a 'value' key, skipping");
                    continue;
                }
                $value = $value->value;
            }
            unset($value); // Sever the reference with the last element

        }  // if ( isset($this->restRequestConfig->parameters) )

        $this->initialized = true;

        return true;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * @see aAction::performPreExecuteTasks()
     * ------------------------------------------------------------------------------------------
     */

    protected function performPreExecuteTasks()
    {

        parent::performPreExecuteTasks();

        // If using a source query, execute it and prepare the result set

        if ( null !== $this->etlSourceQuery ) {

            $sql = $this->variableStore->substitute(
                $this->etlSourceQuery->getSql(),
                "Undefined macros found in SQL"
            );

            $this->logger->debug("REST source query:\n$sql");
            $handle = $this->parameterEndpoint->getHandle();
            $this->etlSourceQueryResult = $handle->query($sql, array(), true);

            if ( 0 == $this->etlSourceQueryResult->rowCount() ) {
                $this->logger->warning("{$this} Source query return 0 rows, exiting");
                return false;
            }
        }  // if ( null !== $this->etlSourceQuery ) {

        // Apply any parameters that are defined

        $this->processParameters();

        return true;

    }  // performPreExecuteTasks()

    private function executeRestCalls()
    {
        // Support a source query, mapping from the source to rest parameters, rest field map
        $requestHeaders = ( isset($this->restRequestConfig->requestHeaders) ? (array) $this->restRequestConfig->requestHeaders : [] );
        $responseKey = ( isset($this->restResponseConfig->reponse) ? $this->restResponseConfig->response : null );

        // --------------------------------------------------------------------------------
        // Perform a-priori verifications

        $timeStart = microtime(true);

        // If using a source query, set parameters for the current result.

        if ( null !== $this->etlSourceQuery && null !== $this->etlSourceQueryResult ) {
            $row = $this->etlSourceQueryResult->fetch(PDO::FETCH_ASSOC);
            foreach( $row as $k => $v ) {
                $this->setParameter($k, $v);
            }
        }

        // --------------------------------------------------------------------------------
        // Retrieve and process the REST results

        $this->setRestUrlWithParameters();

        // Keep the current url for logging
        $this->currentUrl = curl_getinfo($this->utilityHandle, CURLINFO_EFFECTIVE_URL);

        curl_setopt($this->utilityHandle, CURLOPT_HTTPHEADER, $requestHeaders);

        $this->logger->info("REST url: {$this->currentUrl}");

        if ( $this->getEtlOverseerOptions()->isDryrun() ) {
            return 0;
        }

        $numRequestsMade = 1;
        $logCount = 10000;
        $first = true;

        while ( false !== ( $retval = curl_exec($this->utilityHandle) ) ) {

            if ( 0 !== curl_errno($this->utilityHandle) ) {
                $this->logAndThrowException("${this} Error during REST call: " . curl_error($this->utilityHandle));
            }

            $response = json_decode($retval);

            if ( null === $response || ! is_object($response) ) {
                $this->logAndThrowException("{$this} Response is not an array: $retval");
            }

            // --------------------------------------------------------------------------------
            // Identify the various parts of the response based on the configuration and verify them

            // If a top level response key is provided, grab the data that it contains.

            $results = null;
            if ( $responseKey !== null ) {
                if ( ! isset($response->$responseKey) ) {
                    $this->logAndThrowException(
                        "Configured top-level response key '$responseKey' not found in response. "
                        . "Response keys are '" . implode(",", array_keys((array) $response)) . "'"
                    );
                } else {
                    $results = $response->$responseKey;
                }
            } else {
                $results = $response;
            }

            if ( empty($results) ) {
                $this->logger->notice("Request returned an empty result set, skipping. url = {$this->currentUrl}");

                if ( false === $this->setNextUrl($response, $nextKey) ) {
                    break;
                }
                continue;
            }  // else ( 0 == count($results) )

            if ( ($file = tempnam($this->restIngestDir, 'xdmod-rest-ingestor-')) === false ) {
                $this->logAndThrowException("Could not create JSON file, $file, for REST results");
            }

            chmod($file, 0644);

            $jsonFile = $file . '.json';
            rename($file, $jsonFile);

            if ( ($fp = fopen($jsonFile, 'w')) === false) {
                $this->logAndThrowException("Could not open $jsonFile");
            }

            if (fwrite($fp, json_encode($results)) === 0) {
                $this->logAndThrowException("Write failed to $jsonFile");
            }

            fclose($fp);

            // Set up the next url using the "next" key or the source query values

            if ( false === $this->setNextUrl($response, $nextKey) ) {
                break;
            }

            $numRequestsMade++;

        }  // while ( false !== ( $retval = curl_exec($this->utilityHandle) ) )

        if ( 0 != curl_errno($this->utilityHandle) ) {
            $this->logAndThrowException(curl_error($this->utilityHandle));
        }

        $this->logger->info("Made $numRequestsMade REST requests");

        return $numRecordsProcessed;
    }

    /* ------------------------------------------------------------------------------------------
     * @see aIngestor::_execute()
     * ------------------------------------------------------------------------------------------
     */

     // @codingStandardsIgnoreLine
    protected function _execute()
    {
        $this->executeRestCalls();
        parent::_execute();
        $this->sourceEndpoint->cleanUpDirectory();
    }  // _execute()

    /* ------------------------------------------------------------------------------------------
     * The REST ingestor supports request parameters specified in the definition file. Process these
     * parameters, including any macros and add them to the parameter list.
     *
     * @return The number of parameters processed
     *
     * @throw Exception If a parameter was not formatted correctly
     * ------------------------------------------------------------------------------------------
     */

    protected function processParameters()
    {
        $numParameters = 0;

        if ( null === $this->restRequestConfig ||
             ! isset($this->restRequestConfig->parameters) )
        {
            return $numParameters;
        }

        foreach( $this->restRequestConfig->parameters as $parameter => $value ) {

            $value = $this->variableStore->substitute($value);
            $this->setParameter($parameter, $value);
            $numParameters++;

        }  // foreach( $this->restRequestConfig->parameters as $parameter => $value )

        return $numParameters;

    }  // processParameters()

    /* ------------------------------------------------------------------------------------------
     * Set an individual rest parameter.
     *
     * @param $parameter The parameter name
     * @param $value The parameter value
     *
     * @return This object for method chaining.
     * ------------------------------------------------------------------------------------------
     */

    protected function setParameter($parameter, $value)
    {
        if ( null === $parameter || empty($parameter) ) {
            $this->logAndThrowException("REST parameter name not provided");
        }

        $this->restParameters[$parameter] = $value;
        return $this;

    }  // setParameter()

    /* ------------------------------------------------------------------------------------------
     * Format request parameters and add them to the base url.  The format can be a standard querys
     * tring format or a format can be specified in the configuration. Macro substitution is supported
     * in both the parameters and the format.  A special ${REMAINING} macro is supported using the
     * format string that will evaluate to a query string containing any macros that were not used in
     * the format.
     *
     * @return This object for method chaining.
     * ------------------------------------------------------------------------------------------
     */

    protected function setRestUrlWithParameters()
    {
        if ( 0 == count($this->restParameters) ) {
            return;
        }

        if ( null !== $this->restRequestConfig && isset($this->restRequestConfig->format) ) {

            // A format was specified. Substitute any existing parameters in the format string.

            $substitutionDetails = array();
            $vs = new VariableStore($this->restParameters);
            $queryString = $vs->substitute($this->restRequestConfig->format, null, $substitutionDetails);

            if ( false !== strpos($queryString, '${^REMAINING}') ) {
                $used = array_combine($substitutionDetails['substituted'], $substitutionDetails['substituted']);
                $remaining = array_diff_key($this->restParameters, $used);
                $parameters = implode(
                    "&",
                    array_map(
                        function ($v, $k) {
                            return $k . "=" . urlencode($v);
                        },
                        $remaining,
                        array_keys($remaining)
                    )
                );
                $vs->clear();
                $vs->add('^REMAINING', $parameters);
                $queryString = $vs->substitute($queryString);
            }
        } else {
            // Use standard query string format

            $parameters = array_map(
                function ($v, $k) {
                    return $k . "=" . urlencode($v);
                },
                $this->restParameters,
                array_keys($this->restParameters)
            );
            $queryString = "?" . implode("&", $parameters);
        }

        $this->currentUrl = $newUrl = $this->utilityEndpoint->getBaseUrl() . $queryString;
        curl_setopt($this->utilityHandle, CURLOPT_URL, $newUrl);

        return $this;

    }  // setRestUrlWithParameters()

    /* ------------------------------------------------------------------------------------------
     * Set up the url for the next record.
     *
     * @param $response The REST response, used to check for a "next" key that will tell us the url
     *   for the next set of results
     * @param $nextKey The name of the "next" key
     *
     * @return true on success, false if there are no more records to fetch
     * ------------------------------------------------------------------------------------------
     */

    protected function setNextUrl(\stdClass $response, $nextKey)
    {
        // If the next key was specified, use the value from the response for the next call. If we are
        // using a source query, do not use the next key returned in the response.

        if ( null !== $this->etlSourceQuery && null !== $this->etlSourceQueryResult ) {

            // Continue pulling from the source query until we reach the end or we pass parameter verification

            $row = false;

            while ( false !== ($row = $this->etlSourceQueryResult->fetch(PDO::FETCH_ASSOC)) ) {


                foreach( $row as $k => $v ) {
                    $this->setParameter($k, $v);
                }

                // Need to be able to skip this not end the run.

                if ( false !== $this->setRestUrlWithParameters() ) {
                    break;
                }
            }
            if ( false === $row ) {
                return false;
            }

        } elseif ( null !== $nextKey ) {
            if ( ! isset($response->$nextKey) || null === $response->$nextKey ) {
                $this->logger->warning("Next property '$nextKey' not present or has null value in response, finished.");
                return false;
            } else {
                $this->currentUrl = $response->$nextKey;
                curl_setopt($this->utilityHandle, CURLOPT_URL, $response->$nextKey);
            }
        } else {
            // No next key and no source query
            return false;
        }

        $this->logger->debug("REST url: {$this->currentUrl}");

        if ( null !== $this->utilityEndpoint->getSleepMicroseconds() ) {
            usleep($this->utilityEndpoint->getSleepMicroseconds());
        }

        return true;

    }  // setNextUrl()
}  // class RestIngestor
