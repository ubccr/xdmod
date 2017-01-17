<?php
/* ==========================================================================================
 * Interface for providing functionality required for all RDBMS endpoints, namely the ability to
 * quote system identifiers with the correct character and querying table columns for existing
 * tables.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-12-10
 *
 * @see aDataEndpoint
 * ==========================================================================================
 */

namespace ETL\DataEndpoint;

interface iRdbmsEndpoint extends iDataEndpoint
{

    /* ------------------------------------------------------------------------------------------
     * Wrap a system identifier in quotes appropriate for the endpint. For example, MySQL uses a
     * backtick (`) to quote identifiers while Oracle and Postgres using double quotes (").
     *
     * @param $identifier A system identifier (schema, table, column name)
     *
     * @return The identifier quoted appropriately for the endpoint
     * ------------------------------------------------------------------------------------------
     */

    public function quoteSystemIdentifier($identifier);

    /* ------------------------------------------------------------------------------------------
     * @return The character for quoting system identifiers using this endpoint.
     * ------------------------------------------------------------------------------------------
     */

    public function getSystemQuoteChar();

    /* ------------------------------------------------------------------------------------------
     * Query the RDBMS and return TRUE if the table exists, FALSE otherwise.
     *
     * @param $tableName The table to inspect
     * @param $schemaName An optional schema to inspect
     *
     * @return TRUE if the table exists.
     * ------------------------------------------------------------------------------------------
     */

    public function tableExists($tableName, $schemaName = null);

    /* ------------------------------------------------------------------------------------------
     * Query the RDBMS schema associated with this endpoint for the columns
     * associated with the named table.
     *
     * @param $tableName The table to inspect
     * @param $schemaName Optional schema name used to override the current DataEmdpoint schema
     *
     * @return An array of column names for the table
     *
     * @throws Exception if the table does not exist in this schema.
     * ------------------------------------------------------------------------------------------
     */

    public function getTableColumnNames($tableName, $schemaName = null);

    /* ------------------------------------------------------------------------------------------
     * Query the RDBMS and return TRUE if the schema exists, FALSE otherwise.
     *
     * @param $schemaName The schema to inspector NULL current DataEndpoint schema
     *
     * @return TRUE if the schema exists.
     * ------------------------------------------------------------------------------------------
     */

    public function schemaExists($schemaName = null);

    /* ------------------------------------------------------------------------------------------
     * Create a schema.
     *
     * @param $schemaName The schema to create, or NULL to use the current DataEndpoint schema
     *
     * @return TRUE if the schema was created.
     *
     * @throws Exception If there was an error creating the schema.
     * ------------------------------------------------------------------------------------------
     */

    public function createSchema($schemaName = null);

    /* ------------------------------------------------------------------------------------------
     * @param $quote TRUE to wrap the name in quotes to handle special characters
     *
     * @return The name of the schema to be used with this endpoint. Note that this is different than
     * the default schema specified in the database configuration in portal_settings.ini
     * ------------------------------------------------------------------------------------------
     */

    public function getSchema($quote = false);
}  // interface iRdbmsEndpoint
