<?php
/**
 * The top interface for all db classes
 *
 * @author Amin Ghadersohi
 * @date 2010-Jul-07
 *
 * Changelog
 *
 * 2015-12-15 Steve Gallo <smgallo@buffalo.edu>
 * - Added prepare()
 * - Changed name from Database to iDatabase for consistency with coding conventions
 */

namespace CCR\DB;

/**
 * The interface for database classes.
 */
interface iDatabase
{

   /**
    * Establishes the connection to the database.
    */
    public function connect();

    /*
     * Releases the connection to the database
     */
    public function destroy();

    /**
     * @param string $statement (The SQL INSERT statement)
     * @param array $params (The optional parameters to the database,
     *     when needed)
     *
     * @return int (Returns the index of the recently inserted record)
     */
    public function insert($statement, $params = array());

    /**
     * Perform a query and return an associative array of results.  This
     * is the recommended method for executing SELECT statements.
     *
     * @param $query The query string
     * @param $params An array of values with as many elements as there
     *     are bound parameters in the SQL statement being executed.
     *
     * @throws Exception if the query string is empty
     * @throws Exception if there was an error executing the query
     *
     * @return An array containing the query results
     */
    public function query(
        $query,
        array $params = array(),
        $returnStatement = false
    );

    /**
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
     */
    public function execute($query, array $params = array());

    /**
     * Prepare a query for execution and return the prepared statement.
     *
     * @param $query The query string
     *
     * @throws Exception if the query string is empty
     * @throws Exception if there was an error preparing the query
     *
     * @return The prepared statement
     */

    public function prepare($query);

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
