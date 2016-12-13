<?php
/* ==========================================================================================
 * Class for managing aggregation tables in the data warehouse.  An aggregation table combines a
 * Table with a query used to populate the table. The table name is generated using the table prefix
 * specified in the table definition along with an aggregation unit specified during the aggregation
 * process.
 *
 * @see Table
 * @see Query
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-11-20
 * ==========================================================================================
 */

namespace ETL\DbEntity;

use ETL\Utilities;
use \Exception;
use \Log;

class AggregationTable extends Table
{
    // Aggregation unit to use when generating the SQL to populate the table
    protected $aggregationUnit = null;

    // Table prefix used to generate the name along with the aggregation unit
    protected $tablePrefix = null;

    // Query object for populating the table
    protected $query = null;

    /* ------------------------------------------------------------------------------------------
     * Construct a table object from a JSON definition file or a definition object. The definition
     * must contain, at a minimum, name and columns properties.
     *
     * @param $filename A filename for the JSON definition file
     * or
     * @param $definitionObject An object containing the table definition
     *
     * Optional 2nd and 3rd arguments:
     *
     * @param $variableMap An associative array specifying variables that will be substituted in the
     *   table DDL and the aggregation SELECT statement
     * @param $macroDir The directory where macro files are found.
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($config, $systemQuoteChar = null, Log $logger = null)
    {
        parent::__construct($config, $systemQuoteChar, $logger);

        $this->setTablePrefix($config->name);
     
        if ( isset($this->config->query) ) {
            $this->query = new Query($config->query);
        }

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Return the table name.  This combines the prefix and aggregation unit and is set when the
     * aggregation unit is applied.
     *
     * @param $quote true to wrap the name in quotes to handle special characters
     *
     * @return The name of this table, optionally quoted
     *
     * @throw Exception if the aggregation unit has not been set
     * ------------------------------------------------------------------------------------------
     */

    public function getName($quote = false)
    {
        if ( null === $this->aggregationUnit ) {
            $msg = "Aggregation unit must be set to generate table name";
            $this->logAndThrowException($msg);
        }

        return parent::getName($quote);
    
    }  // getName()

    /* ------------------------------------------------------------------------------------------
     * @return The query object used to populate the aggregation table.
     * ------------------------------------------------------------------------------------------
     */

    public function getQuery()
    {
        return $this->query;
    }  // getQuery()

    /* ------------------------------------------------------------------------------------------
     * Set the table prefix used to generate the name of the aggregation table. This is typically
     * combined with the aggregation unit.
     *
     * @param $tablePrefix The table prefix to use when generating the table name.
     *
     * @return This object to support method chaining.
     *
     * @throws Exception if the table prefix is empty or null
     * ------------------------------------------------------------------------------------------
     */

    public function setTablePrefix($tablePrefix)
    {
        if ( empty($tablePrefix) ) {
            $msg = "Table prefix unit cannot be empty or null";
            $this->logAndThrowException($msg);
        }

        $this->tablePrefix = $tablePrefix;
        return $this;
    }  // setTablePrefix()

    /* ------------------------------------------------------------------------------------------
     * @return The table prefix
     * ------------------------------------------------------------------------------------------
     */

    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }  // getTablePrefix()

    /* ------------------------------------------------------------------------------------------
     * Set the aggregation unit used to generate the name of the aggregation table. This is typically
     * combined with the table prefix.
     *
     * @param $aggregationUnit The aggregation unit to use when generating the table name and SQL
     *
     * @return This object to support method chaining.
     *
     * @throws Exception if the table prefix is empty or null
     * ------------------------------------------------------------------------------------------
     */

    public function setAggregationUnit($aggregationUnit)
    {
        if ( empty($aggregationUnit) ) {
            $msg = "Aggregation unit cannot be empty or null";
            $this->logAndThrowException($msg);
        }
    
        $this->aggregationUnit = $aggregationUnit;

        // Update the table name with the prefix + aggregation unit
    
        $this->name = $this->getTablePrefix() . $aggregationUnit;
    
        return $this;
    }  // setAggregationUnit()

    /* ------------------------------------------------------------------------------------------
     * @return The aggregation unit
     * ------------------------------------------------------------------------------------------
     */

    public function getAggregationUnit()
    {
        return $this->aggregationUnit;
    }  // getAggregationUnit()

    /* ------------------------------------------------------------------------------------------
     * Generate an object representation of this item suitable for encoding into JSON.
     *
     * @param $succinct true to use a succinct representation.
     * @param $includeSchema true to include the schema in the table definition
     *
     * @return An object representation for this item suitable for encoding into JSON.
     * ------------------------------------------------------------------------------------------
     */

    public function toJsonObj($succinct = false, $includeSchema = false)
    {
        $data = parent::toJsonObj($succinct, $includeSchema);

        $data->query = $this->query->toJsonObj($succinct, $includeSchema);
    
        return $data;

    }  // toJsonObj()

    /* ------------------------------------------------------------------------------------------
     * Generate a JSON representation of this table.
     *
     * @param $succinct true if a succinct representation should be returned.
     * @param $includeSchema true to include the schema in the table definition
     *
     * @return A JSON formatted string representing the tabe.
     * ------------------------------------------------------------------------------------------
     */

    public function toJson($succinct = false, $includeSchema = false)
    {
        return json_encode($this->toJsonObj($succinct, $includeSchema));
    }  // toJson()

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
            $columnJson[] = $column->toJsonObj();
        }
        foreach ( $this->indexes as $index ) {
            $indexJson[] = $index->toJsonObj();
        }
        foreach ( $this->triggers as $trigger ) {
            $triggerJson[] = $trigger->toJsonObj();
        }

        // Clone this object and clear the existing columns, indexes, and triggers. Add them using the
        // saved definitions after substitutions have been performed.

        $newTable = clone $this;
        $newTable->deleteColumns()->deleteIndexes()->deleteTriggers();

        foreach ( $columnJson as $def ) {
            foreach ( $def as $key => &$value ) {
                if ( null !== $variableMap ) {
                    $value = Utilities::substituteVariables($value, $variableMap);
                }
            }
            unset($value); // Sever the reference with the last element

            // Add the column, allowing duplicate column names to overwrite previous values. Without
            // overwrite turned on, the yearly aggregation tables with throw an exception and log a
            // warning for the year_id column.

            $newTable->addColumn($def, true);

        }

        foreach ( $indexJson as $def ) {
            foreach ( $def as $key => &$value ) {
                if ( null !== $variableMap ) {
                    $value = Utilities::substituteVariables($value, $variableMap);
                }
            }
            unset($value); // Sever the reference with the last element
            $newTable->addIndex($def);
        }

        foreach ( $triggerJson as $def ) {
            foreach ( $def as $key => &$value ) {
                if ( null !== $variableMap ) {
                    $value = Utilities::substituteVariables($value, $variableMap);
                }
            }
            unset($value); // Sever the reference with the last element
            $newTable->addTrigger($def);
        }

        return $newTable;

    }  // copyAndApplyVariables()

}  // class AggregationTable 
