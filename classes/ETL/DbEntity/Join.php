<?php
/* ==========================================================================================
 * Class for managing table indexes in the data warehouse.  This is meant to be used as a component
 * of Table.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-10-29
 *
 * @see Table
 * @see iTableItem
 * ==========================================================================================
 */

namespace ETL\DbEntity;

use \Log;
use \stdClass;

class Join extends aNamedEntity implements iTableItem
{
    // NOTE: The join name is treated as the table.

    // Optional join type (e.g., "LEFT OUTER")
    private $type = null;

    // Alias for the joined table
    private $alias = null;

    // Optional ON clause
    private $on = null;

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($config, $systemQuoteChar = null, Log $logger = null)
    {
        parent::__construct($systemQuoteChar, $logger);

        if ( ! is_object($config) ) {
            $msg = __CLASS__ . ": Join definition must be an object";
            $this->logAndThrowException($msg);
        }

        $requiredKeys = array("name");
        $this->verifyRequiredConfigKeys($requiredKeys, $config);

        $this->initialize($config);

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see aNamedEntity::initialize()
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(stdClass $config, $force = false)
    {
        if ( $this->initialized && ! $force ) {
            return true;
        }

        foreach ( $config as $property => $value ) {
            if ( $this->isComment($property) ) {
                continue;
            }

            if ( ! property_exists($this, $property) ) {
                $msg = "Property '$property' in config is not supported";
                $this->logAndThrowException($msg);
            }

            $this->$property = $value;
        }  // foreach ( $config as $property => $value )

        $this->initialized = true;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * Filter values based on the property. Some properties are true/false but may be specified as
     * true, null, or YES depending on the input source. Other properties may be empty strings when
     * discovered from the database which should be treated as null for our purposes
     *
     * @param $property The property we are filtering
     * @param $value The value of the property as presented from the source (array, object, database)
     *
     * @return The filtered value
     * ------------------------------------------------------------------------------------------
     */

    private function filterValue($property, $value)
    {
        switch ( $property ) {
            default:
                break;
        }  // switch ( $property )

        return $value;
    }  // filterValue()

    /* ------------------------------------------------------------------------------------------
     * Return the optional ON clause for this join.
     *
     * @return The on clause, or null if no on clause was specified.
     * ------------------------------------------------------------------------------------------
     */

    public function getType()
    {
        return $this->type;
    }  // getType()

    /* ------------------------------------------------------------------------------------------
     * Return the optional alias for this table table.
     *
     * @return The alias, or null if no alias was specified.
     * ------------------------------------------------------------------------------------------
     */

    public function getAlias()
    {
        return $this->alias;
    }  // getAlias()

    /* ------------------------------------------------------------------------------------------
     * Return the optional ON clause for this join.
     *
     * @return The on clause, or null if no on clause was specified.
     * ------------------------------------------------------------------------------------------
     */

    public function getOn()
    {
        return $this->on;
    }  // getOn()

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::compare()
     * ------------------------------------------------------------------------------------------
     */

    public function compare(iTableItem $cmp)
    {
        if ( ! $cmp instanceof Join ) {
            return 1;
        }

        return 0;

    }  // compare()

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::getCreateSql()
     * ------------------------------------------------------------------------------------------
     */

    public function getCreateSql($includeSchema = false)
    {
        $parts = array();

        // Allow subqueries to be included and not quoted
        $quoteName = ( 0 !== strpos($this->getName(), '(') );

        $parts[] = ( null !== $this->schema && $includeSchema ? $this->getFullName() : $this->getName($quoteName) );
        if ( null !== $this->alias ) {
            $parts[] = "AS {$this->alias}";
        }
        if ( null !== $this->on ) {
            $parts[] = "ON {$this->on}";
        }

        return implode(" ", $parts);

    }  // getCreateSql()

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::getAlterSql()
     *
     * There is no alter SQL for this item.
     * ------------------------------------------------------------------------------------------
     */

    public function getAlterSql($includeSchema = false)
    {
        return "";
    }  // getAlterSql()

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::toJsonObj()
     * ------------------------------------------------------------------------------------------
     */

    public function toJsonObj($succinct = false)
    {
        $data = new stdClass;
        $data->name = $this->name;
        if ( null !== $this->schema ) {
            $data->schema = $this->schema;
        }
        if ( null !== $this->type ) {
            $data->type = $this->type;
        }
        if ( null !== $this->alias ) {
            $data->alias = $this->alias;
        }
        if ( null !== $this->on ) {
            $data->on = $this->on;
        }

        return $data;

    }  // toJsonObj()
}  // class Join
