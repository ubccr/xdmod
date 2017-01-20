<?php
/* ==========================================================================================
 * Implementation of the Oracle DataEndpoint.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @data 2017-01-13
 * ==========================================================================================
 */

namespace ETL\DataEndpoint;

use ETL\DataEndpoint\DataEndpointOptions;
use \Log;

class Oracle extends aRdbmsEndpoint implements iRdbmsEndpoint
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

    public function schemaExists($schemaName = null)
    {
        if ( null === $schemaName ) {
            $schemaName = $this->getSchema();
        } elseif ( empty($schemaName) ) {
            $msg = "Schema name cannot be empty";
            $this->logAndThrowException($msg);
        }

        // See http://www.postgresql.org/docs/current/static/catalogs.html

        $sql = "SELECT
username AS name
FROM all_users
WHERE username = UPPER(:schema)";

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

        // Creating Oracle schemas require creating a user including specifying an
        // authentication method. Since we do not support writing to Oracle at the moment,
        // we don't need to support schema creation.

        return false;

    }  // createSchema()

    /* ------------------------------------------------------------------------------------------
     * @see iRdbmsEndpoint::tableExists()
     * ------------------------------------------------------------------------------------------
     */

    public function tableExists($tableName, $schemaName = null)
    {
        if ( empty($tableName) ) {
            $msg = "Table name cannot be empty";
            $this->logAndThrowException($msg);
        }

        $sql = "SELECT
table_name as NAME
FROM all_tables
WHERE owner = UPPER(:schema)
AND table_name = UPPER(:tablename)";

        if ( null === $schemaName ) {
            $schemaName = $this->getSchema();
        }

        $params = array(
            ":schema" => $schemaName,
            ":tablename"  => $tableName
            );

        try {
            $dbh = $this->getHandle();
            $result = $dbh->query($sql, $params);
            if ( 0 == count($result) ) {
                return false;
            }
        } catch (PDOException $e) {
            $msg = "Error querying for table '$schema'.'$tableName':";
            $this->logAndThrowSqlException($sql, $e, $msg);
        }

        return true;

    }  // tableExists()

    /* ------------------------------------------------------------------------------------------
     * @see iRdbmsEndpoint::getTableColumnNames()
     *
     * Obtaining the columns for a table is straightforward, however views are another
     * matter. ALL_VIEWS contains the view definition but there is no data dictionary view
     * for views akin to all_tab_cols. For this we need to use "describe <view>".
     * ------------------------------------------------------------------------------------------
     */

    public function getTableColumnNames($tableName, $schemaName = null)
    {
        if ( empty($tableName) ) {
            $msg = "Table name cannot be empty";
            $this->logAndThrowException($msg);
        }

        $sql = "SELECT
column_name AS NAME, data_type AS type
FROM all_tab_cols
WHERE owner = UPPER(:schema)
AND table_name = UPPER(:tablename)
ORDER BY column_id ASC";

        $params = array(":schema" => $this->getSchema(),
                        ":tablename"  => $tableName);

        try {
            $dbh = $this->getHandle();
            $result = $dbh->query($sql, $params);
            if ( 0 == count($result) ) {
                $msg = "No columns returned";
                $this->logAndThrowException($msg);
            }
        } catch (PDOException $e) {
            $msg = "Error retrieving column names from '" . $this->getSchema() . ".'$tableName' ";
            $this->logAndThrowSqlException($sql, $e, $msg);
        }

        $columnNames = array();

        foreach ( $result as $row ) {
            $columnNames[] = $row['NAME'];
        }

        return $columnNames;

    }  // getTableColumnNames()
}  // class Postgres
