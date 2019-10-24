<?php
/**
 * Implementation of the Postgress DataEndpoint.
 */

namespace ETL\DataEndpoint;

use ETL\DataEndpoint\DataEndpointOptions;
use Log;

class Postgres extends aRdbmsEndpoint implements iRdbmsEndpoint
{
    /**
     * @const string Defines the name for this endpoint that should be used in configuration files.
     * It also allows us to implement auto-discovery.
     */

    const ENDPOINT_NAME = 'postgres';

    /**
     * @see iDataEndpoint::__construct()
     */

    public function __construct(DataEndpointOptions $options, Log $logger = null)
    {
        parent::__construct($options, $logger);
        $this->systemQuoteChar = '"';
    }

    /**
     * We consider 2 Postgres servers to be the same if the host and port are equal.  Query both the
     * current and comparison endpoints and compare.
     *
     * @see iDataEndpoint::isSameServer()
     */

    public function isSameServer(iDataEndpoint $cmp)
    {
        return false;
    }

    /**
     * @see iRdbmsEndpoint::schemaExists()
     */

    public function schemaExists($schemaName = null)
    {
        // See http://www.postgresql.org/docs/current/static/catalogs.html

        $sql = "SELECT
nspname as name
FROM pg_catalog.pg_namespace
WHERE nspname = :schema";

        return $this->executeSchemaExistsQuery($sql, $schemaName);

    }

    /**
     * @see iRdbmsEndpoint::createSchema()
     */

    public function createSchema($schemaName = null)
    {
        if ( null === $schemaName ) {
            $schemaName = $this->getSchema(true);
        } elseif ( empty($schemaName) ) {
            $msg = "Schema name cannot be empty";
            $this->logAndThrowException($msg);
        } else {
            $schemaName = ( 0 !== strpos($schemaName, $this->systemQuoteChar)
                            ? $this->quoteSystemIdentifier($schemaName)
                            : $schemaName );
        }

        // Don't use bind parameters because we don't want to quote the schema
        $sql = "CREATE SCHEMA IF NOT EXISTS $schemaName";

        $params = array(":schema" => $this->getSchema());

        try {
            $dbh = $this->getHandle();
            $result = $dbh->query($sql, $params);
        } catch (\PdoException $e) {
            $this->logAndThrowException(
                "Error creating schema '$schemaName'",
                array('exception' => $e, 'sql' => $sql, 'endpoint' => $this)
            );
        }

        return true;

    }
}
