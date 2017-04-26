<?php
/* ==========================================================================================
 * Class for managing table indexes in the data warehouse.  This is meant to be used as a component
 * of Table.  Note that triggers are created separately from the table definition and to alter a
 * trigger it must be dropped and re-created.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-10-29
 *
 * @see Table
 * @see iTableItem
 * ==========================================================================================
 */

namespace ETL\DbModel;

use \Log;
use \stdClass;

class Trigger extends aNamedEntity implements iTableItem
{
    // The time that the trigger is fired (before, after)
    private $time = null;

    // The event that the trigger is fired on (insert, update, delete)
    private $event = null;

    // The table that the trigger is associated with
    private $table = null;

    // The body of the trigger
    private $body = null;

    // The trigger definer for ACL purposes
    private $definer = null;

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($config, $systemQuoteChar = null, Log $logger = null)
    {
        parent::__construct($systemQuoteChar, $logger);

        if ( ! is_object($config) ) {
            $msg = __CLASS__ . ": Argument is not an object";
            $this->logAndThrowException($msg);
        }

        $requiredKeys = array("name", "time", "event", "table", "body");
        $this->verifyRequiredConfigKeys($requiredKeys, $config);

        $this->initialize($config);

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::initialize()
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
     * @return The time that the trigger will fire, null if not specified
     * ------------------------------------------------------------------------------------------
     */

    public function getTime()
    {
        return $this->time;
    }  // getTime()

    /* ------------------------------------------------------------------------------------------
     * @return The trigger event, null if not specified
     * ------------------------------------------------------------------------------------------
     */

    public function getEvent()
    {
        return $this->event;
    }  // getEvent()

    /* ------------------------------------------------------------------------------------------
     * @return The table that the trigger is associated with, null if not specified
     * ------------------------------------------------------------------------------------------
     */

    public function getTable()
    {
        return $this->table;
    }  // getTable()

    /* ------------------------------------------------------------------------------------------
     * @return The trigger body, null if not specified
     * ------------------------------------------------------------------------------------------
     */

    public function getBody()
    {
        return $this->body;
    }  // getBody()

    /* ------------------------------------------------------------------------------------------
     * @return The trigger definer, null if not specified
     * ------------------------------------------------------------------------------------------
     */

    public function getDefiner()
    {
        return $this->definer;
    }  // getDefiner()

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::compare()
     * ------------------------------------------------------------------------------------------
     */

    public function compare(iTableItem $cmp)
    {
        if ( ! $cmp instanceof Trigger ) {
            return 1;
        }

        // Schemas are optional for the trigger

        // Triggers are considered equal if all non-null properties are the same.

        if ( $this->getName() != $cmp->getName()
             || $this->getTime() != $cmp->getTime()
             || $this->getEvent() != $cmp->getEvent()
             || $this->getTable() != $cmp->getTable()
             || $this->getBody() != $cmp->getBody() ) {
            return -1;
        }

        // The following properties have a default set by the database. If the property is not specified
        // a value will be provided when the database information schema is queried.

        if ( ( null !== $this->getDefiner() && null !== $cmp->getDefiner() )
             && $this->getDefiner() != $cmp->getDefiner() ) {
            return -1;
        }

        // The following properties do not have defaults set by the database and should be considered if
        // one of them is set.

        if ( ( null !== $this->getSchema() || null !== $cmp->getSchema() )
             && $this->getSchema() != $cmp->getSchema() ) {
            return -1;
        }

    }  // compare()

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::getCreateSql()
     * ------------------------------------------------------------------------------------------
     */

    public function getCreateSql($includeSchema = false)
    {
        // Triggers queried from MySQL contain the begin/end but the body in the JSON may or may not.

        $addBeginEnd = ( 0 !== strpos($this->body, "BEGIN") );
        $name = ( $includeSchema ? $this->getFullName() : $this->getName(true) );
        $tableName = ( null !== $this->schema && $includeSchema ? $this->quote($this->schema) . "." : "" ) .
            $this->quote($this->table);
        $parts = array();
        $parts[] = "CREATE";
        if ( null !== $this->definer ) {
            $parts[] = "DEFINER = {$this->definer}";
        }
        $parts[] = "TRIGGER $name";
        $parts[] = $this->time;
        $parts[] = $this->event;
        $parts[] = "ON $tableName FOR EACH ROW\n";
        if ( $addBeginEnd ) {
            $parts[] = "BEGIN\n";
        }
        $parts[] = $this->body;
        if ( $addBeginEnd ) {
            $parts[] = "\nEND";
        }

        return implode(" ", $parts);

    }  // getCreateSql()

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::getAlterSql()
     * ------------------------------------------------------------------------------------------
     */

    public function getAlterSql($includeSchema = false)
    {
        return $this->getCreateSql($includeSchema);
    }  // getAlterSql()

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::toJsonObj()
     * ------------------------------------------------------------------------------------------
     */

    public function toJsonObj($succinct = false)
    {
        // There is no succinct definition for a trigger

        $data = new stdClass;
        $data->name = $this->name;
        if ( null !== $this->schema ) {
            $data->schema = $this->schema;
        }
        $data->time = $this->time;
        $data->event = $this->event;
        $data->table = $this->table;
        // The body may contain newlines, these should be encoded.
        $data->body = $this->body;
        $data->definer = $this->definer;

        return $data;

    }  // toJsonObj()
}  // class Trigger
