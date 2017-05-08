<?php
/* ==========================================================================================
 * Class for managing table join clauses in the data warehouse.  This is meant to be used
 * as a component of Query.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-28
 *
 * @see Query
 * @see iEntity
 * ==========================================================================================
 */

namespace ETL\DbModel;

use Log;

class Join extends SchemaEntity implements iEntity
{
    // Properties required by this class. These will be merged with other required
    // properties up the call chain. See @Entity::$requiredProperties
    private $localRequiredProperties = array();

    // Properties provided by this class. These will be merged with other properties up
    // the call chain. See @Entity::$properties
    private $localProperties = array(
        // Join type (e.g., "LEFT OUTER")
        'type'  => null,
        // Alias for the joined table
        'alias' => null,
        // Join ON clause (not needed for FROM)
        'on'    => null
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
     * @see Entity::filterAndVerifyValueValue()
     * ------------------------------------------------------------------------------------------
     */

    protected function filterAndVerifyValue($property, $value)
    {
        $value = parent::filterAndVerifyValue($property, $value);

        if ( null === $value ) {
            return $value;
        }

        switch ( $property ) {
            case 'type':
            case 'alias':
            case 'on':
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
        if ( ! $cmp instanceof Join ) {
            return 1;
        }

        return ( $this == $cmp );

    }  // compare()

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::getSql()
     * ------------------------------------------------------------------------------------------
     */

    public function getSql($includeSchema = false)
    {
        $parts = array();

        // Allow subqueries to be included and not quoted
        $quoteName = ( 0 !== strpos($this->name, '(') );

        $parts[] = ( null !== $this->schema && $includeSchema ? $this->getFullName() : $this->getName($quoteName) );
        if ( null !== $this->alias ) {
            $parts[] = "AS " . $this->alias;
        }
        if ( null !== $this->on ) {
            $parts[] = "ON " . $this->on;
        }

        return implode(" ", $parts);

    }  // getSql()
}  // class Join
