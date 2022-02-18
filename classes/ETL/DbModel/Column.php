<?php
/* ==========================================================================================
 * Class for managing table columns in the data warehouse.  This is meant to be used as a
 * component of Table.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-28
 *
 * @see Table
 * @see iEntity
 * ==========================================================================================
 */

namespace ETL\DbModel;

use Psr\Log\LoggerInterface;

class Column extends NamedEntity implements iEntity
{
    // Properties required by this class. These will be merged with other required
    // properties up the call chain. See @Entity::$requiredProperties
    private $localRequiredProperties = array(
        'type'
    );

    // Properties provided by this class. These will be merged with other properties up
    // the call chain. See @Entity::$properties
    private $localProperties = array(
        // Column type (free-form string)
        'type'     => null,
        // The column character set
        'charset'  => null,
        // The column collation
        'collation' => null,
        // TRUE if the column is nullable
        'nullable' => null,
        // Column default
        'default'  => null,
         // Column extra (see http://dev.mysql.com/doc/refman/5.7/en/create-table.html)
        'extra'    => null,
        // The column comment
        'comment'   => null,
        // Column hints object used to control behavior such as renaming columns
        'hints'     => null
    );

    /* ------------------------------------------------------------------------------------------
     * @see iEntity::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($config, $systemQuoteChar = null, LoggerInterface $logger = null)
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

            case 'name':
                // Normalize property values to lowercase to match MySQL behavior
                $value = strtolower($value);
                break;

            case 'nullable':
                $origValue = $value;
                $value = \xd_utilities\filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ( null === $value ) {
                    $this->logAndThrowException(
                        sprintf("%s must be a boolean, '%s' given", $property, gettype($origValue))
                    );
                }
                break;

            case 'extra':
            case 'comment':
                if ( ! is_string($value) ) {
                    $this->logAndThrowException(
                        sprintf("%s name must be a string, '%s' given", $property, gettype($value))
                    );
                }
                // Normalize property values to lowercase to match MySQL behavior
                if ( 'extra' == $property ) {
                    $value = strtolower($value);
                }
                break;

            case 'type':
            case 'charset':
            case 'collation':
                if ( ! is_string($value) ) {
                    $this->logAndThrowException(
                        sprintf("%s name must be a string, '%s' given", $property, gettype($value))
                    );
                }
                // Normalize property values to lowercase to match MySQL behavior but handle enum
                // properly by only lowercasing the "enum" itself and not the possible values.
                if ( 0 === stripos($value, 'enum') ) {
                    $value = strtolower(substr($value, 0, 4)) . substr($value, 4);
                } else {
                    $value = strtolower($value);
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
        if ( ! $cmp instanceof Column ) {
            return 1;
        }

        if ( ($retval = parent::compare($cmp)) != 0 ) {
            return $retval;
        }

        // Columns are considered equal if all non-null properties in both Columns are the same. The
        // name and type are required but if other properties are set use those in the comparison as
        // well.
        //
        // Note that the "enum" type will be handled in a special case below so only match types here
        // that are different and are not both enumerated.

        if ( $this->type != $cmp->type && ! (0 === strpos($this->type, 'enum') && 0 === strpos($cmp->type, 'enum')) ) {
            $this->logCompareFailure('type', $this->type, $cmp->type, $this->name);
            return -1;
        }

        // Timestamp fields have special handling for default and extra fields.
        // See https://dev.mysql.com/doc/refman/5.5/en/timestamp-initialization.html

        if ( "timestamp" == $this->type ) {

            // In the mode where a config file is compared to an existing database, the source is
            // considered the configuration while the destination is the database.

            $srcDefault = $this->default;
            $destDefault = $cmp->default;
            $srcExtra = $this->extra;
            $destExtra = $cmp->extra;

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

            // If no DEFAULT and no EXTRA is provided MySQL will use:
            // DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            //
            // If no DEFAULT is provided but an EXTRA is provided the default will be 0 unless
            // the collumn is nullable, then it will be NULL.
            //
            // **WARNING**
            //
            // Having multiple TIMESTAMP columns in the same table may result in unexpected behavior
            // for the 2nd or following columns.
            //
            // See:
            // https://dev.mysql.com/doc/refman/5.5/en/timestamp-initialization.html
            // http://jasonbos.co/two-timestamp-columns-in-mysql
            //
            // One TIMESTAMP column in a table can have the current timestamp as the default value
            // for initializing the column, as the auto-update value, or both. It is not possible to
            // have the current timestamp be the default value for one column and the auto-update
            // value for another column.  To specify automatic initialization or updating for a
            // different TIMESTAMP column, you must suppress the automatic properties for the first
            // one.

            if (
                (
                    (null === $srcDefault && null === $srcExtra)
                    || ('current_timestamp' === strtolower($srcDefault) && 'on update current_timestamp' === strtolower($srcExtra))
                )
                && ('current_timestamp' != strtolower($destDefault) || null === $destExtra)
            ) {
                $this->logCompareFailure('timestamp', "$srcDefault $srcExtra", "$destDefault $destExtra", $this->name);
                return -1;
            }

            // With a DEFAULT clause but no ON UPDATE CURRENT_TIMESTAMP clause, the column has the
            // given default value and is not automatically updated to the current timestamp.
            // Note that valid defaults are 0 or a datetime format and MySQL will translate 0 to
            // '0000-00-00 00:00:00' and will add '00:00:00' to date strings.

            if ( (null !== $srcDefault && null === $srcExtra) ) {
                if ( null !== $destExtra ) {
                    // Extra has changed (destination has a value)
                    $this->logCompareFailure('timestamp extra', $srcExtra, $destExtra, $this->name);
                    return -1;
                } elseif ( null === $destDefault ) {
                    // Default has changed from NULL to something
                    $this->logCompareFailure('timestamp default', $srcDefault, $destDefault, $this->name);
                    return -1;
                } elseif ( strtolower($srcDefault) != strtolower($destDefault)
                            && ( ("0" == "$srcDefault" && '0000-00-00 00:00:00' != $destDefault)
                                 || ("0" != "$srcDefault" && $srcDefault . ' 00:00:00' != $destDefault) ) )
                {
                    // Note the casting of 0 to "0" above is necessary because php considers 0 == "0000-01-01" to be true.
                    // Default has changed
                    $this->logCompareFailure('timestamp default', $srcDefault, $destDefault, $this->name);
                    return -1;
                }
            }

            // With an ON UPDATE CURRENT_TIMESTAMP clause and a constant DEFAULT clause, the column
            // is automatically updated to the current timestamp and has the given constant default
            // value.

            if ( null !== $srcDefault && null !== $srcExtra ) {
                if ( $srcExtra != $destExtra ) {
                    $this->logCompareFailure('timestamp extra', $srcExtra, $destExtra, $this->name);
                    return -1;
                } elseif ( strtolower($srcDefault) != strtolower($destDefault)
                            && ( ("0" == "$srcDefault" && '0000-00-00 00:00:00' != $destDefault)
                                 || ("0" != "$srcDefault" && $srcDefault . ' 00:00:00' != $destDefault)) )
                {
                    // Note the casting of 0 to "0" above is necessary because php considers 0 == "0000-01-01" to be true.
                    $this->logCompareFailure('timestamp default', $srcDefault, $destDefault, $this->name);
                    return -1;
                }
            }

            // With an ON UPDATE CURRENT_TIMESTAMP clause but no DEFAULT clause, the column is
            // automatically updated to the current timestamp. The default is 0 unless the column is
            // defined with the NULL attribute, in which case the default is NULL.

            if ( null === $srcDefault && null !== $srcExtra ) {
                if ( null === $destExtra
                     || $srcExtra != $destExtra
                     || (null !== $destDefault && '0000-00-00 00:00:00' != $destDefault) )
                {
                    $this->logCompareFailure('timestamp', "$srcDefault $srcExtra", "$destDefault $destExtra", $this->name);
                    return -1;
                }
            }

        } else {
            // The following properties do not have defaults set by the database and should be considered if
            // one of them is set.
            if ( ( null !== $this->default || null !== $cmp->default ) && $this->default != $cmp->default ) {
                $this->logCompareFailure('default', $this->default, $cmp->default, $this->name);
                return -1;
            }

            if ( ( null !== $this->extra || null !== $cmp->extra ) && $this->extra != $cmp->extra ) {
                $this->logCompareFailure('extra', $this->extra, $cmp->extra, $this->name);
                return -1;
            }
        } // else ( "timestamp" == $this->type )

        // The enum type may be formatted by the database to add/remove spaces between parameter
        // values. Normalize the values before comparing.

        if ( 0 === ($myStartPos = strpos($this->type, 'enum'))
             && 0 === ($cmpStartPos = strpos($cmp->type, 'enum')) ) {
            // Extract the enum value list and normalize it to include no spaces between values
            $myType = substr($this->type, 4);
            $myType = implode(',', preg_split('/\s*,\s*/', trim($myType, "() \t\n\r\0\x0B")));
            $cmpType = substr($cmp->type, 4);
            $cmpType = implode(',', preg_split('/\s*,\s*/', trim($cmpType, "() \t\n\r\0\x0B")));
            if ( $myType != $cmpType ) {
                $this->logCompareFailure('type enum', $myType, $cmpType, $this->name);
                return -1;
            }
        }

        // The following properties have a default set by the database. If the property is not specified
        // a value will be provided when the database information schema is queried.

        if ( ( null !== $this->nullable && null !== $cmp->nullable ) && $this->nullable != $cmp->nullable ) {
            $this->logCompareFailure(
                'nullable',
                ($this->nullable ? 'true' : 'false'),
                ($cmp->nullable ? 'true' : 'false'),
                $this->name
            );
            return -1;
        }

        // The following properties do not have defaults set by the database and should be considered if
        // one of them is set.

        if ( ( null !== $this->comment || null !== $cmp->comment ) && $this->comment != $cmp->comment ) {
            $this->logCompareFailure('comment', $this->comment, $cmp->comment, $this->name);
            return -1;
        }

        // Character set and collation have defaults that are set by the table, database or server.
        // See https://dev.mysql.com/doc/refman/5.5/en/charset-syntax.html

        if ( ( null !== $this->charset && null !== $cmp->charset ) && $this->charset != $cmp->charset ) {
            $this->logCompareFailure('charset', $this->charset, $cmp->charset, $this->name);
            return -1;
        }

        if ( ( null !== $this->collation && null !== $cmp->collation ) && $this->collation != $cmp->collation ) {
            $this->logCompareFailure('collation', $this->collation, $cmp->collation, $this->name);
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
        // Name and type are required. null values are treated as not provided/specified.

        // Columns may be created or altered in different ways (CREATE TABLE vs. ALTER TABLE) so we only
        // return the essentials of the definition and let the Table class figure out the appropriate
        // way to put them together.

        $parts = array();
        $parts[] = $this->getName(true);
        $parts[] = $this->type;

        if ( null !== $this->charset ) {
            $parts[] = 'CHARSET ' . $this->charset;
        }

        if ( null !== $this->collation ) {
            $parts[] = 'COLLATE ' . $this->collation;
        }

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

        $currentTimestampAliases = array(
            'current_timestamp',
            'current_timestamp()',
            'now()',
            'localtime',
            'localtime()',
            'localtimestamp',
            'localtimestamp()'
        );

        if ( null !== $this->default ) {

            if (
                 ( $this->nullable && "NULL" == $this->default ) ||
                 ( "timestamp" == $this->type && is_numeric($this->default) ) ||
                 ( "timestamp" == $this->type && in_array(strtolower($this->default), $currentTimestampAliases) ) ||
                 ( "datetime" === $this->type && in_array(strtolower($this->default), $currentTimestampAliases) ) ||
                 is_numeric($this->default) ||
                 "b'" == substr($this->default, 0, 2) ||
                 "x'" == substr(strtolower($this->default), 0, 2)
            ) {
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

    }  // getSql()
}  // class Column
