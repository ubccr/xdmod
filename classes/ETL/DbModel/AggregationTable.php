<?php
/* ==========================================================================================
 * Class for managing aggregation tables in the data warehouse.  An aggregation table
 * combines a Table with a query used to populate the table. The table name is generated
 * using the table prefix specified in the table definition along with an aggregation unit
 * specified during the aggregation process.
 *
 * @see Table
 * @see Query
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-29
 * ==========================================================================================
 */

namespace ETL\DbModel;

use ETL\Utilities;
use Log;
use stdClass;

class AggregationTable extends Table
{
    // Properties required by this class. These will be merged with other required
    // properties up the call chain. See @Entity::$requiredProperties
    private $localRequiredProperties = array(
        'table_prefix'
    );

    // Properties provided by this class. These will be merged with other properties up
    // the call chain. See @Entity::$properties
    private $localProperties = array(
        // Current aggregation unit to use when generating the SQL to populate the table
        'aggregation_unit'  => null,

        // Table prefix used to generate the name along with the aggregation unit
        'table_prefix'   => null,

        // Query object for populating the table with data
        'query'  => null
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
     * @see aNamedEntity::initialize()
     * ------------------------------------------------------------------------------------------
     */

    public function initialize(stdClass $config)
    {
        // Aggregation table definition files may include the query used to populate the
        // aggregation table.

        if ( isset($config->source_query) ) {
            $this->query = $config->source_query;
            unset($config->source_query);
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
            case 'query':
                if ( ! is_object($value) ) {
                    $this->logAndThrowException(
                        sprintf("%s name must be an object, '%s' given", $property, gettype($value))
                    );
                }
                break;

            case 'aggregation_unit':
            case 'table_prefix':
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
     * Aggregation tables support variables (e.g., ${AGGREGATION_UNIT) as defined by the aggregation
     * machinery) in the column, index, and trigger definitions. These variables must be replaced with
     * values prior to executing DDL statements and prior to comparing tables for generation of ALTER
     * TABLE statements (e.g., prior to calling aDatabaseDestinationAction::manateTable().
     *
     * Create a copy of the current table and perform variable substitution on all column, index, and
     * trigger definitions.
     *
     * @return A copy (clone) of this table with variable substiution performed on the column, index,
     *   and trigger definition fields.
     * ------------------------------------------------------------------------------------------
     */

    public function copyAndApplyVariables(array $variableMap)
    {
        // Save the JSON representation for columns, indexes, triggers

        $columnJson = array();
        $indexJson = array();
        $triggerJson = array();

        foreach ( $this->columns as $column ) {
            $columnJson[] = $column->toStdClass();
        }
        foreach ( $this->indexes as $index ) {
            $indexJson[] = $index->toStdClass();
        }
        foreach ( $this->triggers as $trigger ) {
            $triggerJson[] = $trigger->toStdClass();
        }

        // Clone this object and clear the existing columns, indexes, and triggers. Add them using the
        // saved definitions after substitutions have been performed.

        $newTable = clone $this;
        // We can't set columns to null because it is a required column
        $newTable->columns = array();
        $newTable->indexes = array();
        $newTable->triggers = array();

        foreach ( $columnJson as $def ) {
            if ( null !== $variableMap ) {
                foreach ( $def as $key => &$value ) {
                    $value = Utilities::substituteVariables($value, $variableMap);
                }
                unset($value); // Sever the reference with the last element
            }

            // Add the column, allowing duplicate column names to overwrite previous values. Without
            // overwrite turned on, the yearly aggregation tables with throw an exception and log a
            // warning for the year_id column.

            $newTable->addColumn($def, true);

        }

        foreach ( $indexJson as $def ) {
            if ( null !== $variableMap ) {
                foreach ( $def as $key => &$value ) {
                    $value = Utilities::substituteVariables($value, $variableMap);
                }
                unset($value); // Sever the reference with the last element
            }
            $newTable->addIndex($def);
        }

        foreach ( $triggerJson as $def ) {
            if ( null !== $variableMap ) {
                foreach ( $def as $key => &$value ) {
                    $value = Utilities::substituteVariables($value, $variableMap);
                }
                unset($value); // Sever the reference with the last element
            }
            $newTable->addTrigger($def);
        }

        return $newTable;

    }  // copyAndApplyVariables()

    /* ------------------------------------------------------------------------------------------
     * @see Entity::__set()
     * ------------------------------------------------------------------------------------------
     */

    public function __set($property, $value)
    {
        // If we are not setting a property that is a special case, just call the main setter
        $specialCaseProperties = array('aggregation_unit', 'query');

        if ( ! in_array($property, $specialCaseProperties) ) {
            parent::__set($property, $value);
            return;
        }

        // Verify values prior to doing anything with them

        $value = $this->filterAndVerifyValue($property, $value);

        // Handle special cases.

        switch ($property) {
            case 'aggregation_unit':
                parent::__set($property, $value);
                // When setting the aggregation unit, update the table name to include it
                $this->name = $this->table_prefix . $value;
                break;

            case 'query':
                $this->properties[$property] = null;
                if ( null !== $value ) {
                    $query = ( is_object($value) && $value instanceof Query
                               ? $value
                               : new Query($value, $this->systemQuoteChar, $this->logger) );
                    $this->properties[$property] = $query;
                }
                break;

            default:
                break;
        }  // switch($property)

    }  // __set()
}  // class AggregationTable
