<?php
/* ==========================================================================================
 * Named database entity providing support for a name and schema.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-01-25
 * ==========================================================================================
 */

namespace ETL\DbEntity;

use ETL\aEtlObject;
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;

use \Log;
use \stdClass;

abstract class aNamedEntity extends aEtlObject
{
    // The optional schema name
    protected $schema = null;

    // Character used to quote system identifiers. Mysql uses a backtick while postgres and oracle use
    // a single quote. This is set as a static variable so we can use it in static scope in
    // Table::discover()

    protected $systemQuoteChar = '`';

    // Keys starting with this character are considered comments
    const COMMENT_KEY = "#";

    /* ------------------------------------------------------------------------------------------
     * Construct a database entity object from a JSON definition file or a definition object.
     *
     * @param $systemQuoteChar The character used to quote database system identifiers (may be empty)
     * @param $logger PEAR Log object for system logging
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($systemQuoteChar = null, Log $logger = null)
    {
        parent::__construct($logger);
        $this->setSystemQuoteChar($systemQuoteChar);
    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Initialize data for this item from a configuration object.
     *
     * @param $config A stdClass object representing the item
     * @param $force Force a re-initialization even if the entity has previously been initialized
     *
     * @throw Exception If a property in the config file does not exist in the object.
     * ------------------------------------------------------------------------------------------
     */

    abstract protected function initialize(stdClass $config, $force = false);

    /* ------------------------------------------------------------------------------------------
     * Return the table name.
     *
     * @param $quote true to wrap the name in quotes to handle special characters
     *
     * @return The name of this table, optionally quoted with the schema
     * ------------------------------------------------------------------------------------------
     */

    public function getName($quote = false)
    {
        return ( $quote ? $this->quote($this->name) : $this->name );
    }  // getName()

    /* ------------------------------------------------------------------------------------------
     * @param $quote TRUE if the schema and name should be quoted, defaults to TRUE.
     *
     * @return The fully qualified and quoted name including the schema, if one was set.
     * ------------------------------------------------------------------------------------------
     */

    public function getFullName($quote = true)
    {
        return ( null !== $this->schema
                 ? $this->getSchema($quote) . "."
                 : "" ) . $this->getName($quote);
    }  // getFullName()

    /* ------------------------------------------------------------------------------------------
     * @param $quote true to wrap the name in quotes to handle special characters
     *
     * @return The schema for this table or null of no schema was set
     * ------------------------------------------------------------------------------------------
     */

    public function getSchema($quote = false)
    {
        return ( $quote
                 ? $this->quote($this->schema)
                 : $this->schema );
    }  // getSchema()

    /* ------------------------------------------------------------------------------------------
     * Set the schema for this table. This allows us to dynamically place the table into the correct
     * schema based on the destination data endpoint.
     *
     * @param $schema The new schema (may be empty or null)
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setSchema($schema)
    {
        if ( null !== $schema && ! is_string($schema) ) {
            $msg = "Entity schema must be null or a string";
            $this->logAndThrowException($msg);
        }

        $this->schema = $schema;
        return $this;

    }  // setSchema()

    /* ------------------------------------------------------------------------------------------
     * @return The character used to quote system identifiers
     * ------------------------------------------------------------------------------------------
     */

    public function getSystemQuoteChar()
    {
        return $this->systemQuoteChar;
    }  // getSystemQuoteChar()

    /* ------------------------------------------------------------------------------------------
     * @param $char The character used to quote system identifiers (may be empty)
     * ------------------------------------------------------------------------------------------
     */

    public function setSystemQuoteChar($char)
    {
        if ( null !== $char && ! is_string($char) ) {
            $msg = "System quote character must be a string";
            $this->logAndThrowException($msg);
        }

        $this->systemQuoteChar = $char;
        return $this;

    }  // setSystemQuoteChar()

    /* ------------------------------------------------------------------------------------------
     * Wrap a system identifier in quotes appropriate for the endpint. For example, MySQL uses a
     * backtick (`) to quote identifiers while Oracle and Postgres using double quotes (").
     *
     * @param $identifier A system identifier (schema, table, column name)
     *
     * @return The identifier quoted appropriately for the endpoint
     * ------------------------------------------------------------------------------------------
     */

    public function quote($identifier)
    {
        return $this->systemQuoteChar . $identifier . $this->systemQuoteChar;
    }  // quote()

    /* ------------------------------------------------------------------------------------------
     * Identify commented out keys in JSON definition/specification files.
     *
     * @param $key The string to examine
     *
     * @return TRUE if the key is considered a comment, FALSE otherwise.
     * ------------------------------------------------------------------------------------------
     */

    protected function isComment($key)
    {
        return ( 0 === strpos($key, self::COMMENT_KEY) );
    }  // isComment()

}  // abstract class aNamedEntity
