<?php
/**
 * Web Server Log file endpoint. This endpoint contains the machinery
 * to parse web server log files and user agent strings. It also includes
 * the optional ability to use a GeoIP database file to associate the
 * location with an ip address.
 *
 * Configuration properties:
 * - log_format: A format string that specifies the expected log file format. The
 *               syntax is identical to the format in the apache configuration.
 * - geoip_file: Optional path to a GeoLite2 database file in MMDB format. If absent
 *               then the IP to location mapping will not be performed.
 *
 * The fields output by this endpoint will be dependent on the log_format string.
 */

namespace ETL\DataEndpoint;

use Psr\Log\LoggerInterface;
use ETL\DataEndpoint\DataEndpointOptions;

class WebServerLogFile extends aStructuredFile implements iStructuredFile
{
    const CACHE_SIZE = 1000;

    private $web_parser = null;

    private $ua_parser = null;
    private $ua_parser_cache = array();

    private $geoip_lookup = null;
    private $geoip_cache = array();

    /**
     * @const string Defines the name for this endpoint that should be used in configuration files.
     * It also allows us to implement auto-discovery.
     */

    const ENDPOINT_NAME = 'webserverlog';

    /**
     * @see iDataEndpoint::__construct()
     */

    public function __construct(DataEndpointOptions $options, LoggerInterface $logger = null)
    {
        parent::__construct($options, $logger);

        $this->web_parser = new \Kassner\LogParser\LogParser();
        if (isset($options->log_format)) {
            $this->web_parser->setFormat($options->log_format);
        }

        $this->ua_parser = \UAParser\Parser::create();

        if (isset($options->geoip_file)) {
            $this->geoip_lookup = new \GeoIp2\Database\Reader($options->geoip_file);
        }
    }

    private function lookupGeoIp($host) {

        if (array_key_exists($host, $this->geoip_cache)) {
            return $this->geoip_cache[$host];
        }

        $result = new \stdClass();
        $result->{"city"} = 'NA';
        $result->{"subdivision"} = 'NA';
        $result->{"country"} = 'NA';

        if ($this->geoip_lookup !== null) {
            try {
                $geoip = $this->geoip_lookup->city($host);
                $result->{"city"} = $geoip->city->name;
                $result->{"subdivision"} = $geoip->mostSpecificSubdivision->isoCode;
                $result->{"country"} = $geoip->country->isoCode;
            }
            catch (\GeoIp2\Exception\AddressNotFoundException $e) {
                $result->{"city"} = 'unknown';
                $result->{"subdivision"} = 'unknown';
                $result->{"country"} = 'unknown';
            }
            catch (\InvalidArgumentException $e) {
                // leave at the default value of 'N/A'
            }

            if (count($this->geoip_cache) > self::CACHE_SIZE) {
                array_shift($this->geoip_cache);
            }
            $this->geoip_cache[$host] = $result;
        }

        return $result;
    }

    /**
     * @see aStructuredFile::decodeRecord()
     */

    protected function decodeRecord($data)
    {
        try {
            $decoded = $this->web_parser->parse($data);

            if (property_exists($decoded, 'HeaderUserAgent')) {
                if (array_key_exists($decoded->HeaderUserAgent, $this->ua_parser_cache)) {
                    $ua_decoded = $this->ua_parser_cache[$decoded->HeaderUserAgent];
                } else {
                    if (count($this->ua_parser_cache) > self::CACHE_SIZE) {
                        array_shift($this->ua_parser_cache);
                    }
                    $ua_decoded = $this->ua_parser->parse($decoded->HeaderUserAgent);
                    $this->ua_parser_cache[$decoded->HeaderUserAgent] = $ua_decoded;
                }

                $decoded->{"ua_family"} = $ua_decoded->ua->family;
                $decoded->{"ua_major"} = $ua_decoded->ua->major;
                $decoded->{"ua_minor"} = $ua_decoded->ua->minor;
                $decoded->{"ua_patch"} = $ua_decoded->ua->patch;

                $decoded->{"ua_os_family"} = $ua_decoded->os->family;
                $decoded->{"ua_os_major"} = $ua_decoded->os->major;
                $decoded->{"ua_os_minor"} = $ua_decoded->os->minor;
                $decoded->{"ua_os_patch"} = $ua_decoded->os->patch;

                $decoded->{"ua_device_family"} = $ua_decoded->device->family;
                $decoded->{"ua_device_brand"} = $ua_decoded->device->brand;
                $decoded->{"ua_device_model"} = $ua_decoded->device->model;
            }

            if (property_exists($decoded, 'host')) {
                $location = $this->lookupGeoIp($decoded->host);
                $decoded->{"geo_city_name"} = $location->city;
                $decoded->{"geo_subdivision"} = $location->subdivision;
                $decoded->{"geo_country"} = $location->country;
            }

            $this->recordList[] = $decoded;

        } catch (\Kassner\LogParser\FormatException $e) {
            // ignore failed lines
            $this->logger->debug("Skip " . $data);
        }

        return true;
    }

    /**
     * @see aStructuredFile::verifyData()
     */

    protected function verifyData()
    {
        return true;
    }

    /**
     * @see aStructuredFile::discoverRecordFieldNames()
     */

    protected function discoverRecordFieldNames()
    {
        // If there are no records in the file then we don't need to set the discovered
        // field names.

        if ( 0 == count($this->recordList) ) {
            return;
        }

        // Determine the record names based on the structure of the JSON that we are
        // parsing.

        reset($this->recordList);
        $record = current($this->recordList);

        if ( is_array($record) ) {

            if ( $this->hasHeaderRecord ) {

                // If we have a header record skip the first record and use its values as
                // the field names

                $this->discoveredRecordFieldNames = array_shift($this->recordList);

            } elseif ( 0 !== count($this->requestedRecordFieldNames) ) {

                // If there is no header record and the requested field names have been
                // provided, use them as the discovered field names.  If a subsequent
                // record contains fewer fields return NULL values for those fields, if a
                // subsequent record contains more fields ignore them.

                $this->discoveredRecordFieldNames = $this->requestedRecordFieldNames;

            } else {
                $this->logAndThrowException("Record field names must be specified for JSON array records");
            }

        } elseif ( is_object($record) ) {

            // Pull the record field names from the object keys

            $this->discoveredRecordFieldNames = array_keys(get_object_vars($record));

        } else {
            $this->logAndThrowException(
                sprintf("Unsupported record type in %s. Got %s, expected array or object", $this->path, gettype($record))
            );
        }

        // If no field names were requested, return all discovered fields

        if ( 0 == count($this->requestedRecordFieldNames) ) {
            $this->requestedRecordFieldNames = $this->discoveredRecordFieldNames;
        }
    }
}
