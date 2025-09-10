<?php

namespace CCR\DB;

use CCR\Log;
use CCR\LogOutput;
use Exception;
use CCR\DB\MySQLDB;
use Psr\Log\LoggerInterface;
use xd_utilities;

class MySQLHelper
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var MySQLDB
     */
    protected $db;

    /**
     * Factory method.
     *
     * @param MySQLDB $db
     *
     * @return MySQLHelper
     */
    public static function factory(MySQLDB $db)
    {
        return new static($db);
    }

    /**
     * Constructor.
     *
     * @param MySQLDB $db
     */
    protected function __construct(MySQLDB $db)
    {
        $this->db     = $db;
        $this->logger = Log::singleton('null');
    }

    /**
     * Set the logger.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get the database.
     *
     * @return MySQLDB
     */
    public function getDatabase()
    {
        return $this->db;
    }

    /**
     * Get the database name.
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->db->_db_name;
    }

    /**
     * Drop a table.
     *
     * @param string $tableName
     */
    public function dropTable($tableName)
    {
        $sql = 'DROP TABLE ' . $this->db->getHandle()->quote($tableName);

        $this->logger->debug("Drop statement: $sql");

        $this->db->execute($sql);
    }

    /**
     * Create a table.
     *
     * @param string $tableName Name of the table that will be created.
     * @param array $columnDefs Column definitions.  Array of arrays for
     *     each column in the table with keys:
     *         - name => Name of the column.
     *         - type => MySQL type.
     *         - nullable => True if the column can contain NULL
     *                       (default false).
     *         - auto => True if this is an AUTO_INCREMENT field
     *                   (default false).
     *         - unique => True if the column is UNIQUE (default false).
     *         - key => "primary" or true if the column is a key
     *                  (default false).
     * @param array $keyDefs (Multi-column) key definitions.  Array of
     *     arrays for each index with keys:
     *         - type => Optional MySQL key type (e.g. "primary" or
     *                   "unique").
     *         - name => Key name (do not use with the "primary" key).
     *         - columns => Array of column names included in the key.
     */
    public function createTable(
        $tableName,
        array $columnDefs,
        array $keyDefs = array()
    ) {

        $columnSql = array();
        $keySql    = array();

        foreach ($columnDefs as $col) {
            $def = $col['name'] . ' ' . $col['type'];

            $nullable = isset($col['nullable']) && $col['nullable'] === true;
            $auto     = isset($col['auto'])     && $col['auto']     === true;
            $unique   = isset($col['unique'])   && $col['unique']   === true;

            if (!$nullable) {
                $def .= ' NOT NULL';
            }

            if ($auto) {
                $def .= ' AUTO_INCREMENT';
            }

            if ($unique) {
                $def .= ' UNIQUE';
            }

            if (isset($col['key'])) {
                if ($col['key'] === 'primary') {
                    $keySql[] = "PRIMARY KEY ({$col['name']})";
                } elseif ($col['key'] === true) {
                    $keySql[] = "KEY {$col['name']} ({$col['name']})";
                }
            }

            $columnSql[] = $def;
        }

        foreach ($keyDefs as $key) {
            $def = '';

            if (isset($key['type'])) {
                $def .= strtoupper($key['type']);
            }

            $def .= ' KEY';

            if (isset($key['name'])) {
                $def .=  ' ' . $key['name'];
            }

            $def .= ' (' . implode(', ', $key['columns']) . ')';

            $keySql[] = $def;
        }

        $sql = "CREATE TABLE $tableName (\n"
            . implode(",\n", array_merge($columnSql, $keySql))
            . "\n)";

        $this->logger->debug("Create statement:\n$sql");

        $this->db->execute($sql);
    }

    /**
     * Check if a table exists in the database.
     *
     * If the table name contains a dot (".") it is assumed that the
     * part of the string before the dot is the schema name.
     *
     * @param string $tableName
     *
     * @return bool
     */
    public function tableExists($tableName)
    {
        $sql = '
            SELECT COUNT(*) AS count
            FROM information_schema.tables
            WHERE table_schema = :schema_name
                AND table_name = :table_name
        ';
        $this->logger->debug("Query: $sql");

        $schemaName = $this->db->_db_name;

        // Check if the schema name is prepended to the table name.
        if (strpos($tableName, '.') !== false) {
            list($schemaName, $tableName) = explode('.', $tableName, 2);
        }

        list($row) = $this->db->query($sql, array(
            'schema_name' => $schemaName,
            'table_name'  => $tableName,
        ));

        return $row['count'] > 0;
    }

    /**
     * Execute a SQL statement.
     *
     * @param string $stmt The statement to execute.
     *
     * @return array The MySQL output.
     */
    public function executeStatement($stmt)
    {
        $this->logger->info(LogOutput::from(array(
            'message'   => 'Executing SQL statement',
            'host'      => $this->db->_db_host,
            'port'      => $this->db->_db_port,
            'username'  => $this->db->_db_username,
            'database'  => $this->db->_db_name,
            'statement' => $stmt,
        )));

        $optionsFile = static::createPasswordFile($this->db->_db_password);

        // --defaults-extra-file must be the first argument.
        $args = array(
            '--defaults-extra-file=' . $optionsFile,
            '-ss',
            '--local-infile',
            '-h', $this->db->_db_host,
            '-P', $this->db->_db_port,
            '-u', $this->db->_db_username,
            $this->db->_db_name,
            '-e', $stmt,
        );

        $output = $this->executeCommand($args);

        unlink($optionsFile);

        return $output;
    }

    /**
     * Execute the contents of a file.
     *
     * @param string $file The path to the file to execute.
     *
     * @return array The MySQL output.
     */
    public function executeFile($file)
    {
        $this->logger->info(LogOutput::from(array(
            'message'  => 'Executing SQL file',
            'host'     => $this->db->_db_host,
            'port'     => $this->db->_db_port,
            'username' => $this->db->_db_username,
            'database' => $this->db->_db_name,
            'file'     => $file,
        )));

        $optionsFile = static::createPasswordFile($this->db->_db_password);

        // --defaults-extra-file must be the first argument.
        $args = array(
            '--defaults-extra-file=' . $optionsFile,
            '-h', $this->db->_db_host,
            '-P', $this->db->_db_port,
            '-u', $this->db->_db_username,
            $this->db->_db_name,
        );

        $output = $this->executeCommand($args, $file);

        unlink($optionsFile);

        return $output;
    }

    /**
     * Execute the MySQL command line client.
     *
     * @param array $args The command arguments.
     * @param string $input Input file path (optional).
     *
     * @return array The command output.
     */
    public function executeCommand(array $args, $input = null)
    {
        return static::staticExecuteCommand($args, $input);
    }

    /**
     * Check that a database exists.
     *
     * Used by the Open XDMoD setup script to determine if a database
     * needs to be created.
     *
     * @param string $host MySQL server host name.
     * @param int $port MySQL server port number.
     * @param string $username MySQL username.
     * @param string $password MySQL password.
     * @param string $dbName Database to check existence of.
     *
     * @return bool True if the database exists.
     */
    public static function databaseExists(
        $host,
        $port,
        $username,
        $password,
        $dbName
    ) {
        $stmt = "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA"
            . " WHERE SCHEMA_NAME = '$dbName'";

        $output = static::staticExecuteStatement(
            $host,
            $port,
            $username,
            $password,
            null,
            $stmt
        );

        if (count($output) == 0) {
            return false;
        } elseif (count($output) == 1 && $output[0] == $dbName) {
            return true;
        } else {
            $msg = 'Failed to check for existence of database: '
                . implode("\n", $output);
            throw new Exception($msg);
        }
    }

    /**
     * Create a database.
     *
     * @param string $host MySQL server host name.
     * @param int $port MySQL server port number.
     * @param string $username MySQL username.
     * @param string $password MySQL password.
     * @param string $dbName Name of the database to create.
     */
    public static function createDatabase(
        $host,
        $port,
        $username,
        $password,
        $dbName
    ) {
        $stmt = "CREATE DATABASE $dbName";

        static::staticExecuteStatement(
            $host,
            $port,
            $username,
            $password,
            null,
            $stmt
        );
    }

    /**
     * Drop a database.
     *
     * @param string $host MySQL server host name.
     * @param int $port MySQL server port number.
     * @param string $username MySQL username.
     * @param string $password MySQL password.
     * @param string $dbName Name of the database to drop.
     */
    public static function dropDatabase(
        $host,
        $port,
        $username,
        $password,
        $dbName
    ) {
        $stmt = "DROP DATABASE $dbName";

        static::staticExecuteStatement(
            $host,
            $port,
            $username,
            $password,
            $dbName,
            $stmt
        );
    }

    /**
     * Grant all privileges to a MySQL user.
     *
     * @param string $host MySQL server host name.
     * @param int $port MySQL server port number.
     * @param string $username MySQL username.
     * @param string $password MySQL password.
     * @param string $localHost Host that the user will connect from.
     * @param string $dbUsername User granting privileges to.
     * @param string $dbPassword User password.
     */
    public static function grantAllPrivileges(
        $host,
        $port,
        $username,
        $password,
        $localHost,
        $dbUsername,
        $dbPassword
    ) {
        /**
         *  The privileges are listed out instead of ALL so that the user
         *  created cannot administrate users or database tasks.
         */
        $stmt = "GRANT TRIGGER, DROP, INDEX, CREATE, INSERT,"
            . " SELECT, DELETE, UPDATE, CREATE VIEW, SHOW VIEW,"
            . " ALTER, SHOW DATABASES, CREATE TEMPORARY TABLES,"
            . " CREATE ROUTINE, ALTER ROUTINE, EVENT, RELOAD, FILE,"
            . " CREATE TABLESPACE, PROCESS, REFERENCES,"
            . " LOCK TABLES"
            . " ON *.* TO '$dbUsername'@'$localHost'"
            . " IDENTIFIED BY '$dbPassword';FLUSH PRIVILEGES;";

        static::staticExecuteStatement(
            $host,
            $port,
            $username,
            $password,
            '',
            $stmt
        );
    }

    /**
     * Grant all privileges to a MySQL user on a specific database.
     *
     * @param string $host MySQL server host name.
     * @param int $port MySQL server port number.
     * @param string $username MySQL username.
     * @param string $password MySQL password.
     * @param string $dbName Database name.
     * @param string $localHost Host that the user will connect from.
     * @param string $dbUsername User granting privileges to.
     * @param string $dbPassword User password.
     */
    public static function grantAllPrivilegesOnDatabase(
        $host,
        $port,
        $username,
        $password,
        $dbName,
        $localHost,
        $dbUsername,
        $dbPassword
    ) {
        $stmt = "GRANT ALL ON $dbName.* TO '$dbUsername'@'$localHost'"
            . " IDENTIFIED BY '$dbPassword'";

        static::staticExecuteStatement(
            $host,
            $port,
            $username,
            $password,
            $dbName,
            $stmt
        );
    }

    /**
     * Execute a SQL statement.
     *
     * @param string $host MySQL server host name.
     * @param int $port MySQL server port number.
     * @param string $username MySQL username.
     * @param string $password MySQL password.
     * @param string|null $dbName Database name or null if no database
     *     should be specified.
     * @param string $stmt The statement to execute.
     *
     * @return array The MySQL output.
     */
    public static function staticExecuteStatement(
        $host,
        $port,
        $username,
        $password,
        $dbName,
        $stmt
    ) {
        $args = array(
            '-ss',
            '--local-infile',
            '-h', $host,
            '-P', $port,
            '-u', $username,
        );

        if ($password !== '') {
            $optionsFile = static::createPasswordFile($password);

            // --defaults-extra-file must be the first argument.
            array_unshift($args, '--defaults-extra-file=' . $optionsFile);
        }

        if ($dbName !== null) {
            $args[] = $dbName;
        }

        array_push($args, '-e', $stmt);

        $output = static::staticExecuteCommand($args);

        if (isset($optionsFile)) {
            unlink($optionsFile);
        }

        return $output;
    }

    /**
     * Execute the contents of a file.
     *
     * @param string $host MySQL server host name.
     * @param int $port MySQL server port number.
     * @param string $username MySQL username.
     * @param string $password MySQL password.
     * @param string $dbName Database name.
     * @param string $file The path to the file to execute.
     *
     * @return array The MySQL output.
     */
    public static function staticExecuteFile(
        $host,
        $port,
        $username,
        $password,
        $dbName,
        $file
    ) {
        $args = array(
            '-h', $host,
            '-P', $port,
            '-u', $username,
        );

        if ($password !== '') {
            $optionsFile = static::createPasswordFile($password);

            // --defaults-extra-file must be the first argument.
            array_unshift($args, '--defaults-extra-file=' . $optionsFile);
        }

        $args[] = $dbName;

        $output = static::staticExecuteCommand($args, $file);

        if (isset($optionsFile)) {
            unlink($optionsFile);
        }

        return $output;
    }

    /**
     * Execute the MySQL command line client.
     *
     * @param array $args The command arguments.
     * @param string $input Input file path (optional).
     *
     * @return array The command output.
     */
    public static function staticExecuteCommand(array $args, $input = null)
    {
        $args = array_map('escapeshellarg', $args);

        $command = 'mysql ' . implode(' ', $args);

        if ($input !== null) {
            $command .= ' <' . escapeshellarg($input);
        }

        $output    = array();
        $returnVar = 0;
        $tmpHome = xd_utilities\createTemporaryDirectory('mysql-helper-');

        exec(
            sprintf('%s %s 2>&1', 'HOME=' . escapeshellarg($tmpHome), $command),
            $output,
            $returnVar
        );

        rmdir($tmpHome);

        if ($returnVar != 0) {
            $msg = "Command returned non-zero value '$returnVar': "
                . implode("\n", $output);
            throw new Exception($msg);
        }

        return $output;
    }

    /**
     * Create a MySQL options file containing the specified password.
     *
     * @param string $password The password to store in the options
     *     file.
     *
     * @return string The full path for the options file.
     */
    private static function createPasswordFile($password)
    {
        $optionsFile = tempnam(sys_get_temp_dir(), 'mysql-options-');

        if ($optionsFile === false) {
            throw new Exception('Failed to create options file');
        }

        $quotedPassword = static::quoteOptionsString($password);

        $bytes = file_put_contents(
            $optionsFile,
            "[client]\npassword = $quotedPassword"
        );

        if ($bytes === false) {
            throw new Exception('Failed to write options file');
        }

        return $optionsFile;
    }

    /**
     * Quote and escape a string to be used in a MySQL options file.
     *
     * Option values typically don't need to be quoted unless they
     * contain a "#" or ";", the characters used to indicate the
     * beginning of a comment.  Also, string containing double-quote or
     * single-quote characters should be escaped and quoted.
     *
     * @param string $str The string that will be quoted.
     *
     * @return string The quoted string.
     */
    private static function quoteOptionsString($str)
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }
}
