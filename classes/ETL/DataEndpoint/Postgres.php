<?php
/* ==========================================================================================
 * Implementation of the Postgress DataEndpoint.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @data 2015-11-11
 * ==========================================================================================
 */

namespace ETL\DataEndpoint;

use ETL\DataEndpoint\DataEndpointOptions;
use \Log;

class Postgres extends aRdbmsEndpoint
implements iRdbmsEndpoint
{

    public function __construct(DataEndpointOptions $options, Log $logger = null)
    {
        parent::__construct($options, $logger);
        $this->systemQuoteChar = '"';
    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * We consider 2 Postgres servers to be the same if the host and port are equal.  Query both the
     * current and comparison endpoints and compare.
     *
     * @see iDataEndpoint::isSameServer()
     * ------------------------------------------------------------------------------------------
     */

    public function isSameServer(iDataEndpoint $cmp)
    {
        return false;
    }  // isSameServer()

    /* ------------------------------------------------------------------------------------------
     * @see iRdbmsEndpoint::schemaExists()
     * ------------------------------------------------------------------------------------------
     */

    public function schemaExists($schemaName)
    {
        if ( empty($schemaName) ) {
            $msg = "Schema name cannot be empty";
            $this->logAndThrowException($msg);
        }

        // See http://www.postgresql.org/docs/current/static/catalogs.html

        $sql = "SELECT
nspname as name
FROM pg_catalog.pg_namespace
WHERE nspname = :schema";

        $params = array(":schema" => $this->getSchema());

        try {
            $dbh = $this->getHandle();
            $result = $dbh->query($sql, $params);
            if ( 0 == count($result) ) {
                return false;
            }
        } catch (\PdoException $e) {
            $msg = "Error querying for schema '" . $this->getSchema() . "'";
            $this->logAndThrowSqlException($sql, $e, $msg);
        }

        return true;

    }  // schemaExists()

    /* ------------------------------------------------------------------------------------------
     * @see iRdbmsEndpoint::createSchema()
     * ------------------------------------------------------------------------------------------
     */

    public function createSchema($schemaName)
    {
        if ( empty($schemaName) ) {
            $msg = "Schema name cannot be empty";
            $this->logAndThrowException($msg);
        }

        // Don't use bind parameters because we don't want to quote the schema
        $sql = "CREATE SCHEMA IF NOT EXISTS " . $this->getSchema(true);

        $params = array(":schema" => $this->getSchema());

        try {
            $dbh = $this->getHandle();
            $result = $dbh->query($sql, $params);
        } catch (\PdoException $e) {
            $msg = "Error creating schema '" . $this->getSchema() . "'";
            $this->logAndThrowSqlException($sql, $e, $msg);
        }

        return true;

    }  // createSchema()

}  // class Postgres
