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

namespace ETL\DbModel;

use \Log;
use \stdClass;

class Index extends aNamedEntity implements iTableItem
{
    private $type = null;
    private $is_unique = null;
    private $columns = array();

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($config, $systemQuoteChar = null, Log $logger = null)
    {
        parent::__construct($systemQuoteChar, $logger);

        if ( ! is_object($config) ) {
            $msg = __CLASS__ . ": Index definition must be an array or object";
            $this->logAndThrowException($msg);
        }

        $requiredKeys = array("columns");
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

        if ( ! is_array($config->columns) || 0 == count($config->columns) ) {
            $msg = "Index columns must be an non-empty array";
            $this->logAndThrowException($msg);
        }

        if ( ! isset($config->name) ) {
            $config->name = $this->generateIndexName($config->columns);
        }

        foreach ( $config as $property => $value ) {

            if ( ! property_exists($this, $property) ) {
                $msg = "Property '$property' in config is not supported";
                $this->logAndThrowException($msg);
            }

            $this->$property = $value;

        }  // foreach ( $config as $property => $value )

        $this->initialized = true;

    }  // initialize()

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
     * @return The list of column names for this index
     * ------------------------------------------------------------------------------------------
     */

    public function getColumnNames()
    {
        return $this->columns;
    }  // getColumnNames()

    /* ------------------------------------------------------------------------------------------
     * @return The index type, or null if no type was specified.
     * ------------------------------------------------------------------------------------------
     */

    public function getType()
    {
        return $this->type;
    }  // getType()

    /* ------------------------------------------------------------------------------------------
     * @return true if the index is unique, false if it is not, or null if not specified.
     * ------------------------------------------------------------------------------------------
     */

    public function isUnique()
    {
        return $this->is_unique;
    }  // isUnique()

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::compare()
     * ------------------------------------------------------------------------------------------
     */

    public function compare(iTableItem $cmp)
    {
        if ( ! $cmp instanceof Index ) {
            return 1;
        }

        // Indexes are considered equal if all non-null properties are the same but only the name and
        // columns are required but if the type and uniqueness are provided use those in the comparison
        // as well.

        if ( $this->getName() != $cmp->getName()
             || $this->getColumnNames() != $cmp->getColumnNames() ) {
            return -1;
        }

        // The following properties have a default set by the database. If the property is not specified
        // a value will be provided when the database information schema is queried.

        if ( ( null !== $this->getType() && null !== $cmp->getType() )
             && $this->getType() != $cmp->getType() ) {
            return -11;
        }

        // The following properties do not have defaults set by the database and should be considered if
        // one of them is set.

        // By default a primary key in MySQL has the name PRIMARY and is unique

        if ( "PRIMARY" != $this->getName() && "PRIMARY" != $cmp->getName()
             && ( null !== $this->isUnique() && null !== $cmp->isUnique() )
             && $this->isUnique() != $cmp->isUnique() ) {
            return -111;
        }

        return 0;

    }  // compare()

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::getCreateSql()
     * ------------------------------------------------------------------------------------------
     */

    public function getCreateSql($includeSchema = false)
    {
        // Primary keys always have an index name of "PRIMARY"
        // See https://dev.mysql.com/doc/refman/5.7/en/create-table.html

        // Indexes may be created or altered in different ways (CREATE TABLE vs. ALTER TABLE) so we only
        // return the essentials of the definition and let the Table class figure out the appropriate
        // way to put them together.

        $parts = array();
        $parts[] = (null !== $this->name && "PRIMARY" == $this->name
                    ? "PRIMARY KEY"
                    : ( null !== $this->is_unique && $this->is_unique ? "UNIQUE ": "") . "INDEX " . $this->getName(true) );
        if ( null !== $this->type ) {
            $parts[] = "USING {$this->type}";
        }
        $parts[] = "(" . implode(", ", array_map(array($this, 'quote'), $this->columns)) . ")";

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
        if ( $succinct ) {
            $data = $this->columns;
        } else {
            $data = new stdClass;
            $data->name = $this->name;
            $data->columns = $this->columns;
            if ( null !== $this->type ) {
                $data->type = $this->type;
            }
            if ( null !== $this->is_unique ) {
                $data->is_unique = ( 1 == $this->is_unique);
            }
        }

        return $data;

    }  // toJsonObj()
}  // class Index
