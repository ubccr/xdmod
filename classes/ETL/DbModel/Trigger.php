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

                // Normalize property values to lowercase to match MySQL behavior
                if ( in_array($property, array('time', 'event')) ) {
                    $value = strtoupper($value);
                } elseif ( 'body' == $property && 0 !== stripos($value, "BEGIN") ) {
                    $value = sprintf("BEGIN\n%s\nEND", $value);
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

        if ( ($retval = parent::compare($cmp)) != 0 ) {
            return $retval;
        }

        // Triggers are considered equal if all non-null properties are the same.

        if ( $this->time != $cmp->time ) {
            $this->logCompareFailure('time', $this->time, $cmp->time, $this->name);
            return -1;
        } elseif ( $this->event != $cmp->event ) {
            $this->logCompareFailure('event', $this->event, $cmp->event, $this->name);
            return -1;
        } elseif ( $this->table != $cmp->table ) {
            $this->logCompareFailure('table', $this->table, $cmp->table, $this->name);
            return -1;
        } elseif ( $this->body != $cmp->body ) {
            $this->logCompareFailure('body', $this->body, $cmp->body, $this->name);
            return -1;
        }

        // The following properties have a default set by the database. If the property is not specified
        // a value will be provided when the database information schema is queried.

        if ( ( null !== $this->definer && null !== $cmp->definer ) && $this->definer != $cmp->definer ) {
            $this->logCompareFailure('definer', $this->definer, $cmp->definer, $this->name);
            return -1;
        }

    }  // compare()

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::getSql()
     * ------------------------------------------------------------------------------------------
     */

    public function getSql($includeSchema = false)
    {
        // Triggers queried from MySQL contain the begin/end but the body in the JSON may or may not.

        // $addBeginEnd = ( 0 !== stripos($this->body, "BEGIN") );
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
        $parts[] = $this->body;

        return implode(" ", $parts);

    }  // getSql()
}  // class Trigger
