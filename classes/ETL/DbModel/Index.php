<?php
/* ==========================================================================================
 * Class for managing table indexes in the data warehouse.  This is meant to be used as a
 * component of Table.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-10-29
 *
 * @see Table
 * @see iEntity
 * ==========================================================================================
 */

namespace ETL\DbModel;

use Log;
use stdClass;

class Index extends NamedEntity implements iEntity
{
    // Properties required by this class. These will be merged with other required
    // properties up the call chain. See @Entity::$requiredProperties
    private $localRequiredProperties = array(
        'columns'
    );

    // Properties provided by this class. These will be merged with other properties up
    // the call chain. See @Entity::$properties
    private $localProperties = array(
        'columns'   => array(),
        'type'      => null,
        'is_unique' => null
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
     * @see aNamedEntity::initialize()
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(stdClass $config)
    {
        // Local verifications

        if ( ! isset($config->name) ) {
            $config->name = $this->generateIndexName($config->columns);
        }

        parent::initialize($config);

    }  // initialize()

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
                // Normalize property values to lowercase to match MySQL behavior. PRIMARY keys are
                // represented in information schema as indexes named PRIMARY.
                if ( 'PRIMARY' == strtoupper($value) ) {
                    $value = 'PRIMARY';
                } else {
                    $value = strtolower($value);
                }
                break;

            case 'is_unique':
                $origValue = $value;
                $value = \xd_utilities\filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ( null === $value ) {
                    $this->logAndThrowException(
                        sprintf("%s must be a boolean, '%s' given", $property, gettype($origValue))
                    );
                }
                break;

            case 'columns':
                if ( ! is_array($value) ) {
                    $this->logAndThrowException(
                        sprintf("%s must be an array, '%s' given", $property, gettype($value))
                    );
                } elseif ( 0 == count($value) ) {
                    $this->logAndThrowException(
                        sprintf("%s must be an non-empty array", $property)
                    );
                }

                // Normalize property values to lowercase to match MySQL behavior

                $normalizedValues = array();
                foreach ( $value as $column ) {
                    $normalizedValues[] = strtolower($column);
                }
                $value = $normalizedValues;
                break;

            case 'type':
                if ( ! is_string($value) ) {
                    $this->logAndThrowException(
                        sprintf("%s name must be a string, '%s' given", $property, gettype($value))
                    );
                }
                // Normalize property values to lowercase to match MySQL behavior
                $value = strtoupper($value);
                break;

            default:
                break;
        }  // switch ( $property )

        return $value;

    }  // filterAndVerifyValue()

    /* ------------------------------------------------------------------------------------------
     * Auto-generate an index name based on the columns included in the index. If the length of the
     * index name would be too large use a hash.
     *
     * @param $columns The array of index column names
     *
     * @return The generated index name
     * ------------------------------------------------------------------------------------------
     */

    private function generateIndexName(array $columns)
    {
        $str = implode("_", $columns);
        $name = ( strlen($str) <= 32 ? $str : md5($str) );
        return "index_" . $name;
    }  // generateIndexName()

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::compare()
     * ------------------------------------------------------------------------------------------
     */

    public function compare(iEntity $cmp)
    {
        if ( ! $cmp instanceof Index ) {
            return 1;
        }

        if ( ($retval = parent::compare($cmp)) != 0 ) {
            return $retval;
        }

        // Indexes are considered equal if all non-null properties are the same but only the name and
        // columns are required. If the type and uniqueness are provided use those in the comparison
        // as well.

        if ( $this->columns != $cmp->columns ) {
            $this->logCompareFailure('columns', implode(',', $this->columns), implode(',', $cmp->columns), $this->name);
            return -1;
        }

        // The following properties have a default set by the database. If the property is not specified
        // a value will be provided when the database information schema is queried.

        if ( ( null !== $this->type && null !== $cmp->type ) && $this->type != $cmp->type ) {
            $this->logCompareFailure('type', $this->type, $cmp->type, $this->name);
            return -1;
        }

        // The following properties do not have defaults set by the database and should be considered if
        // one of them is set.

        // By default a primary key in MySQL has the name PRIMARY and is unique

        if (
            'PRIMARY' != $this->name && 'PRIMARY' != $cmp->name
            && ( null !== $this->is_unique && null !== $cmp->is_unique )
            && $this->is_unique != $cmp->is_unique
        ) {
            $this->logCompareFailure(
                'is_unique',
                ($this->is_unique ? 'true' : 'false'),
                ($cmp->is_unique ? 'true' : 'false'),
                $this->name
            );
            return -1;
        }

        return 0;

    }  // compare()

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::getSql()
     * ------------------------------------------------------------------------------------------
     */

    public function getSql($includeSchema = false)
    {
        // Primary keys always have an index name of "PRIMARY"
        // See https://dev.mysql.com/doc/refman/5.7/en/create-table.html

        // Indexes may be created or altered in different ways (CREATE TABLE vs. ALTER TABLE) so we only
        // return the essentials of the definition and let the Table class figure out the appropriate
        // way to put them together.

        $parts = array();
        if ( null !== $this->name && "PRIMARY" == $this->name ) {
            $parts[] = "PRIMARY KEY";
        } else {
            $parts[] = ( null !== $this->is_unique && $this->is_unique ? "UNIQUE ": "" )
                . "INDEX "
                . $this->getName(true);
        }

        if ( null !== $this->type ) {
            $parts[] = "USING " . $this->type;
        }

        $parts[] = "(" . implode(", ", array_map(array($this, 'quote'), $this->columns)) . ")";

        return implode(" ", $parts);

    }  // getSql()
}  // class Index
