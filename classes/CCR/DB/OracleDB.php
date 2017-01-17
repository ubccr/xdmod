<?php
/* 
* @author Amin Ghadersohi
* @date 2013-Jul-08
*
* The top interface for oracle dbs using pdo driver
* 
*/
namespace CCR\DB;

class OracleDB extends PDODB
{
    function __construct($db_host, $db_port, $db_name, $db_username, $db_password)
    {
        parent::__construct("oci", $db_host, $db_port, $db_name, $db_username, $db_password, "oci:dbname=$db_name");
    }
    function __destruct()
    {
        parent::__destruct();
    }
}
