<?php
/* ==========================================================================================
 * Class for managing triggers in the data warehouse.  This is meant to be used as a
 * component of Table.  Note that triggers are created separately from the table
 * definition and to alter a trigger it must be dropped and re-created.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-28
 *
 * @see Table
 * @see iEntity
 * ==========================================================================================
 */

namespace ETL\DbModel;

use Log;

class Trigger extends SchemaEntity implements iEntity
{
    // Properties required by this class. These will be merged with other required
    // properties up the call chain. See @Entity::$requiredProperties
    private $localRequiredProperties = array(
        'time',
        'event',
        'table',
        'body'
    );

    // Properties provided by this class. These will be merged with other properties up
    // the call chain. See @Entity::$properties
    private $localProperties = array(
        // The time that the trigger is fired (before, after)
        'time'    => null,
        // The event that the trigger is fired on (insert, update, delete)
        'event'   => null,
        // The table that the trigger is associated with
        'table'   => null,
        // The body of the trigger
        'body'    => null,
        // The trigger definer for ACL purposes
        'definer' => null
    );

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($config, $systemQuoteChar = null, Log $logger = null)
    {
        // Property merging is performed first so the values can be used in the constructor
        parent::mergeProperties($this->localRequiredProperties, $this->localProperties);
        parent::__construct($config, $systemQuoteChar, $logger);
    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see Entity::filterAndVerifyValue()
     * ------------------------------------------------------------------------------------------
     */

    protected function filterAndVerifyValue($property, $value)
    {
        $value = parent::filterAndVerifyValue($property, $value);

        if ( null === $value ) {
            return $value;
        }

        switch ( $property ) {
            case 'time':
            case 'event':
            case 'table':
            case 'body':
                if ( ! is_string($value) ) {
                    $this->logAndThrowException(
                        sprintf("%s name must be a string, '%s' given", $property, gettype($value))
                    );
                }
                break;

            default:
                break;
        }  // switch ( $property )

        return $value;

    }  // filterAndVerifyValue()

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::compare()
     * ------------------------------------------------------------------------------------------
     */

    public function compare(iEntity $cmp)
    {
        if ( ! $cmp instanceof Trigger ) {
            return 1;
        }

        // Schemas are optional for the trigger

        // Triggers are considered equal if all non-null properties are the same.

        if ( $this->name != $cmp->name
             || $this->time != $cmp->time
             || $this->event != $cmp->event
             || $this->table != $cmp->table
             || $this->body != $cmp->body ) {
            return -1;
        }

        // The following properties have a default set by the database. If the property is not specified
        // a value will be provided when the database information schema is queried.

        if ( ( null !== $this->definer && null !== $cmp->definer ) && $this->definer != $cmp->definer ) {
            return -2;
        }

        // The following properties do not have defaults set by the database and should be considered if
        // one of them is set.

        if ( ( null !== $this->schema || null !== $cmp->schema ) && $this->schema != $cmp->schema ) {
            return -3;
        }

    }  // compare()

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::getSql()
     * ------------------------------------------------------------------------------------------
     */

    public function getSql($includeSchema = false)
    {
        // Triggers queried from MySQL contain the begin/end but the body in the JSON may or may not.

        $addBeginEnd = ( 0 !== strpos($this->body, "BEGIN") );
        $name = ( $includeSchema ? $this->getFullName() : $this->getName(true) );
        $tableName = ( null !== $this->schema && $includeSchema ? $this->quote($this->schema) . "." : "" ) .
            $this->quote($this->table);
        $parts = array();
        $parts[] = "CREATE";
        if ( null !== $this->definer ) {
            $parts[] = "DEFINER = " . $this->definer;
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

    }  // getSql()
}  // class Trigger
