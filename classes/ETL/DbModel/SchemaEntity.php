<?php
/* ==========================================================================================
 * Lightweight database model providing support for a set of pre-defined (property, value)
 * pairs and quoting.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-27
 * ==========================================================================================
 */

namespace ETL\DbModel;

use Log;
use CCR\Loggable;
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;

class SchemaEntity extends NamedEntity
{
    // Properties required by this class. These will be merged with other required
    // properties up the call chain. See @Entity::$requiredProperties
    private $localRequiredProperties = array();

    // Properties provided by this class. These will be merged with other properties up
    // the call chain. See @Entity::$properties
    private $localProperties = array(
        'schema' => null
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
    }

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

            case 'schema':
                if ( ! is_string($value) ) {
                    $this->logAndThrowException(
                        sprintf("%s must be a string, '%s' given", $property, gettype($value))
                    );
                }
                break;

            default:
                break;
        }  // switch ( $property )

        return $value;

    }  // filterAndVerifyValue()

    /* ------------------------------------------------------------------------------------------
     * @param $quote TRUE if the schema and name should be quoted, defaults to TRUE.
     *
     * @return The fully qualified and quoted name including the schema, if one was set.
     * ------------------------------------------------------------------------------------------
     */

    public function getFullName($quote = true)
    {
        return ( null !== $this->schema ? $this->getSchema($quote) . "." : "" )
            . $this->getName($quote);
    }  // getFullName()

   /* ------------------------------------------------------------------------------------------
    * This is a convienece metod to return the model schema, optionally quoted, rather
    * than using $this->quote($obj->schema).
    *
    * @param $quote true to wrap the name in quotes to handle special characters
    *
    * @return The name of this table, optionally quoted with the schema
    * ------------------------------------------------------------------------------------------
    */

    public function getSchema($quote = false)
    {
        return ( $quote ? $this->quote($this->schema) : $this->schema );
    }  // getSchema()

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::compare()
     * ------------------------------------------------------------------------------------------
     */

    public function compare(iEntity $cmp)
    {

        if ( ! $cmp instanceof SchemaEntity ) {
            return 1;
        }

        if ( ($retval = parent::compare($cmp)) != 0 ) {
            return $retval;
        }

        // One or the other value may be null so ensure inequality with things like empty strings

        if ( $this->schema !== $cmp->schema ) {
            $this->logCompareFailure('schema', $this->schema, $cmp->schema, $this->name);
            return -1;
        }

        return 0;
    }
}  // class SchemaEntity
