<?php
/* ==========================================================================================
 * XDMoD driver for accessing the Oracle database. PHP built-in support for Oracle (OCI8)
 * must be built against Oracle client libraries. The PDO_OCI PECL module is deprecated
 * and internal PDO_OCI support must be compiled from source (and is also out of date) so
 * we are using the PDOOCI wrapper from https://github.com/taq/pdooci.
 *
 * Support for both Easy Connect Naming and Local Naming (via tnsnames.ora) is
 * supported. Note that in order to use Local Naming, the TNS_ADMIN environment variable
 * must be set (this is used by the OCI driver).
 *
 * @author Steve Gallo
 * @date 2017-01-17
 * ==========================================================================================
 */

namespace CCR\DB;

use Exception;

class OracleDB extends PDODB
{
    /* ------------------------------------------------------------------------------------------
     * Set up the machinery. Oracle requires at minimum a database name (local naming,
     * this resolves to an entry in tnsnames.org) or a name, host, and optionally a port
     * (easy connect naming).
     *
     * Note: We do not support the $dsn_override that PDODB supports.
     *
     * @see PDODB::__construct()
     *
     * @throw Exception If minimum parameters were not provided.
     *
     * @see iDatabase::__construct()
     * ------------------------------------------------------------------------------------------
     */

    protected string $_dsn;

    public function __construct($db_host, $db_port, $db_name, $db_username, $db_password, $dsn_extra = null)
    {
        // At a minimum we must have either the (db_name) or the (db_host, db_name)

        if ( null === $db_name ) {
            $msg = __CLASS__
                . ' requires at a minimum (db_name) for Local Naming '
                . ' or (db_host, db_name[, db_port]) for Easy Connect Naming';
            throw new Exception($msg);
        }

        parent::__construct(
            "oci",
            $db_host,
            $db_port,
            $db_name,
            $db_username,
            $db_password
        );

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Clean up after ourselves and close the connection. Unlike standard PDO setting the
     * connection to NULL OCI has an oci_close() function.
     * ------------------------------------------------------------------------------------------
     */

    public function __destruct()
    {
        if ( null !== $this->_dbh) {
            $this->_dbh->close();
        }
    }  // __destruct()

    /* ------------------------------------------------------------------------------------------
     * Connect to the server.
     *
     * @return The database connection handle
     *
     * @throw Exception If there was a connection error
     * ------------------------------------------------------------------------------------------
     */

    public function connect()
    {
        if ( null !== $this->_dbh ) {
            return $this->_dbh;
        }

        $namingMethod = null;

        // If the host is not set then assume we are using Easy Connect
        // https://docs.oracle.com/database/121/NETAG/naming.htm#NETAG008
        // For example oci:dbname=//localhost:1521/mydb
        //
        // Otherwise assume Local Naming (via tnsnames.ora)
        // https://docs.oracle.com/database/121/NETAG/naming.htm#NETAG081
        // For example oci:dbname=mydb

        if ( null === $this->_db_host ) {
            $this->_dsn = 'oci:dbname=' . $this->_db_name;
            $namingMethod = "Local";
        } else {
            $this->_dsn = 'oci:dbname=//'
                . $this->_db_host
                . ( null !== $this->_db_port ? ':' . $this->_db_port : '' )
                . '/' . $this->_db_name;
            $namingMethod = "Easy Connect";
        }


        try {
            $this->_dbh = @new \PDOOCI\PDO($this->_dsn, $this->_db_username, $this->_db_password);
            $this->_dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        } catch (\PDOException $e) {
            $msg = __CLASS__
                . " Error connecting to database '" . $this->_dsn . "' using $namingMethod Naming. "
                . $e->getMessage();
            throw new Exception($msg);
        }

        return $this->_dbh;

    }  // connect()
}  // class OracleDB
