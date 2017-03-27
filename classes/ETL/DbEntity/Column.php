<?php
/* ==========================================================================================
 * Class for managing table columns in the data warehouse.  This is meant to be used as a component
 * of Table.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-10-29
 *
 * @see Table
 * @see iTableItem
 * ==========================================================================================
 */

namespace ETL\DbEntity;

use \Log;
use \stdClass;

class Column extends aNamedEntity implements iTableItem
{
    // Column type (free-form string)
    private $type = null;

    // true if the column is nullable
    private $nullable = null;

    // Column default
    private $default = null;

    // Column extra (see http://dev.mysql.com/doc/refman/5.7/en/create-table.html)
    private $extra = null;

    // Column comment
    private $comment = null;

    // Column hints objecct
    private $hints = null;

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($config, $systemQuoteChar = null, Log $logger = null)
    {
        parent::__construct($systemQuoteChar, $logger);

        if ( ! is_object($config) ) {
            $msg = __CLASS__ . ": Column definition must be an object";
            $this->logAndThrowException($msg);
        }

        $requiredKeys = array("name", "type");
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

        foreach ( $config as $property => $value ) {
            if ( $this->isComment($property) ) {
                continue;
            }

            if ( ! property_exists($this, $property) ) {
                $msg = "Property '$property' in config is not supported";
                $this->logAndThrowException($msg);
            }

            $this->$property = $this->filterValue($property, $value);
        }  // foreach ( $config as $property => $value )

        $this->initialized = true;

    }  // initialize()

    /* ------------------------------------------------------------------------------------------
     * Filter values based on the property. Some properties are true/false but may be specified as
     * true, null, or YES depending on the input source. Other properties may be empty strings when
     * discovered from the database which should be treated as null for our purposes
     *
     * @param $property The property we are filtering
     * @param $value The value of the property as presented from the source (array, object, database)
     *
     * @return The filtered value
     * ------------------------------------------------------------------------------------------
     */

    private function filterValue($property, $value)
    {
        switch ( $property ) {

            case 'nullable':
                // The config files use "NULL" and "NOT NULL" but the MySQL information schema uses "YES" and
                // "NO"
                $tmp = strtolower($value);
                $tmp = ( "null" == $tmp ? true : $tmp );
                $value = \xd_utilities\filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                break;

            case 'extra':
            case 'comment':
                // If these values are empty they are considered null
                $value = ( empty($value) ? null : $value );
                break;

            case 'type':
                // MySQL stores the column type in lowercase
                $value = strtolower($value);
                break;

            default:
                break;
        }  // switch ( $property )

        return $value;
    }  // filterValue()

    /* ------------------------------------------------------------------------------------------
     * @return The type of this column
     * ------------------------------------------------------------------------------------------
     */

    public function getType()
    {
        return $this->type;
    }  // getType()

    /* ------------------------------------------------------------------------------------------
     * @return true if this column is nullable, false if it is not, or null if the property is not
     *   set.
     * ------------------------------------------------------------------------------------------
     */

    public function isNullable()
    {
        return $this->nullable;
    }  // isNullable()

    /* ------------------------------------------------------------------------------------------
     * @return The default value for this column, or null if the property is not set.
     * ------------------------------------------------------------------------------------------
     */

    public function getDefault()
    {
        return $this->default;
    }  // getDefault()

    /* ------------------------------------------------------------------------------------------
     * @return The "extra" value for this column, or null if the property is not set.
     * ------------------------------------------------------------------------------------------
     */

    public function getExtra()
    {
        return $this->extra;
    }  // getExtra()

    /* ------------------------------------------------------------------------------------------
     * @return The comment for this column, or null if the property is not set.
     * ------------------------------------------------------------------------------------------
     */

    public function getComment()
    {
        return $this->comment;
    }  // getComment()

    /* ------------------------------------------------------------------------------------------
     * @return The hints for this column, or null if the property is not set.
     * ------------------------------------------------------------------------------------------
     */

    public function getHints()
    {
        return $this->hints;
    }  // getHints()

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::compare()
     * ------------------------------------------------------------------------------------------
     */

    public function compare(iTableItem $cmp)
    {
        if ( ! $cmp instanceof Column ) {
            return 1;
        }

        // Columns are considered equal if all non-null properties in both Columns are the same. The
        // name and type are required but if other properties are set use those in the comparison as
        // well.
        //
        // Note that the "enum" type will be handled in a special case below so only match types here
        // that are different and are not both enumerated.

        if ( $this->getName() != $cmp->getName()
             ||
             ( $this->getType() != $cmp->getType()
               && ! (0 === strpos($this->getType(), 'enum')
                     && 0 === strpos($cmp->getType(), 'enum'))
             )
           ) {
            return -1;
        }

        // Timestamp fields have special handling for default and extra fields.
        // See https://dev.mysql.com/doc/refman/5.5/en/timestamp-initialization.html

        if ( "timestamp" == $this->getType() ) {

            // In the mode where a config file is compared to an existing database, the source is
            // considered the configuration while the destination is the database.

            $srcDefault = $this->getDefault();
            $destDefault = $cmp->getDefault();
            $srcExtra = $this->getExtra();
            $destExtra = $cmp->getExtra();

            // MySQL considers the following equivalent to CURRENT_TIMESTAMP and will convert them
            // automatically. Map them now so we don't get into an endless ALTER TABLE loop.

            if ( null !== $srcExtra) {
                $search = array(
                    "CURRENT_TIMESTAMP()",
                    "NOW()",
                    "LOCALTIME",
                    "LOCALTIME()"
                );
                $srcExtra = str_ireplace($search, "CURRENT_TIMESTAMP", $srcExtra);
            }  // if ( null !== $srcExtra)

            // If no DEFAULT and no EXTRA is provided, MySQL will use:
            // DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            //
            // If no DEFAULT is provided but an EXTRA is provided the default will be 0 unless
            // the collumn is nullable, then it will be NULL.

            if ( ( (null === $srcDefault && null === $srcExtra)
                   || ('current_timestamp' === strtolower($srcDefault) && 'on update current_timestamp' === strtolower($srcExtra)) )
                 && ('current_timestamp' != strtolower($destDefault) || null === $destExtra) )
            {
                return -1;
            }

            // With a DEFAULT clause but no ON UPDATE CURRENT_TIMESTAMP clause, the column has the
            // given default value and is not automatically updated to the current timestamp.
            // Note that valid defaults are 0 or a datetime format and MySQL will translate 0 to
            // '0000-00-00 00:00:00' and will add '00:00:00' to date strings.

            if ( (null !== $srcDefault && null === $srcExtra) ) {
                if ( null !== $destExtra ) {
                    // Extra has changed (destination has a value)
                    return -1;
                } elseif ( null === $destDefault ) {
                    // Default has changed from NULL to something
                    return -1;
                } elseif ( strtolower($srcDefault) != strtolower($destDefault)
                            && ( ("0" == "$srcDefault" && '0000-00-00 00:00:00' != $destDefault)
                                 || ("0" != "$srcDefault" && $srcDefault . ' 00:00:00' != $destDefault) ) )
                {
                    // Note the casting of 0 to "0" above is necessary because php considers 0 == "0000-01-01" to be true.
                    // Default has changed
                    return -1;
                }
            }

            // With an ON UPDATE CURRENT_TIMESTAMP clause and a constant DEFAULT clause, the column
            // is automatically updated to the current timestamp and has the given constant default
            // value.

            if ( null !== $srcDefault && null !== $srcExtra ) {
                if ( strtolower($srcExtra) != strtolower($destExtra) ) {
                    return -1;
                } elseif ( strtolower($srcDefault) != strtolower($destDefault)
                            && ( ("0" == "$srcDefault" && '0000-00-00 00:00:00' != $destDefault)
                                 || ("0" != "$srcDefault" && $srcDefault . ' 00:00:00' != $destDefault)) )
                {
                    // Note the casting of 0 to "0" above is necessary because php considers 0 == "0000-01-01" to be true.
                    return -1;
                }
            }

            // With an ON UPDATE CURRENT_TIMESTAMP clause but no DEFAULT clause, the column is
            // automatically updated to the current timestamp. The default is 0 unless the column is
            // defined with the NULL attribute, in which case the default is NULL.

            if ( null === $srcDefault && null !== $srcExtra ) {
                if ( null === $destExtra
                     || strtolower($srcExtra) != strtolower($destExtra)
                     || (null !== $destDefault && '0000-00-00 00:00:00' != $destDefault) )
                {
                    return -1;
                }
            }

        } else {
            // The following properties do not have defaults set by the database and should be considered if
            // one of them is set.
            if ( ( null !== $this->getDefault() || null !== $cmp->getDefault() )
                 && $this->getDefault() != $cmp->getDefault() ) {
                return -1;
            }

            if ( ( null !== $this->getExtra() || null !== $cmp->getExtra() )
                 && $this->getExtra() != $cmp->getExtra() ) {
                return -1;
            }
        } // else ( "timestamp" == $this->getType() )

        // The enum type may be formatted by the database to add spaces between parameter
        // values. Normalize the values before comparing.

        if ( 0 === ($myStartPos = strpos($this->getType(), 'enum'))
             && 0 === ($cmpStartPos = strpos($cmp->getType(), 'enum')) ) {
            // Extract the enum value list and normalize it to include no spaces between values
            $myType = substr($this->getType(), 4);
            $myType = implode(',', preg_split('/\s*,\s*/', trim($myType, "() \t\n\r\0\x0B")));
            $cmpType = substr($cmp->getType(), 4);
            $cmpType = implode(',', preg_split('/\s*,\s*/', trim($cmpType, "() \t\n\r\0\x0B")));
            if ( $myType != $cmpType ) {
                return -1;
            }
        }

        // The following properties have a default set by the database. If the property is not specified
        // a value will be provided when the database information schema is queried.

        if ( ( null !== $this->isNullable() && null !== $cmp->isNullable() )
             && $this->isNullable() != $cmp->isNullable() ) {
            return -1;
        }

        // The following properties do not have defaults set by the database and should be considered if
        // one of them is set.

        if ( ( null !== $this->getComment() || null !== $cmp->getComment() )
             && $this->getComment() != $cmp->getComment() ) {
            return -1;
        }

        return 0;

    }  // compare()

    /* ------------------------------------------------------------------------------------------
     * @see iTableItem::getCreateSql()
     * ------------------------------------------------------------------------------------------
     */

    public function getCreateSql($includeSchema = false)
    {
        // Name and type are required. null values are treated as not provided/specified.

        // Columns may be created or altered in different ways (CREATE TABLE vs. ALTER TABLE) so we only
        // return the essentials of the definition and let the Table class figure out the appropriate
        // way to put them together.

        $parts = array();
        $parts[] = $this->getName(true);
        $parts[] = $this->type;
        if ( null !== $this->nullable ) {
            $parts[] = ( false === $this->nullable ? "NOT NULL" : "NULL" );
        }

        // Specifying defaults can get tricky.  If the default is non-null:
        //  - If the field is nullable and default is the string "NULL" leave it as is ( "NULL" -> DEFAULT NULL)
        //  - If the field is a timestamp do not quote CURRENT_TIMESTAMP or 0
        //  - If a numeric value leave as is (5 -> DEFAULT 5)
        //  - If a boolean value use boolean constant (false -> DEFAULT FALSE)
        //  - If it is a mysql bit or hex literal leave it as is(b'1' -> DEFAULT b'1')
        //  - If a string value ensure it is quoted ("Bob", "'Bob'" -> DEFAULT 'Bob')
        //
        // - Note that in MySQL a nullable column with no default is considered a default of NULL.
        // - Note that MySQL assigns TIMESTAMP columns a default of CURRENT_TIMESTAMP with an extra field of "on update CURRENT_TIMESTAMP"

        if ( null !== $this->default ) {

            if ( ($this->nullable && "NULL" == $this->default)  ||
                 ( "timestamp" == $this->type && (is_numeric($this->default) || 'current_timestamp' == strtolower($this->default)) ) ||
                 is_numeric($this->default) ||
                 "b'" == substr($this->default, 0, 2) ||
                 "x'" == substr($this->default, 0, 2) ||
                 "X'" == substr($this->default, 0, 2) ) {
                $parts[] = "DEFAULT " . $this->default;
            } elseif ( ($this->nullable && null === $this->default) ) {
                $parts[] = "DEFAULT NULL";
            } elseif (is_bool($this->default)) {
                $parts[] = "DEFAULT " . ($this->default ? "TRUE" : "FALSE");
            } elseif ( "'" == substr($this->default, 0, 1) && "'" == substr($this->default, -1) ) {
                $parts[] = "DEFAULT " . addslashes($this->default);
            }
            else {
                $parts[] = "DEFAULT '" . addslashes($this->default) . "'";
            }

        }  // if ( null !== $this->default )

        if ( null !== $this->extra ) {
            $parts[] = $this->extra;
        }
        if ( null !== $this->comment ) {
            $parts[] =  "COMMENT '" . addslashes($this->comment) . "'";
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
        if ( $succinct ) {

            $data = array($this->name, $this->type);
            if ( null !== $this->nullable ) {
                $data[] = ( $this->nullable ? "null" : "not null" );
            }
            if ( null !== $this->default ) {
                $data[] = $this->default;
            }
            if ( null !== $this->extra) {
                $data[] = $this->extra;
            }
            if ( null !== $this->comment) {
                $data[] = $this->comment;
            }

        } else {

            $data = new stdClass;
            $data->name = $this->name;
            $data->type = $this->type;
            if ( null !== $this->nullable ) {
                $data->nullable = $this->nullable;
            }
            if ( null !== $this->default ) {
                $data->default = $this->default;
            }
            if ( null !== $this->extra) {
                $data->extra = $this->extra;
            }
            if ( null !== $this->comment) {
                $data->comment = $this->comment;
            }
        }

        return $data;

    }  // toJsonObj()
}  // class Column
