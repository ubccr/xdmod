<?php
/**
 * Perform ETL on federated resources.  This is different than the traditional ETL process in that
 * it uses a new mechanism for passing options to the ingesters and is (hopefully) more flexible.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 */

require __DIR__ . '/../configuration/linker.php';
restore_exception_handler();

use CCR\Log;
use ETL\Configuration\EtlConfiguration;
use ETL\DbModel\Table;
use ETL\DbModel\AggregationTable;

$supportedFormats = array("json", "sql");

// ==========================================================================================
// Script options with defaults

$scriptOptions = array(
  // ETL configuration file
  'config-file'       => NULL,
  // Endpoint (defined in the ETL config) to use when querying tables
  'endpoint'          => "utility",
  // Table to use in discovery mode, needed for alter statement
  'discover-table'    => NULL,
  // TRUE to include the schema name in tables and triggers
  'include-schema'    => FALSE,
  // Operation to perform
  'operation'         => NULL,
  // Output file
  'output-file'       => NULL,
  // Output format (json or sql)
  'output-format'     => 'json',
  // Succinct or verbose mode
  'succinct-mode'     => FALSE,
  // Table definition file
  'table-config'      => NULL,
  'verbosity'         => Log::NOTICE
  );

// ==========================================================================================
// Process command line arguments

$options = array(
  'h'   => 'help',
  'c:'  => 'config-file:',
  'd:'  => 'discover-table:',
  'f:'  => 'output-file:',
  'e:'  => 'endpoint:',
  'i'   => 'include-schema',
  'o:'  => 'operation:',
  's'   => 'succinct',
  't:'  => 'table-config:',
  'v:'  => 'verbosity:',
  'x:'  => 'output-format:'
  );

$args = getopt(implode('', array_keys($options)), $options);

foreach ($args as $arg => $value) {
  switch ($arg) {

  case 'c':
  case 'config-file':
    $scriptOptions['config-file'] = $value;
    break;

  case 'd':
  case 'discover-table':
    $scriptOptions['discover-table'] = $value;
    break;

  case 'e':
  case 'endpoint':
    $scriptOptions['endpoint'] = $value;
    break;

  case 'f':
  case 'output-file':
    $scriptOptions['output-file'] = $value;
    break;

  case 'i':
  case 'include-schema':
    $scriptOptions['include-schema'] = TRUE;
    break;

  case 'o':
  case 'operation':
    $scriptOptions['operation'] = $value;
    break;

  case 's':
  case 'succinct':
    $scriptOptions['succinct-mode'] = TRUE;
    break;

  case 't':
  case 'table-config':
    $scriptOptions['table-config'] = $value;
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
      break;
    }  // switch ( $value )
    break;

  case 'x':
  case 'output-format':
    if ( ! in_array($value, $supportedFormats) ) usage_and_exit("Unsupported output format");
    $scriptOptions['output-format'] = $value;
    break;

  case 'h':
  case 'help':
    usage_and_exit();
    break;

  default:
    break;
  }
}  // foreach ($args as $arg => $value)

// ------------------------------------------------------------------------------------------
// Set up the logger

$conf = array(
  'emailSubject' => gethostname() . ': XDMOD: Data Warehouse: Federated ETL Log',
  'mail' => FALSE
  );

if ( NULL !== $scriptOptions['verbosity'] ) $conf['consoleLogLevel'] = $scriptOptions['verbosity'];

$logger = Log::factory('etl_aggregation_table_manager', $conf);

if ( NULL === $scriptOptions['config-file'] ||
     NULL === $scriptOptions['operation'] ) {
  usage_and_exit();
} else if ( ! is_file($scriptOptions['config-file']) ) {
  usage_and_exit("Config file not found: '" . $scriptOptions['config-file'] . "'");
}

// ------------------------------------------------------------------------------------------
// Parse the ETL configuration. We will need it for listing available ingestors, aggregators, etc.

try {
  $etlConfig = EtlConfiguration::factory($scriptOptions['config-file']);
} catch ( Exception $e ) {
  exit($e->getMessage() . "\n");
}

// ------------------------------------------------------------------------------------------
// Verify the requested endpoint exists

$dataEndpoint = NULL;
if ( FALSE === ($dataEndpoint = $etlConfig->getGlobalEndpoint($scriptOptions['endpoint'])) ) {
  $msg = "Global utility endpoint not defined, cannot query database for resource code mapping";
  throw new Exception($msg);
}

// ------------------------------------------------------------------------------------------

$parsedTable = NULL;

if ( NULL !== $scriptOptions['table-config'] ) {
  try {
    $parsedTable = new AggregationTable($scriptOptions['table-config']);
    $parsedTable->setAggregationUnit("year");
    // print_r($parsedTable); exit();
    // print_r($parsedTable->getCreateSql());
    $parsedTable->verify();
  } catch ( Exception $e) {
    exit($e->getMessage() . "\n");
  }
}

$discoveredTable = NULL;

if ( NULL !== $scriptOptions['discover-table'] ) {
  try {
    $discoveredTable = Table::discover($scriptOptions['discover-table'], $dataEndpoint);
  } catch ( Exception $e ) {
    $msg = "Error discovering table '" . $scriptOptions['discover-table'] . "' using endpoint $dataEndpoint: " .
      $e->getMessage() . "\n";
    exit($msg);
  }
}

// Perform the requested operation

$outputStr = NULL;

try {
  switch ( $scriptOptions['operation'] ) {

  case 'dump-discovered':
    if ( NULL !== $discoveredTable ) {
      $outputStr = ( "json" == $scriptOptions['output-format']
                     ? $discoveredTable->toJson($scriptOptions['succinct-mode'], $scriptOptions['include-schema'])
                     : "DELIMITER ;;\n" . implode("\n;;\n", $discoveredTable->getCreateSql($scriptOptions['include-schema'])) . "\n;;" );
    }
    break;

  case 'dump-parsed':
    if ( NULL !== $parsedTable ) {
      $outputStr = ( "json" == $scriptOptions['output-format']
                     ? $parsedTable->toJson($scriptOptions['succinct-mode'], $scriptOptions['include-schema'])
//                     : "DELIMITER ;;\n" . $parsedTable->getSelectSql($scriptOptions['include-schema']) . "\n;;" );
                   : "DELIMITER ;;\n" . implode("\n;;\n", $parsedTable->getCreateSql($scriptOptions['include-schema'])) . "\n;;" );
    }
    break;

  case 'dump-alter':
    if ( NULL !== $discoveredTable && NULL !== $parsedTable ) {
      if ( "json" == $scriptOptions['output-format'] ) usage_and_exit("JSON format not supported for ALTER TABLE");
      $alterSqlList = $discoveredTable->getAlterSql($parsedTable, $scriptOptions['include-schema']);
      if ( $alterSqlList ) $outputStr = "DELIMITER ;;\n" . implode("\n;;\n", $alterSqlList) . "\n;;";
    }
    break;

  default:
    usage_and_exit("Unknown operation");
    break;
  }
} catch (Exception $e) {
  $msg = "Error performing '" . $scriptOptions['operation'] . "': " . $e->getMessage() . "\n";
  exit($msg);
}

if ( NULL === $outputStr ) exit(0);

if ( NULL !== $scriptOptions['output-file'] ) {
  file_put_contents($scriptOptions['output-file'], "$outputStr\n");
} else {
  fwrite(STDOUT, "$outputStr\n");
}


exit(0);

// ==========================================================================================


/**
 * Display usage text and exit with error status.
 */

function usage_and_exit($msg = null)
{
  global $argv, $scriptOptions, $supportedFormats;

  if ($msg !== null) {
    fwrite(STDERR, "\n$msg\n\n");
  }

  $verbosityDefault = Log::NOTICE;
  $availablelFormats = implode(",", $supportedFormats);

  fwrite(
    STDERR,
    <<<"EOMSG"
    Usage: {$argv[0]}
    -h, --help
    Display this help

    -c, --config-file <file>
    ETL configuration file [default {$scriptOptions['config-file']}]

    -d, --discover-table <table>
    Existing database table to perform discovery on.

    -e, --endpoint <endpoint>
    The name of the endpoint (defined in the ETL configuration file) that will be used to connect to the database.

    -f, --output-file <file>
    Output file for saving results.

    -o, --operation <operation>
    Operation to perform:
      dump-discovered - Dump the discovered table
      dump-parsed - Dump the parsed table
      dump-alter - Dump the alter SQL to bring the discovered table in line with the parsed table

    -s, --succinct
    Dump JSON in succinct format

    -t, --table-config <file>
    Table definition file to parse

    -v, --verbosity {debug, info, notice, warning} [default $verbosityDefault]
    Level of verbosity to output from the ETL process

    -x, --output-format <format>
    Output format ($availablelFormats)

EOMSG
    );

  exit(1);
}
