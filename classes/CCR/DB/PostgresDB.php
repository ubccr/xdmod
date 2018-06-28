<?php
/*
 * @author Amin Ghadersohi
 * @date 2010-Jul-07
 *
 * The top interface for postgres dbs using pdo driver
 *
 * Changelog
 *
 * 2015-12-15 Steve Gallo <smgallo@buffalo.edu>
 * - Now implements iDatabase
 *
 */

namespace CCR\DB;

use Exception;

class PostgresDB extends PDODB implements iDatabase
{
    // ------------------------------------------------------------------------------------------
    // @see iDatabase::__construct()
    // ------------------------------------------------------------------------------------------

    public function __construct($db_host, $db_port, $db_name, $db_username, $db_password, $dsn_extra = null)
    {
        if ( null == $db_host || null === $db_name || null === $db_username ) {
            $msg = "Database engine " . __CLASS__ . " requires (host, database, username)";
            throw new Exception($msg);
        }

        parent::__construct("pgsql", $db_host, $db_port, $db_name, $db_username, $db_password, "options='--application_name=XDMoD'");
    }
}  // class PostgresDB
