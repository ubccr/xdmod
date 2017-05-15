<?php
/* ==========================================================================================
 * Implementation of the Mysql DataEndpoint
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @data 2015-10-05
 * ==========================================================================================
 */

namespace ETL\DataEndpoint;

use ETL\DataEndpoint\DataEndpointOptions;
use \Log;

class Mysql extends aRdbmsEndpoint implements iRdbmsEndpoint
{

    /* ------------------------------------------------------------------------------------------
     * @see iDataEndpoint::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(DataEndpointOptions $options, Log $logger = null)
    {
        parent::__construct($options, $logger);
        $this->systemQuoteChar = '`';
    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * We consider 2 MySQL servers to be the same if the host and port are equal.  Query both the
     * current and comparison endpoints and compare.
     *
     * @see iDataEndpoint::isSameServer()
     * ------------------------------------------------------------------------------------------
     */

    public function isSameServer(iDataEndpoint $cmp)
    {
        $comparisonValues = array("hostname", "port");

        // Interrogate the source and destination connections and compare host/port information

        $criteria = array();
        foreach ( $comparisonValues as $value ) {
            $criteria[] = "Variable_name = '$value'";
        }

        $sql = "SHOW VARIABLES WHERE " . implode(" or ", $criteria);
        $sourceResult = $this->getHandle()->query($sql);
        $destinationResult = $cmp->getHandle()->query($sql);

        $sourceInfo = array();
        foreach ( $sourceResult as $row ) {
            $sourceInfo[ $row['Variable_name'] ] = $row['Value'];
        }

        $destinationInfo = array();
        foreach ( $destinationResult as $row ) {
            $destinationInfo[ $row['Variable_name'] ] = $row['Value'];
        }

        $match = true;
        foreach ( $comparisonValues as $value ) {
            if ( $sourceInfo[$value] != $destinationInfo[$value] ) {
                $match = false;
            }
        }

        return $match;

    }  // isSameServer()

    /* ------------------------------------------------------------------------------------------
     * @see iRdbmsEndpoint::schemaExists()
     * ------------------------------------------------------------------------------------------
     */

    public function schemaExists($schemaName = null)
    {
        $sql = "SELECT
schema_name as name, catalog_name as catalog
FROM information_schema.schemata
WHERE schema_name = :schema";

        return $this->executeSchemaExistsQuery($sql, $schemaName);

    }  // schemaExists()

    /* ------------------------------------------------------------------------------------------
     * @see iRdbmsEndpoint::createSchema()
     * ------------------------------------------------------------------------------------------
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
        $sql = "CREATE DATABASE IF NOT EXISTS $schemaName";

        try {
            $dbh = $this->getHandle();
            $result = $dbh->execute($sql);
        } catch (\PdoException $e) {
            $this->logAndThrowException(
                "Error creating schema '$schemaName'",
                array('exception' => $e, 'sql' => $sql, 'endpoint' => $this)
            );
        }

        return true;

    }  // createSchema()
}  // class Mysql
