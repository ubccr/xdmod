<?php
// --------------------------------------------------------------------------------
// @author Steve Gallo
// @date 2011-Jan-07
//
// Singleton database abstraction layer to provide machinery to query the
// configuration file or use parameters to obtain database engine and connection
// information.
//
// Changes:
//
// 2015-12-15 Steve Gallo <smgallo@buffalo.edu>
// - Enforce that the database engine implements iDatabase. We may want to have a basic iDatabase
//     which is extended my iRdbmsDatabase, iMongoDatabase, etc.
// - Check that the engine class exists and throw an exception if it does not
// - Removed dead/useless code, made indentation consistent
// --------------------------------------------------------------------------------

namespace CCR;

use xd_utilities;
use Exception;
use CCR\DB\iDatabase;

class DB
{
    // An array (pool) of database connection handles, one per configuration file section

    private static $instancePool = array();

    // Ensure that this class is a singleton

    private function __construct() {}

    // ================================================================================
    // Cleanup
    // ================================================================================

    public function __destruct() {}

    // ================================================================================
    // Create an instance of the database singleton.  A single argument is
    // required, which is configuration file section identifier (e.g. [datawarehouse]).
    // The database connection parameters in that section will be used to create the
    // instance, which will be cached for re-use by subsequent requests targeting the
    // same section.
    //
    // @param $sectionName Name of the configuration section containing database parameters
    // @param $autoConnect If TRUE, connect to the database after creating the object
    //
    // @throws Exception if there is an invalid number of arguments
    //
    // @returns An instance of the database class
    // ================================================================================

    public static function factory($sectionName, $autoConnect = true)
    {
        // If this section has been used before in creating a database instance (handle), then
        // it will have been cached.  In this case, the cached handle will be returned.

        if ( array_key_exists($sectionName, self::$instancePool) ) {
            return self::$instancePool[$sectionName];
        }

        try {
            $iniSection = xd_utilities\getConfigurationSection($sectionName, 'db_engine');
        } catch (Exception $e) {
            $msg = "Unable to get database configuration options: " . $e->getMessage();
            throw new Exception ($msg);
        }

        // Not all engines are required to specify all configuration options (e.g., Oracle) so
        // allow NULL (empty) options. Specific engines may enforce additional requirements.

        $engine   = ( array_key_exists('db_engine', $iniSection) ? $iniSection['db_engine'] : null);
        $database = ( array_key_exists('database', $iniSection) ? $iniSection['database'] : null);
        $user     = ( array_key_exists('user', $iniSection) ? $iniSection['user'] : null );

        if ( null === $engine || null === $database || null === $user ) {
            $msg = "Configuration section '$sectionName' missing required options (db_engine, database, user)";
            throw new Exception($msg);
        }

        $password = ( array_key_exists('pass', $iniSection) ? $iniSection['pass'] : null );
        $host     = ( array_key_exists('host', $iniSection) ? $iniSection['host'] : null );
        $port     = ( array_key_exists('port', $iniSection) ? $iniSection['port'] : null );

        $engine = "CCR\\DB\\$engine";

        // Ensure that the class exists before we attempt to instantiate it

        if ( class_exists($engine) ) {
            $db = new $engine($host, $port, $database, $user, $password);
        } else {
            $msg = "Error creating database defined in section '$sectionName', class '$engine' not found";
            throw new Exception($msg);
        }

        // All database interfaces must implement iDatabase

        if ( ! $db instanceof iDatabase ) {
            throw new Exception("$engine does not implenment interface iDatabase");
        }

        self::$instancePool[$sectionName] = $db;
        if ($autoConnect) self::$instancePool[$sectionName]->connect();

        return self::$instancePool[$sectionName];

    }  // factory()

}  // class DB
