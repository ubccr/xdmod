<?php
/* ==========================================================================================
 * The top interface that must be implemented for all db classes returned by PDODB::factory()
 *
 * @author Amin Ghadersohi
 * @date 2010-Jul-07
 *
 * Changelog
 *
 * 2015-12-15 Steve Gallo <smgallo@buffalo.edu>
 * - Added prepare()
 * - Changed name from Database to iDatabase for consistency with coding conventions
 *
 * 2017-01-12 Steve Gallo <smgallo@buffalo.edu>
 * - Added methods that were added over time to PDODB (__construct(), handle(), insert(),
 *   getRowCount())
 * - Added documentation and cleaned up for consistency
 * ==========================================================================================
 */

namespace CCR\DB;

interface iDatabase
{
    /* ------------------------------------------------------------------------------------------
     * Constructor
     *
     * @param $db_engine PDO database engine name
     * @param $db_host Database hostname
     * @param $db_port Database port
     * @param $db_name Database name
     * @param $db_username Database username
     * @param $db_password Database user password
     * @param $dsn_extra Optional extra parameters to be added to the DSN
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($db_engine, $db_host, $db_port, $db_name, $db_username, $db_password);

    /* ------------------------------------------------------------------------------------------
     * Perform any necessary cleanup when the object is destroyed, such as closing the
     * connection
     * ------------------------------------------------------------------------------------------
     */

    public function __destruct();

    /* ------------------------------------------------------------------------------------------
     * Establishes the connection to the database.
     *
     * @return A PDO database object
     * ------------------------------------------------------------------------------------------
     */

    public function connect();

    /* ------------------------------------------------------------------------------------------
     * Releases the connection to the database.
     * ------------------------------------------------------------------------------------------
     */

    public function disconnect();

    /* ------------------------------------------------------------------------------------------
     * @return The the PDO handle created by connect()
     * ------------------------------------------------------------------------------------------
     */

    public function handle();

    /* ------------------------------------------------------------------------------------------
     * Perform a query and return an associative array of results.  This is the recommended
     * method for executing SELECT statements.
     *
     * @param $query The query string
     * @param $params An array of values with as many elements as there
     *     are bound parameters in the SQL statement being executed.
     *
     * @throws Exception if the query string is empty
     * @throws Exception if there was an error executing the query
     *
     * @return An array containing the query results
     * ------------------------------------------------------------------------------------------
     */

    public function query($query, array $params = array(), $returnStatement = false);

    /* ------------------------------------------------------------------------------------------
     * Execute an SQL statement and return the number of rows affected.
     * This is the recommended method for executing non-SELECT
     * statements.
     *
     * @param $query The query string
     * @param $params An array of values with as many elements as there
     *     are bound parameters in the SQL statement being executed.
     *
     * @throws Exception if the query string is empty
     * @throws Exception if there was an error executing the query
     *
     * @returns The number of rows affected by the statement
     * ------------------------------------------------------------------------------------------
     */

    public function execute($query, array $params = array());

    /* ------------------------------------------------------------------------------------------
     * Prepare a query for execution and return the prepared statement.
     *
     * @param $query The query string
     *
     * @throws Exception if the query string is empty
     * @throws Exception if there was an error preparing the query
     *
     * @return The prepared statement
     * ------------------------------------------------------------------------------------------
     */

    public function prepare($query);

    /* ------------------------------------------------------------------------------------------
     * Specifically support a query that is an INSERT command and return the last insert id.
     *
     * @param $statement The insert statement / command
     * @param $params An array of values with as many elements as there are bound
     *                parameters in the SQL statement being executed.
     *
     * @throws Exception if the statement string is empty
     * @throws PDOException if table does not exist or other db errors
     *
     * @returns An integer referring to the id associated with the recently inserted record
     * ------------------------------------------------------------------------------------------
     */

    public function insert($statement, $params = array());

    /* ------------------------------------------------------------------------------------------
     * Return the number of rows in a table.
     *
     * @param $schema the schema the table belongs to
     * @param $table the name of the table to count rows for
     *
     * @throws Exception if the table parameter is empty
     * @throws PDOException if table does not exist or other db errors
     *
     * @returns the number of rows in the table
     * ------------------------------------------------------------------------------------------
     */

    public function getRowCount($schema, $table);

    /**
     * Start a database transaction.
     *
     * @return bool
     */
    public function beginTransaction();

    /**
     * Commit the current database transaction.
     *
     * @return bool
     */
    public function commit();

    /**
     * Roll back the current database transaction.
     *
     * @return bool
     */
    public function rollBack();

    /**
     * Quote a string for use in a query.
     *
     * @param string $string The string to quote.
     *
     * @return string The quoted string.
     */
    public function quote($string);
}
