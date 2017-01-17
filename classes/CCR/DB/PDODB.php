<?php
/* 
 * @author Amin Ghadersohi
 * @date 2010-Jul-07
 *
 * The top interface for dbs using pdo driver
 *
 * Changelog
 *
 * 2015-12-15 Steve Gallo <smgallo@buffalo.edu>
 * - Added prepare()
 * - Moved empty query check from individual calls to prepare() since they all call it anyway
 */

namespace CCR\DB;

use \PDO;
use \Exception;

/*
 * @class PDODB
 * The implementation of the interface iDatabase based on PDO
 */

class PDODB implements iDatabase
{
    public $_db_engine = null;
    public $_db_host = null;
    public $_db_port = null;
    public $_db_name = null;
    public $_db_username = null;
    public $_db_password = null;

    public $_dsn_override = null;

    protected $_dbh = null;

    protected static $_debug_mode = false;
    protected static $_queries = array();
    protected static $_params = array();

    // --------------------------------------------------------------------------------
    // Constructor
    //
    // @param $db_engine PDO database engine name
    // @param $db_host Database hostname
    // @param $db_port Database port
    // @param $db_name Database name
    // @param $db_username Database username
    // @param $db_password Database user password
    // --------------------------------------------------------------------------------

    public function __construct($db_engine, $db_host, $db_port, $db_name, $db_username, $db_password, $dsn_override = null)
    {
        $this->_db_engine = $db_engine;
        $this->_db_host = $db_host;
        $this->_db_port = $db_port;
        $this->_db_name = $db_name;
        $this->_db_username = $db_username;
        $this->_db_password = $db_password;
        $this->_dsn_override = $dsn_override;
    } // __construct()

    // --------------------------------------------------------------------------------

    function __destruct()
    {
        $this->destroy();
    }
    
    // --------------------------------------------------------------------------------

    public function disconnect()
    {
        if (null !== $this->_dbh) {
            $this->_dbh->close();
        }
    }

    // --------------------------------------------------------------------------------
    // Connect to the database using PDO
    //
    // @throws PDOException if an error ocurred connecting to the database
    //
    // @returns An instance of the PDO database handle
    // --------------------------------------------------------------------------------

    public function connect()
    {
        if (null === $this->_dbh) {
            try {
                $dsn = (null !== $this->_dsn_override) ? $this->_dsn_override : $this->_db_engine . ':host=' . $this->_db_host . ';port=' . $this->_db_port . ';dbname=' . $this->_db_name;

                $this->_dbh = new PDO($dsn, $this->_db_username, $this->_db_password);
                $this->_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (Exception $err) {
                throw $err;
            }
        }
        return $this->_dbh;
    } // connect()

    // --------------------------------------------------------------------------------

    public function destroy()
    {
        $this->_dbh = null;
    }

    // --------------------------------------------------------------------------------
    // @returns The database handle
    // --------------------------------------------------------------------------------

    public function handle()
    {
        return $this->connect();
    }

    // --------------------------------------------------------------------------------
    // @see Database::query()
    // --------------------------------------------------------------------------------

    public function query($query, array $params = array(), $returnStatement = false)
    {
        $stmt = $this->prepare($query);

        try {
            if ($this->debugging()) {
                $this->debug($query, $params);
            }
        } catch (Exception $e) {
            // TODO: setup the logger and log this.
        }
      
        if (false === $stmt->execute($params)) {
            list($sqlState, $errorCode, $errorMsg) = $stmt->errorInfo;
            throw new Exception("$sqlState: $errorMsg ($errorCode)");
        }
        if ($returnStatement !== false) {
            return $stmt;
        } else {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } // query()

    // --------------------------------------------------------------------------------
    // @see Database::execute()
    // --------------------------------------------------------------------------------

    public function execute($query, array $params = array())
    {
        $stmt = $this->prepare($query);
      
        try {
            if ($this->debugging()) {
                $this->debug($query, $params);
            }
        } catch (Exception $e) {
            // TODO: setup the logger and log this.
        }

        if (false === $stmt->execute($params)) {
            list($sqlState, $errorCode, $errorMsg) = $stmt->errorInfo;
            throw new Exception("$sqlState: $errorMsg ($errorCode)");
        }

        return $stmt->rowCount();
    } // execute()

    // --------------------------------------------------------------------------------
    // Prepare a query for execution and return the prepared statement.
    //
    // @param $query The query string
    //
    // @throws Exception if the query string is empty
    // @throws Exception if there was an error preparing the query
    //
    // @return The prepared PDOStatement
    // --------------------------------------------------------------------------------
  
    public function prepare($query)
    {
        if (empty($query)) {
            throw new Exception("No query string provided");
        }
      
        return $this->handle()->prepare($query);
    }  // prepare()

    // --------------------------------------------------------------------------------
    // Perform an INSERT command
    //
    // @param $statement The insert statement / command
    // @param $params An array of values with as many elements as there are bound
    //                parameters in the SQL statement being executed.
    //
    // @throws Exception if the statement string is empty
    // @throws PDOException if table does not exist or other db errors
    //
    // @returns An integer referring to the id associated with the recently inserted record
    // --------------------------------------------------------------------------------

    public function insert($statement, $params = array())
    {
        $stmt = $this->prepare($statement);
      
        try {
            if ($this->debugging()) {
                $this->debug($statement, $params);
            }
        } catch (Exception $e) {
            // TODO: setup the logger and log this.
        }
      
        if (false === $stmt->execute($params)) {
            list($sqlState, $errorCode, $errorMsg) = $stmt->errorInfo;
            throw new Exception("$sqlState: $errorMsg ($errorCode)");
        }

        return $this->handle()->lastInsertID();
    } // insert()

    // --------------------------------------------------------------------------------
    // Get the number of rows in a table
    //
    // @param $schema the schema the table belongs to
    // @param $table the name of the table to count rows for
    //
    // @throws Exception if the table parameter is empty
    // @throws PDOException if table does not exist or other db errors

    // @returns the number of rows in the table
    // --------------------------------------------------------------------------------

    public function getRowCount($schema, $table)
    {
        if (empty($table)) {
            throw new Exception("PDODB::getRowCount:: No table string provided");
        }

        $full_tablename = (empty($schema) ? '' : $schema . '.') . $table;
        $query = "select count(*) as count_result from $full_tablename";

        try {
            if ($this->debugging()) {
                $this->debug($query, array());
            }
        } catch (Exception $e) {
            // TODO: setup the logger and log this.
        }

        $count_result = $this->query($query);

        return intval($count_result[0]['count_result']);
    } // getRowCount()

    // --------------------------------------------------------------------------------
    // Transform a PDO exception into nicely formatted HTML that is printed to
    // the screen.
    //
    // @param $err PDOException object to be displayed
    // --------------------------------------------------------------------------------

    public static function exceptionToHTML(PDOException $err)
    {
        $trace = '<table border="0">';
        foreach ($err->getTrace() as $a => $b) {
            foreach ($b as $c => $d) {
                if ($c == 'args') {
                    foreach ($d as $e => $f) {
                        $trace .= '<tr><td><b>' . strval($a) . '#</b></td><td align="right"><u>args:</u></td> <td><u>' . $e . '</u>:</td><td><i>' . $f . '</i></td></tr>';
                    }
                } else {
                    $trace .= '<tr><td><b>' . strval($a) . '#</b></td><td align="right"><u>' . $c . '</u>:</td><td></td><td><i>' . $d . '</i></td>';
                }
            }
        }
        $trace .= '</table>';
        echo '<br /><br /><br /><font face="Verdana"><center><fieldset style="width: 66%; border: 4px ;"><legend><b>[</b>PHP PDO Error ' . strval($err->getCode()) . '<b>]</b></legend> <table border="0"><tr><td align="right"><b><u>Message:</u></b></td><td><i>' . $err->getMessage() . '</i></td></tr><tr><td align="right"><b><u>Code:</u></b></td><td><i>' . strval($err->getCode()) . '</i></td></tr><tr><td align="right"><b><u>File:</u></b></td><td><i>' . $err->getFile() . '</i></td></tr><tr><td align="right"><b><u>Line:</u></b></td><td><i>' . strval($err->getLine()) . '</i></td></tr><tr><td align="right"><b><u>Trace:</u></b></td><td><br /><br />' . $trace . '</td></tr></table></fieldset></center></font>';
    }  // exceptionToHTML()


    public function beginTransaction()
    {
        return $this->handle()->beginTransaction();
    }

    public function commit()
    {
        return $this->handle()->commit();
    }

    public function rollBack()
    {
        return $this->handle()->rollBack();
    }

    public function quote($string)
    {
        return $this->handle()->quote($string);
    }

    public static function debugInfo()
    {
        return array(
            "queries" => PDODB::$_queries,
            "params" => PDODB::$_params
        );
    }

    public static function debugOn()
    {
        PDODB::$_debug_mode = true;
    }

    public static function debugOff()
    {
        PDODB::$_debug_mode = false;
    }

    public static function resetDebugInfo()
    {
        unset($GLOBALS['PDODB::$_queries']);
        unset($GLOBALS['PDODB::$_params']);
    }

    private function debugging()
    {
        $sql_debug_mode = false;

        try {
            $sql_debug_mode = \xd_utilities\getConfiguration('general', 'sql_debug_mode');
        } catch (Exception $e) {
        }


        return  $sql_debug_mode || PDODB::$_debug_mode;
    }

    private function debug($query, $params)
    {
        PDODB::$_queries[] = trim(preg_replace("(\s+)", " ", $query));
        PDODB::$_params[] = PDODB::protectParams($params);
    }

    private static function protectParams($params)
    {
        foreach ($params as $key => $value) {
            if (is_string($key) && strtolower($key) === 'password') {
                $length = strlen($value);
                $mask = str_repeat("*", $length);
                $params[$key] = $mask;
            }
        }
        return $params;
    }
}
