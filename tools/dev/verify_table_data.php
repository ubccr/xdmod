#!/usr/bin/env php
<?php
/**
 * Verify the structure and data in multiple tables. Useful for verifying ETL changes.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 */

require __DIR__ . '/../../configuration/linker.php';
restore_exception_handler();

use CCR\Log;
use CCR\DB;

// ==========================================================================================
// Script options with defaults

// Allow initialization of some options from the configuration file

$scriptOptions = array(
    // Tables for comparison
    'compare-tables'   => array(),
    // Configuration section to use when connecting to the database
    'database-config'  => "datawarehouse",
    // Destination table schema, defaults to source table schema if not set
    'dest-schema'      => null,
    // Exclude these columns from tables
    'exclude-columns'  => array(),
    // Ignore the column count between tables as long as the source columns are present in
    // the destination
    'ignore-column-count' => false,
    // Ignore the column types between tables
    'ignore-column-type'  => false,
    // Map these column names from source to destination tables
    'map-columns'      => array(),
    // Number of missing rows to display, all rows if NULL
    'num-missing-rows' => null,
    // Round the values of these columns
    'round-columns'    => array(),
    // Source table schema
    'source-schema'    => null,
    // Apply where clauses to query
    'wheres'           => array(),
    'verbosity'        => Log::NOTICE
);

// ==========================================================================================
// Process command line arguments

$options = array(
    'h'   => 'help',
    'c:'  => 'database-config:',
    'd:'  => 'dest-schema:',
    'n:'  => 'num-missing-rows:',
    'r:'  => 'round-column:',
    's:'  => 'source-schema:',
    't:'  => 'table:',
    'v:'  => 'verbosity:',
    'w:'  => 'where:',
    'x:'  => 'exclude-column:',
    ''    => 'ignore-column-count',
    ''    => 'ignore-column-type'
    );

$args = getopt(implode('', array_keys($options)), $options);

foreach ($args as $arg => $value) {
    switch ($arg) {

        case 'c':
        case 'database-config':
            $scriptOptions['database-config'] = $value;
            break;

        case 'd':
        case 'dest-schema':
            $scriptOptions['dest-schema'] = $value;
            break;

        case 'ignore-column-count':
            $scriptOptions['ignore-column-count'] = true;
            break;

        case 'ignore-column-type':
            $scriptOptions['ignore-column-type'] = true;
            break;

        case 'n':
        case 'num-missing-rows':
            $scriptOptions['num-missing-rows'] = $value;
            break;

        case 'r':
        case 'round-column':
            // Merge array because long and short options are grouped separately
            $scriptOptions['round-columns'] = array_merge(
                $scriptOptions['round-columns'],
                ( is_array($value) ? $value : array($value) )
            );
            break;

        case 's':
        case 'source-schema':
            $scriptOptions['source-schema'] = $value;
            break;

        case 't':
        case 'table':
            $value = ( is_array($value) ? $value : array($value) );
            foreach ( $value as $option ) {
                $parts = explode('=', $option);
                if ( 1 == count($parts) ) {
                    $scriptOptions['compare-tables'][] = array($parts[0], $parts[0]);
                } elseif ( 2 == count($parts) ) {
                    $scriptOptions['compare-tables'][] = array($parts[0], $parts[1]);
                } else {
                    usage_and_exit("Tables must be in the form 'table' or 'source_table=dest_table'");
                }
            }
            break;

        case 'x':
        case 'exclude-column':
            // Merge array because long and short options are grouped separately
            $scriptOptions['exclude-columns'] = array_merge(
                $scriptOptions['exclude-columns'],
                ( is_array($value) ? $value : array($value) )
            );
            break;

        case 'w':
        case 'where':
            // Merge array because long and short options are grouped separately
            $scriptOptions['wheres'] = array_merge(
                $scriptOptions['wheres'],
                ( is_array($value) ? $value : array($value) )
            );
            break;

        case 'v':
        case 'verbosity':
            switch ( $value ) {
                case 'debug':
                    $scriptOptions['verbosity'] = Log::DEBUG;
                    break;
                case 'info':
                    $scriptOptions['verbosity'] = Log::INFO;
                    break;
                case 'notice':
                    $scriptOptions['verbosity'] = Log::NOTICE;
                    break;
                case 'warning':
                    $scriptOptions['verbosity'] = Log::WARNING;
                    break;
                case 'quiet':
                    $scriptOptions['verbosity'] = Log::EMERG;
                    break;
                default:
                    usage_and_exit("Invalid verbosity level: $value");
                    break;
            }  // switch ( $value )
            break;

        case 'h':
        case 'help':
            usage_and_exit();
            break;

        default:
            usage_and_exit("Invalid option: $arg");
            break;
    }
}  // foreach ($args as $arg => $value)

if ( null === $scriptOptions['source-schema'] ) {
    usage_and_exit("Source schema not specified");
}

if ( 0 == count($scriptOptions['compare-tables']) ) {
    usage_and_exit("No tables specified for comparison");
}

if ( null === $scriptOptions['dest-schema'] ) {
    $scriptOptions['dest-schema'] = $scriptOptions['source-schema'];
}

// ------------------------------------------------------------------------------------------
// Set up the logger

$conf = array(
    'mail' => false
);

if ( null !== $scriptOptions['verbosity'] ) {
    $conf['consoleLogLevel'] = $scriptOptions['verbosity'];
}

$logger = Log::factory('ETLv2', $conf);

try {
    $dbh = DB::factory($scriptOptions['database-config']);
} catch (Exception $e) {
    exit("Error connecting to database: " . $e->getMessage() . "\n");
}

// ------------------------------------------------------------------------------------------
// Verify the tables

$success = true;

foreach ($scriptOptions['compare-tables'] as $table ) {

    list($srcTable, $destTable) = $table;
    $retval = compareTables($srcTable, $destTable);
    $success = $success && $retval;
}

exit($success ? 0 : 1);

/* ------------------------------------------------------------------------------------------
 * Compare the structure and contents of 2 tables.
 * -------------------------------------------------------------------------------------------
 */

function compareTables($srcTable, $destTable)
{
    global $scriptOptions, $logger;

    $srcSchema = $scriptOptions['source-schema'];
    $destSchema = $scriptOptions['dest-schema'];

    // Tables may already contain a schema specification. If it does, override the defdault schema.

    if ( false !== strpos($srcTable, '.') ) {
        $parts = explode('.', $srcTable);
        if ( 2 != count($parts) ) {
            $logger->err("Too many dots in source table name: '$srcTable'");
            return false;
        }
        list($srcSchema, $srcTable) = $parts;
    }

    if ( false !== strpos($destTable, '.') ) {
        $parts = explode('.', $destTable);
        if ( 2 != count($parts) ) {
            $logger->err("Too many dots in destination table name: '$destTable'");
            return false;
        }
        list($destSchema, $destTable) = $parts;
    }

    $qualifiedSrcTable = sprintf("%s.%s", $srcSchema, $srcTable);
    $qualifiedDestTable = sprintf("%s.%s", $destSchema, $destTable);
    $logger->notice(sprintf("Compare tables src=%s, dest=%s", $qualifiedSrcTable, $qualifiedDestTable));
    if ( 0 != count($scriptOptions['exclude-columns']) ) {
        $logger->info("Exclude columns: " . implode(', ', $scriptOptions['exclude-columns']));
    }

    if ( $qualifiedSrcTable == $qualifiedDestTable ){
        $logger->warning(sprintf(
            "Cannot compare a table to itself: %s.%s == %s.%s",
            $qualifiedSrcTable,
            $qualifiedDestTable
        ));
        return false;
    }

    // Verify number and type of columns

    $srcTableColumns = getTableColumns($srcTable, $srcSchema, $scriptOptions['exclude-columns']);
    $destTableColumns = getTableColumns($destTable, $destSchema, $scriptOptions['exclude-columns']);
    $numSrcColumns = count($srcTableColumns);
    $numDestColumns = count($destTableColumns);

    if ( ! $scriptOptions['ignore-column-count'] && $numSrcColumns != $numDestColumns ) {
        $logger->err(sprintf(
            "Column number mismatch %s (%d); dest %s (%d)",
            $qualifiedSrcTable,
            $numSrcColumns,
            $qualifiedDestTable,
            $numDestColumns
        ));
        return false;
    }

    $missing = array_diff(array_keys($srcTableColumns), array_keys($destTableColumns));
    if ( 0 != count($missing) ) {
        $logger->err(sprintf("%s missing columns: %s", $qualifiedDestTable, implode(', ', $missing)));
        return false;
    }

    $logger->info(sprintf("%d columns", $numSrcColumns));

    $mismatch = false;
    foreach ( $srcTableColumns as $k => $v ) {
        if ( ! array_key_exists($k, $destTableColumns) ) {
            $logger->warning(
                sprintf("Dest missing %s type=%s key=%s", $k, $v['type'], $v['key_type'])
            );
            $mismatch = true;
        } elseif ( $v != $destTableColumns[$k] && ! $scriptOptions['ignore-column-type'] ) {
            $logger->err(sprintf(
                "Column mismatch %s: src type=%s %s, dest type=%s %s",
                $k,
                $v['type'],
                ( "" != $v['key_type'] ? "key=" . $v['key_type'] : "" ),
                $destTableColumns[$k]['type'],
                ( "" != $destTableColumns[$k]['key_type'] ? "key=" . $destTableColumns[$k]['key_type'] : "" )
            ));
            $mismatch = true;
        }
    }
    if ( $mismatch ) {
        return false;
    }

    $numSrcRows = getTableRows($srcTable, $srcSchema);
    $numDestRows = getTableRows($destTable, $destSchema);
    $logger->info(sprintf(
        "Row counts: %s = %s; %s = %s",
        $qualifiedSrcTable,
        number_format($numSrcRows),
        $qualifiedDestTable,
        number_format($numDestRows)
    ));

    return compareTableData(
        $srcTable,
        $destTable,
        $srcSchema,
        $destSchema,
        array_keys($srcTableColumns),
        array_keys($destTableColumns)
    );

}  // compareTables()

/* ------------------------------------------------------------------------------------------
 * Query the information schema for table column information.
 * -------------------------------------------------------------------------------------------
 */

function getTableColumns($table, $schema, array $excludeColumns)
{
    global $dbh, $logger;
    $tableName = "`$schema`.`$table`";

    $where = array(
        "table_schema = :schema",
        "table_name = :tablename",
    );

    if ( 0 != count($excludeColumns) ) {
        $excludeColumns = array_map(
            function ($c) {
                return "'$c'";
            },
            $excludeColumns
        );
        $where[] = "column_name NOT IN (" . implode(',', $excludeColumns) . ")";
    }

    $sql = "SELECT
column_name as name,
column_type as type,
column_key as key_type
FROM information_schema.columns
" . ( 0 != count($where) ? "WHERE " . implode(' AND ', $where) : "" ) ."
ORDER BY ordinal_position ASC";

    $params = array(
        ":schema" => $schema,
        ":tablename"  => $table
    );

    try {
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
    } catch ( Exception $e ) {
        $logger->err("Error retrieving column names for '$tableName': " . $e->getMessage());
        exit();
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ( 0 == count($result) ) {
        $logger->err("Table '$tableName' does not exist");
        exit();
    }

    $retval = array();

    foreach ( $result as $row) {
        $retval[$row['name']] = $row;
    }

    // Sort the columns because there is no guarantee the order they are returned
    ksort($retval);

    return $retval;
}  // getTableColumns()

/* ------------------------------------------------------------------------------------------
 * Query the information schema for the number of table rows
 * -------------------------------------------------------------------------------------------
 */

function getTableRows($table, $schema)
{
    global $dbh, $logger;
    $tableName = "`$schema`.`$table`";

    $sql = "SELECT COUNT(*) AS table_rows FROM $tableName";

    try {
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
    } catch ( Exception $e ) {
        $logger->err("Error retrieving table information for '$tableName': " . $e->getMessage());
        exit();
    }

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ( 0 == count($result) ) {
        $logger->err("Table '$tableName' does not exist");
        exit();
    }

    $row = array_shift($result);

    return $row['table_rows'];

}  // getTableRows()

/* ------------------------------------------------------------------------------------------
 * Compare the data in two tables
 * -------------------------------------------------------------------------------------------
 */

function compareTableData(
    $srcTable,
    $destTable,
    $srcSchema,
    $destSchema,
    array $srcTableColumns,
    array $destTableColumns
) {
    global $dbh, $logger, $scriptOptions;
    $srcTableName = "`$srcSchema`.`$srcTable`";
    $destTableName = "`$destSchema`.`$destTable`";
    $firstCol = current($srcTableColumns);

    // Determine the columns to round, if any.

    $roundColumns = array();
    foreach ( $scriptOptions['round-columns'] as $column ) {
        $parts = explode(',', $column);
        $roundColumns[$parts[0]] = ( 2 == count($parts) ? $parts[1] : 0 );
    }

    // Generate the ON clause using on the source table columns. This ignores columns
    // present in the destination table that do not exist in the source table.

    $constraints = array_map(
        function ($c1, $c2) use ($roundColumns) {
            if ( array_key_exists($c1, $roundColumns) ) {
                return sprintf(
                    'ROUND(src.%s, %d) <=> ROUND(dest.%s, %d)',
                    $c1,
                    $roundColumns[$c1],
                    $c2,
                    $roundColumns[$c1]
                );
            } else {
                // Note the use of the null-safe operator <=>
                return sprintf('src.%s <=> dest.%s', $c1, $c2);
            }
        },
        $srcTableColumns,
        $srcTableColumns
    );

    $where = array(
        "dest.$firstCol IS NULL"
    );

    if ( 0 != count($scriptOptions['wheres']) ) {
        $where = array_merge($where, $scriptOptions['wheres']);
    }

    $sql = "
SELECT src.*
FROM $srcTableName src
LEFT OUTER JOIN $destTableName dest ON (" . join("\nAND ", $constraints) . ")"
        . ( 0 != count($where) ? "\nWHERE " . implode("\nAND ", $where) : "" )
        . ( null !== $scriptOptions['num-missing-rows']
            ? "\nLIMIT " . $scriptOptions['num-missing-rows']
            : "" );

    $logger->debug($sql);

    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $numRows = $stmt->rowCount();

    if ( 0 != $numRows ) {
        $logger->warning(sprintf("Missing %d rows in %s.%s", $numRows, $destSchema, $destTable));
        while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
            $logger->warning(sprintf("Missing row: %s", print_r($row, 1)));
        }
    } else {
        $logger->notice("Identical");
    }

    return (0 == $numRows);

}  // compareTableData()

/* ------------------------------------------------------------------------------------------
 * Display usage text and exit with error status.
 * ------------------------------------------------------------------------------------------
 */

function usage_and_exit($msg = null)
{
    global $argv, $scriptOptions;

    if ($msg !== null) {
        fwrite(STDERR, "\n$msg\n\n");
    }

    fwrite(
        STDERR,
        <<<"EOMSG"
Usage: {$argv[0]}

    -h, --help
    Display this help

    -c, --database-config
    The portal_settings.ini section to use for database configuration parameters

    -d, --dest-schema <destination_schema>
    The schema for the destination tables. If not specified the source schema will be used.

    --ignore-column-count
    Ignore the column count between tables as long as the source columns are present in the destination.

    --ignore-column-count
    Ignore the column types between tables, useful for comparing the effect of data type changes.

    -n, --num-missing-rows <number_of_rows>
    Display this number of missing rows. If not specified, all missing rows are displayed.

    -r, --round-column <column>[,<digits>]
    Round the values in the specified column before comparing. If <digits> is specified round to that number of digits (default 0). This is useful when comparing doubles or values that have been computed and may differ in decimal precision.

    -s, --source-schema <source_schema>
    The schema for the source tables.

    -t, --table <table_name>
    -t, --table <source_table_name>=<dest_table_name>
    A table to compare between the source and destination schemas. Use the 2nd form to specify different names for the source and destination tables. Table names may also include a schema designation, in which case the default schema will not be added. May be specified multiple times.

    -w, --where <where_clause_fragment>
    Add a WHERE clause to the table comparison. The table aliass "src" and "dest" refer to the source and destination tables, respectively.

    -x, --exclude-column
    Exclude this column from the comparison. May be specified multiple times.

    -v, --verbosity {debug, info, notice, warning, quiet} [default notice]
    Level of verbosity to output from the ETL process

EOMSG
    );

    exit(1);
}
?>
