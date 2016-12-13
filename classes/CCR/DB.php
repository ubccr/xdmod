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
use \Exception;
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
  // @throws Exception if there is an invalid number of arguments
  //
  // @returns An instance of the database class
  // ================================================================================
  
  public static function factory($section, $autoConnect = true)
  {   
    // If this section has been used before in creating a database instance (handle), then
    // it will have been cached.  In this case, the cached handle will be returned.
    
    if ( array_key_exists($section, self::$instancePool) ) {
		return self::$instancePool[$section];
    }
    
    $engine =   xd_utilities\getConfiguration($section, 'db_engine');
    $host =     xd_utilities\getConfiguration($section, 'host');
    $database = xd_utilities\getConfiguration($section, 'database');
    $user =     xd_utilities\getConfiguration($section, 'user');
    $passwd =   xd_utilities\getConfiguration($section, 'pass');
    $port =     xd_utilities\getConfiguration($section, 'port');
    
    $engine = "CCR\\DB\\$engine";
    
    // Ensure that the class exists before we attempt to instantiate it

    if ( class_exists($engine) ) {
      $db = new $engine($host, $port, $database, $user, $passwd);
    } else {
      $msg = "Error creating database '" . $options->name . "', class '$className' not found";
      throw new Exception($msg);
    }

    // All database interfaces must implement iDatabase

    if ( ! $db instanceof iDatabase ) {
      throw new Exception("$engine does not implenment interface iDatabase");
    }

    self::$instancePool[$section] = $db;
    if ($autoConnect) self::$instancePool[$section]->connect();
    
    return self::$instancePool[$section];
    
  }  // factory()

}  // class DB
