<?php
/**
 * Required interface for implementing a DataEndpoint for the ETL process. Data endpoints are a
 * specification of a data source or destination and can be a database, file, stream, url, or any
 * other source.  Endpoints must have at minimum a type and a name but may have other attributes as
 * well such as a schema for a database or a mode for a file.  In addition to metadata, endpoints
 * provide a handle to the underlying implementation (e.g., a PDO or file handle)
 *
 * Data Endpoint configurations tend to be immutable once they are created, providing only
 * accessors to properties and not setters.
 *
 * @see aDataEndpoint
 */

namespace ETL\DataEndpoint;

// PEAR logger
use Log;

interface iDataEndpoint
{
    /**
     * Create the DataEndpoint object
     *
     * @param DataEndpointOptions $options A DataEndpointOptions object containing option
     *    information
     * @param Log $logger Optional PEAR Log object for system logging
     */

    public function __construct(DataEndpointOptions $options, Log $logger = null);

    /**
     * @return The endpoint type (e.g., mysql, postgres, file)
     */

    public function getType();

    /**
     * @return The endpoint name.
     */

    public function getName();

    /**
     * @return A unique key that can be used to identify this endpoint. Typically some combination of
     *   type and name.
     */

    public function getKey();

    /**
     * @return A handle to the underlying data access mechanism (file handle, database handle, etc.)
     */

    public function getHandle();

    /**
     * Compare this endpoint with the specified endpoint and return TRUE if they are considered to be
     * on the same server.  This may allow certain optimizations to be performed.
     *
     * @param $cmp The endpoint object to compare. Must also implement iDataEndpoint.
     *
     * @return TRUE If the objects are considered to be on the same server
     */

    public function isSameServer(iDataEndpoint $cmp);

    /**
     * Connect to the endpoint.
     *
     * @return The handle to the underlying endpoint (e.g., file handle, database handle, etc.)
     */

    public function connect();

    /**
     * Disconnect from the endpoint and set the handle to NULL.
     *
     * @return TRUE on successful disconnection
     */

    public function disconnect();

    /**
     * Places quotes around the input string (if required) and escape special characters within the
     * input string, using a quoting style appropriate to the underlying endpoint.
     *
     * @param $str The string to quote
     *
     * @return TRUE on successful disconnection
     */

    public function quote($str);

    /**
     * Verify that the endpoint exists. For a database endpoint this may mean connecting and verifying
     * that the schema exists while a file may verify that the file exists and is readable and/or
     * writable.
     *
     * @param $$dryrun TRUE if we are in DRYRUN mode and no changes should be made
     * @param $leaveConnected TRUE if the endpoint should remain connected after verification
     *
     * @return TRUE if verification was successful
     *
     * @throw PdoException If we could not connect to the database
     * @throw Exception Iif the schema does not exist
     */

    public function verify($dryrun = false, $leaveConnected = false);

    /**
     * Generate a string representation of the endpoint. Typically the name, plus other pertinant
     * information as appropriate.
     *
     * @return A string representation of the endpoint
     */

    public function __toString();
}
