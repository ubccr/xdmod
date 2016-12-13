<?php
/* ==========================================================================================
 * Interface for all items comprising a table. This includes columns, indexes, and triggers.
 *
 * @author Steve Gallo  <smgallo@buffalo.edu>
 * @date 2015-10-29
 * ==========================================================================================
 */

namespace ETL\DbEntity;

use \Log;

interface iTableItem
{
    /* ------------------------------------------------------------------------------------------
     * The contructor MUST provide a configuration specification, which may be an array, object, or
     * file depending on the type of table item.  Additional arguments MAY be provided and will be
     * handled by the individual contructors using func_get_args()
     *
     * @param $config An object or an array containing the item definition, or possibly a file name if
     *   supported by the particular item.
     * @param $systemQuoteChar Character used for escaping system identifiers in queries.
     *
     * @throw Exception If an invalid nummber of arguments was provided
     * @throw Exception If the column definition was incomplete
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($config, $systemQuoteChar = null, Log $logger = null);

    /* ------------------------------------------------------------------------------------------
     * @param $quote TRUE to wrap the name in quotes to handle special characters
     *
     * @return The name of this item
     * ------------------------------------------------------------------------------------------
     */

    public function getName($quote = false);

    /* ------------------------------------------------------------------------------------------
     * @return The fully qualified and quoted name including the schema, if one was set.
     * ------------------------------------------------------------------------------------------
     */

    public function getFullName();

    /* ------------------------------------------------------------------------------------------
     * @return A string representation for this item. The format of the representation is flexible.
     * ------------------------------------------------------------------------------------------
     */

    public function __toString();

    /* ------------------------------------------------------------------------------------------
     * Compare the specified object to this one and return 0 of the objects are the same, -1 if the
     * specified object is considered less than this object, or 1 of the specified object is
     * considered greater than this object. Not all object support the concept of greater or less than
     * in which case any non-zero value is considered different.
     *
     * @return 0 of the objects are the same, -1 if the specified object is considered less than this
     *   object, or 1 of the specified object is considered greater than this object.
     * ------------------------------------------------------------------------------------------
     */

    public function compare(iTableItem $cmp);

    /* ------------------------------------------------------------------------------------------
     * Generate an object representation of this item suitable for encoding into JSON. This is
     * designed to be called recursively to build up the representation of a Table object.
     *
     * @param $succinct Flag indicating whether or not the succinct (array) representation should be
     *   returned as opposed to the object notation.
     *
     * @return An object representation for this item suitable for encoding into JSON.
     * ------------------------------------------------------------------------------------------
     */

    public function toJsonObj($succinct = false);

    /* ------------------------------------------------------------------------------------------
     * Generate an array containing all SQL statements or fragments required to create the item. Note
     * that some items (such as triggers) may require multiple statements to alter them (e.g., DROP
     * TRIGGER, CREATE TRIGGER).
     *
     * @param $includeSchema TRUE to include the schema in the item name, if appropriate.
     *
     * @return An string comtaining the SQL required for creating this item.
     * ------------------------------------------------------------------------------------------
     */

    public function getCreateSql($includeSchema = false);

    /* ------------------------------------------------------------------------------------------
     * Generate an array containing all SQL statements or fragments required to alter the item. Note
     * that some items (such as triggers) may require multiple statements to alter them (e.g., DROP
     * TRIGGER, CREATE TRIGGER).
     *
     * @param $includeSchema TRUE to include the schema in the item name, if appropriate.
     *
     * @return An string comtaining the SQL required for altering this item.
     * ------------------------------------------------------------------------------------------
     */

    public function getAlterSql($includeSchema = false);

}  // interface iTableItem
