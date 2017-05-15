<?php
/* ==========================================================================================
 * Interface for all database model entities including tables, columns, indexes, and triggers.
 *
 * @author Steve Gallo  <smgallo@buffalo.edu>
 * @date 2017-04-28
 * ==========================================================================================
 */

namespace ETL\DbModel;

use Log;
use stdClass;

interface iEntity
{
    /* ------------------------------------------------------------------------------------------
     * The contructor MUST provide a configuration specification (or null if configuration
     * will be handled manually). The type and verification of the specification is up to
     * the implementation, but the default is a stdClass object.  Additional arguments MAY
     * be provided and will be handled by the individual contructors using func_get_args()
     *
     * @param mixed $config A representation of the configuration containing the item
     *    definition, or possibly a file name if supported by the particular item. If
     *    $config is NULL, an object with no property values should be created.
     * @param string $systemQuoteChar Character used for escaping system identifiers.
     * @param Log $logger PEAR Log object for system logging
     *
     * @throw Exception If an invalid nummber of arguments was provided
     * @throw Exception If the column definition was incomplete
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($config, $systemQuoteChar = null, Log $logger = null);

    /* ------------------------------------------------------------------------------------------
     * Initialize the object properties from a stdClass object. Only supported properties
     * will be set.
     *
     * @param stdClass $config An object containing (property, value) pairs to be set.
     *
     * @return This object
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(stdClass $config);

    /* ------------------------------------------------------------------------------------------
     * Reset all of the data properties of this object to their default (unconfigured)
     * values. This bypasses the usual checks enforced in filterAndVerifyValue() and
     * __set().
     *
     * @return This object
     * ------------------------------------------------------------------------------------------
     */

    public function resetPropertyValues();

    /* ------------------------------------------------------------------------------------------
     * Perform any necessary verification on the entity after it has been initialized. For
     * simple entities, verificaiton may be a no-op. For example, table verification may
     * entail checking that columns specified in index definitions are present in the
     * table definition and Query verification may check that requested columns are
     * present in the destination table.
     *
     * NOTE: The interface simple requires that this method is define and does not take
     * any parameters. The implementation may require parameters and use
     * function_get_args() to access them.
     *
     * @return TRUE if verification was successful, FALSE otherwise.
     * ------------------------------------------------------------------------------------------
     */

    public function verify();

    /* ------------------------------------------------------------------------------------------
     * Compare the specified entity to this one and return 0 if the entities are the same,
     * -1 if the specified entity is considered less than this one, or 1 of the specified
     * entity is considered greater than this one. Not all entities support the concept of
     * greater or less than in which case any non-zero value is considered different.
     *
     * @return 0 of the entities are the same, -1 if the specified entity is considered
     *   less than this one, or 1 of the specified entity is considered greater than this
     *   one.
     * ------------------------------------------------------------------------------------------
     */

    public function compare(iEntity $cmp);

    /* ------------------------------------------------------------------------------------------
     * Wrap a system identifier in quotes appropriate for the endpint if it is not already
     * quoted. For example, MySQL uses a backtick (`) to quote identifiers while Oracle
     * and Postgres using double quotes (").
     *
     * @param $identifier A system identifier (schema, table, column name) to quote
     *
     * @return The identifier quoted appropriately for the endpoint
     * ------------------------------------------------------------------------------------------
     */

    public function quote($identifier);

    /* ------------------------------------------------------------------------------------------
     * Generate a simple representation of this entity's data properties as a stdClass
     * object suitable for manipulating and feeding back into this entity's constructor as
     * a configuration object. Essentially, re-create the configuration object and any
     * changes.  This can also be used to convert the object into a JSON
     * representation. Any complex objects contained in the properties should implement
     * iEntity so their toStdClass() method can be called to convert to stdClass,
     * otherwise get_object_vars() may be used instead.
     *
     * @return A stdClass object representation for this entity
     * ------------------------------------------------------------------------------------------
     */

    public function toStdClass();

    /* ------------------------------------------------------------------------------------------
     * Generate a JSON representation of this entity.
     *
     * @return A JSON string representation for this entity
     * ------------------------------------------------------------------------------------------
     */

    public function toJson();

    /* ------------------------------------------------------------------------------------------
     * Generate an array containing all SQL statements or fragments required to create
     * this entity. Note that some entities such as columns will generate SQL fragments
     * and other entities such as triggers may require multiple statements to manage them
     * (e.g., DROP TRIGGER, CREATE TRIGGER). Tables with triggers require multiple SQL
     * statements to manage them (e.g., CREATE TABLE and CREATE TRIGGER).
     *
     * @param $includeSchema TRUE to include the schema in the entity names, if
     *    appropriate.
     *
     * @return An array comtaining all SQL statements required for creating this item or
     *    FALSE if there was an error.
     * ------------------------------------------------------------------------------------------
     */

    public function getSql($includeSchema = false);

    /* ------------------------------------------------------------------------------------------
     * @return A string representation for this item. The format of the representation is flexible.
     * ------------------------------------------------------------------------------------------
     */

    public function __toString();
}  // interface iEntity
