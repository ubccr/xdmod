<?php
/* 
* @author Ryan Gentner
* @date 2011-Jul-07
*
* The top interface for sybase dbs using pdo driver
* 
*/
namespace CCR\DB;

use PDO;
use PDOException;

class SybaseDB extends PDODB
{

    function __construct($db_host, $db_port, $db_name, $db_username, $db_password)
    {
        parent::__construct("dblib", $db_host, $db_port, $db_name, $db_username, $db_password);
    }
   
   // ----------------------------------------------
    
    function __destruct()
    {
        parent::__destruct();
    }

   // ----------------------------------------------
       
    public function connect()
    {
  
        if (null === $this->_dbh) {
            try {
                $dsn = $this->_db_engine . ':host=' . $this->_db_host . ':' . $this->_db_port. ';dbname=' . $this->_db_name;
              
                $this->_dbh = new PDO($dsn, $this->_db_username, $this->_db_password);
                $this->_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $err) {
                throw $err;
            }
        }
  
        return $this->_dbh;
    }// connect()
}//SybaseDB
