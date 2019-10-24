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
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;

class NamedEntity extends Entity
{
    // Properties required by this class. These will be merged with other required
    // properties up the call chain. See @Entity::$requiredProperties
    private $localRequiredProperties = array(
        'name'
    );

    // Properties provided by this class. These will be merged with other properties up
    // the call chain. See @Entity::$properties
    private $localProperties = array(
        'name' => null
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

            case 'name':
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
    * This is a convienece metod to return the model name, optionally quoted, rather than
    * using $this->quote($obj->name).
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
     * @see iEntity::compare()
     * ------------------------------------------------------------------------------------------
     */

    public function compare(iEntity $cmp)
    {
        if ( ! $cmp instanceof NamedEntity ) {
            return 1;
        }

        if ( $this->name != $cmp->name ) {
            $this->logCompareFailure('name', $this->name, $cmp->name);
            return -1;
        }

        return 0;
    }

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::__toString()
     * ------------------------------------------------------------------------------------------
     */

    public function __toString()
    {
        return sprintf('%s (%s)', $this->name, get_class($this));
    }  // __toString()
}  // class NamedEntity
