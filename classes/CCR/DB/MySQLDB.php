<?php
/* 
* @author Amin Ghadersohi
* @date 2010-Jul-07
*
* The top interface for mysql dbs using pdo driver
* 
* Changelog
*
* 2015-12-15 Steve Gallo <smgallo@buffalo.edu>
* - Now implements iDatabase
*/

namespace CCR\DB;

class MySQLDB
extends PDODB
implements iDatabase
{
	function __construct($db_host,$db_port,$db_name,$db_username,$db_password)
	{
		$dsn = 'mysql:host=' . $db_host . ';port=' . $db_port . ';dbname=' . $db_name . ';charset=utf8';
		parent::__construct("mysql",$db_host,$db_port,$db_name,$db_username,$db_password, $dsn);
	}
	function __destruct()
	{
		parent::__destruct();
    }

}
