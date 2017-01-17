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
        foreach ($comparisonValues as $value) {
            $criteria[] = "Variable_name = '$value'";
        }

        $sql = "SHOW VARIABLES WHERE " . implode(" or ", $criteria);
        $sourceResult = $this->getHandle()->query($sql);
        $destinationResult = $cmp->getHandle()->query($sql);

        $sourceInfo = array();
        foreach ($sourceResult as $row) {
            $sourceInfo[ $row['Variable_name'] ] = $row['Value'];
        }

        $destinationInfo = array();
        foreach ($destinationResult as $row) {
            $destinationInfo[ $row['Variable_name'] ] = $row['Value'];
        }

        $match = true;
        foreach ($comparisonValues as $value) {
            if ($sourceInfo[$value] != $destinationInfo[$value]) {
                $match = false;
            }
        }

        return $match;
    }  // isSameServer()

    /* ------------------------------------------------------------------------------------------
     * @see iRdbmsEndpoint::schemaExists()
     * ------------------------------------------------------------------------------------------
     */

    public function schemaExists($schemaName)
    {
        if (empty($schemaName)) {
            $msg = "Schema name cannot be empty";
            $this->logAndThrowException($msg);
        }

        $sql = "SELECT
schema_name as name, catalog_name as catalog
FROM information_schema.schemata
WHERE schema_name = :schema";

        $params = array(":schema" => $this->getSchema());

        try {
            $dbh = $this->getHandle();
            $result = $dbh->query($sql, $params);
            if (0 == count($result)) {
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
        if (empty($schemaName)) {
            $msg = "Schema name cannot be empty";
            $this->logAndThrowException($msg);
        }

        // Don't use bind parameters because we don't want to quote the schema
        $sql = "CREATE DATABASE IF NOT EXISTS " . $this->getSchema(true);

        // $params = array(":schema" => $this->getSchema());

        try {
            $dbh = $this->getHandle();
            $result = $dbh->execute($sql);
        } catch (\PdoException $e) {
            $msg = "Error creating schema '" . $this->getSchema() . "'";
            $this->logAndThrowSqlException($sql, $e, $msg);
        }

        return true;
    }  // createSchema()
}  // class Mysql
