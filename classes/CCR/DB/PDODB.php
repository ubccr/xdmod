<?php
/* ==========================================================================================
 * @author Amin Ghadersohi
 * @date 2010-Jul-07
 *
 * The top interface for dbs using pdo driver, providing all of the necerssary
 * functionality unless overrides are needed.
 *
 * Changelog
 *
 * 2015-12-15 Steve Gallo <smgallo@buffalo.edu>
 * - Added prepare()
 * - Moved empty query check from individual calls to prepare() since they all call it anyway
 *
 * 2017-01-12 Steve Gallo <smgallo@buffalo.edu>
 * - Added generateDsn()
 * - Renamed destroy() to disconnect()
 * - Cleaned up connect()
 * - Renamed $dsn_override to $dsn_extra to allow extra parameters to be added
 * ==========================================================================================
 */

namespace CCR\DB;

use PDO;
use Exception;

class PDODB implements iDatabase
{

    // Database connection parameters. Be aware that these are accessed directly
    // throughout the ETLv1 code and in the ETLv2 pdoIngester.php
    public $_db_engine = null;
    public $_db_host = null;
    public $_db_port = null;
    public $_db_name = null;
    public $_db_username = null;
    public $_db_password = null;

    // Optional extra parameters to be added to the DSN
    public $dsn_extra = null;

    // Generated DSN
    protected $dsn = null;

    // Handle to the PDO instance
    protected $_dbh = null;

    protected static $debug_mode = false;
    protected static $queries = array();
    protected static $params = array();

    // --------------------------------------------------------------------------------
    // @see iDatabase::__construct()
    // --------------------------------------------------------------------------------

    public function __construct($db_engine, $db_host, $db_port, $db_name, $db_username, $db_password, $dsn_extra = null)
    {
        $this->_db_engine = $db_engine;
        $this->_db_host = $db_host;
        $this->_db_port = $db_port;
        $this->_db_name = $db_name;
        $this->_db_username = $db_username;
        $this->_db_password = $db_password;
        $this->dsn_extra = $dsn_extra;
    } // __construct()

    // --------------------------------------------------------------------------------
    // Perform any necessary cleanup when the object is destroyed
    // --------------------------------------------------------------------------------

    public function __destruct()
    {
        $this->disconnect();
    }

    // --------------------------------------------------------------------------------
    // See iDatabase::connect()
    // --------------------------------------------------------------------------------

    public function connect()
    {
        if ( null !== $this->_dbh) {
            return $this->_dbh;
        }

        $this->dsn = $this->generateDsn();
        $this->_dbh = new PDO($this->dsn, $this->_db_username, $this->_db_password);
        $this->_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $this->_dbh;
    } // connect()

    // --------------------------------------------------------------------------------
    // See iDatabase::disconnect()
    // --------------------------------------------------------------------------------

    public function disconnect()
    {
        $this->_dbh = null;
    }


    // --------------------------------------------------------------------------------
    // @see iDatabase::handle()
    // --------------------------------------------------------------------------------

    public function handle()
    {
        return $this->connect();
    }

    // ------------------------------------------------------------------------------------------
    // Generate a standard PDO DSN. This method may be overriden by a child class if needed.
    //
    // @return A PDO connection DSN
    // ------------------------------------------------------------------------------------------

    protected function generateDsn()
    {
        $dsn = $this->_db_engine
            . ':host=' . $this->_db_host
            . ( null !== $this->_db_port ? ';port=' . $this->_db_port : '' )
            . ';dbname=' . $this->_db_name;

        if ( null !== $this->dsn_extra ) {
            $dsn .= ( 0 !== strpos($this->dsn_extra, ';') ? ';' : '' ) . $this->dsn_extra;
        }

        return $dsn;
    }  // generateDsn()

    // ------------------------------------------------------------------------------------------
    // @return The generated DSN, or NULL of no DSN has been generated.
    // ------------------------------------------------------------------------------------------

    public function getDsn()
    {
        return $this->dsn;
    }  // getDsn()

    // --------------------------------------------------------------------------------
    // @see iDatabase::query()
    // --------------------------------------------------------------------------------

    public function query($query, array $params = array(), $returnStatement = false)
    {
        $stmt = $this->prepare($query);

        try {
            if ( $this->debugging() ) {
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
    // @see iDatabase::execute()
    // --------------------------------------------------------------------------------

    public function execute($query, array $params = array())
    {
        $stmt = $this->prepare($query);

        try {
            if ( $this->debugging() ) {
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
    // @see iDatabase::prepare()
    // --------------------------------------------------------------------------------

    public function prepare($query)
    {
        if (empty($query)) {
            throw new Exception("No query string provided");
        }

        return $this->handle()->prepare($query);

    }  // prepare()

    // --------------------------------------------------------------------------------
    // @see iDatabase::insert()
    // --------------------------------------------------------------------------------

    public function insert($statement, $params = array())
    {
        $stmt = $this->prepare($statement);

        try {
            if ( $this->debugging() ) {
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
    // @see iDatabase::getRowCount()
    // --------------------------------------------------------------------------------

    public function getRowCount($schema, $table)
    {
        if (empty($table)) {
            throw new Exception(__CLASS__ . ": No table string provided");
        }

        $full_tablename = (empty($schema) ? '' : $schema . '.') . $table;
        $query = "select count(*) as count_result from $full_tablename";

        try {
            if ( $this->debugging() ) {
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
            "queries" => PDODB::$queries,
            "params" => PDODB::$params
        );
    }

    public static function debugOn()
    {
        PDODB::$debug_mode = true;
    }

    public static function debugOff()
    {
        PDODB::$debug_mode = false;
    }

    public static function resetDebugInfo()
    {
        unset($GLOBALS['PDODB::$queries']);
        unset($GLOBALS['PDODB::$params']);
    }

    private function debugging()
    {
        $sql_debug_mode = false;

        try {
            $sql_debug_mode = \xd_utilities\getConfiguration('general', 'sql_debug_mode');
        } catch (Exception $e) {
        }

        return  $sql_debug_mode || PDODB::$debug_mode;
    }

    private function debug($query, $params)
    {
        PDODB::$queries[] = trim(preg_replace("(\s+)", " ", $query));
        PDODB::$params[] = PDODB::protectParams($params);
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
}  // class PDODB
