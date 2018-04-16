#!/usr/bin/env php
<?php
/* ==========================================================================================
 * Manage ETL Action State Object. This includes:
 *  1. List existing state objects
 *  2. Showing contents of state objects
 *  3. Deleting state objects
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-07-01
 * ==========================================================================================
 */

require __DIR__ . '/../../configuration/linker.php';
restore_exception_handler();

// Character to use when separating list output
const LIST_SEPARATOR = "\t";

use \Exception;
use CCR\Log;
use CCR\DB;
use ETL\Configuration\EtlConfiguration;
use ETL\Utilities;
use ETL\State\StateManager;

// ==========================================================================================
// Script options with defaults

$scriptOptions = array(
    // Base path for relative file locations
    'base-dir'          => null,
    // ETL configuration file
    'config-file'       => CONFIG_DIR . '/etl/etl.json',
    // List of action state objects to delete
    'delete-objects'    => array(),
    // Run in dryrun mode performing all operations except execution of the actions
    'dryrun'            => false,
    // Data endpoint to use (defined in EtlConfiguration)
    'endpoint'          => "destination",
    // List action state objects
    'list'              => false,
    // List of action state objects to dump
    'info-objects'      => array(),
    'verbosity'         => Log::NOTICE
    );

$showList = false;

// ==========================================================================================
// Process command line arguments

$options = array(
    'h'   => 'help',
    'b:'  => 'base-dir:',
    'c:'  => 'config-file:',
    'd:'  => 'delete:',
    'e:'  => 'endpoint:',
    'i:'  => 'info:',
    'l'  => 'list',
    't'   => 'dryrun',
    'v:'  => 'verbosity:'
    );

$args = getopt(implode('', array_keys($options)), $options);

foreach ($args as $arg => $value) {
    switch ($arg) {

    case 'b':
    case 'base-dir':
        if ( ! is_dir($value) ) usage_and_exit("Base directory does not exist: '$value'");
        $scriptOptions['base-dir'] = $value;
        break;

    case 'c':
    case 'config-file':
        $scriptOptions['config-file'] = $value;
        break;

    case 'd':
    case 'delete':
        $scriptOptions['delete-objects'] = array_merge($scriptOptions['delete-objects'],
                                                       ( is_array($value) ? $value : array($value) ));
        break;

    case 't':
    case 'dryrun':
        $scriptOptions['dryrun'] = true;
        break;

    case 'e':
    case 'endpoint':
        $scriptOptions['endpoint'] = $value;
        break;

    case 'i':
    case 'info':
        $scriptOptions['info-objects'] = array_merge($scriptOptions['info-objects'],
                                                     ( is_array($value) ? $value : array($value) ));
        break;

    case 'l':
    case 'list':
        $scriptOptions['list'] = true;
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

// Check for options that were passed but not processed by getopt() including arguments after a
// non-option. For example, if you modified your command line and removed -k by accident you don't
// want to throw away your --dryrun
//
// etl_overseer.php -c /etc/etl/etl.json -n 2 -p osg month --dryrun

$parsedArgs = array_keys($args);
foreach ( $argv as $index => $arg ) {
    $opt = null;

    if ( 0 === strpos($arg, "--") ) {
        $opt = substr($arg, 2);
    } else if ( 0 === strpos($arg, "-") ) {
        $opt = substr($arg, 1);
    }
    if ( null !== $opt && ! in_array($opt, $parsedArgs) ) {
        usage_and_exit("Unparsed argument: $arg");
    }
}

// ------------------------------------------------------------------------------------------
// Set up the logger

$conf = array(
    'emailSubject' => gethostname() . ': XDMOD: Data Warehouse: Federated ETL Log',
    'mail' => false
    );

if ( null !== $scriptOptions['verbosity'] ) $conf['consoleLogLevel'] = $scriptOptions['verbosity'];

$logger = Log::factory('DWI', $conf);

$cmd = implode(' ', array_map('escapeshellarg', $argv));
$logger->info("Command: $cmd");

if ( null === $scriptOptions['config-file'] ) {
    usage_and_exit("Config file required");
} else if ( null !== $scriptOptions['config-file'] && ! is_file($scriptOptions['config-file']) ) {
    usage_and_exit("Config file not found: '" . $scriptOptions['config-file'] . "'");
}

// NOTE: "process_start_time" is needed for log summary.

if ( $scriptOptions['dryrun']) {
    $logger->notice("Running in DRYRUN mode");
}

// ------------------------------------------------------------------------------------------
// Parse the ETL configuration. We will need it for listing available ingestors, aggregators, etc.

try {
    $etlConfig = new EtlConfiguration($scriptOptions['config-file'],
                                      $scriptOptions['base-dir']);
    $etlConfig->setLogger($logger);
    $etlConfig->initialize();
    $etlConfig->cleanup();
} catch ( Exception $e ) {
    exit($e->getMessage() . "\n". $e->getTraceAsString() . "\n");
}

if ( false === ($ep = $etlConfig->getGlobalEndpoint($scriptOptions['endpoint'])) ) {
    exit("Unknown data endpoint: '{$scriptOptions['endpoint']}'\n");
}

if ( $scriptOptions['list'] ) {
    try {
        $list = StateManager::getList($ep, $logger);
    } catch ( Exception $e ) {
        exit($e->getMessage() . "\n");
    }

    if ( 0 == count($list) ) {
        print "No state objects found\n";
    } else {
        $headings = array(
            "Key",
            "Type",
            "Creating Action",
            "Creation Time",
            "Last Mod Action",
            "Modification Time",
            "Size (bytes)"
            );
        print implode(LIST_SEPARATOR, $headings) . "\n";

        foreach ( $list as $row ) {
            print implode(LIST_SEPARATOR, $row) . "\n";
        }
    }
}

if ( 0 != count($scriptOptions['delete-objects']) ) {
    foreach ( $scriptOptions['delete-objects'] as $key ) {
        $logger->info("Delete state with key $key");

        try {
            StateManager::delete($key, $ep, $logger);
        } catch ( Exception $e ) {
            exit($e->getMessage() . "\n");
        }
    }
}

if ( 0 != count($scriptOptions['info-objects']) ) {
    foreach ( $scriptOptions['info-objects'] as $key ) {
        try {
            $stateObj = StateManager::load($key, $ep, $logger);
        } catch ( Exception $e ) {
            exit($e->getMessage() . "\n");
        }
        if ( false === $stateObj ) {
            print "No state object with key $key\n";
        } else {
            print $stateObj;
        }
    }
}

exit(0);

// ==========================================================================================

/**
 * Display usage text and exit with error status.
 */

function usage_and_exit($msg = null)
{
    global $argv, $scriptOptions;

    if ($msg !== null) {
        fwrite(STDERR, "\n$msg\n\n");
    }

    $verbosityDefault = Log::NOTICE;

    fwrite(
        STDERR,
<<<"EOMSG"
Usage: {$argv[0]}

    -h, --help
    Display this help

    -b, --base-dir <directory>
    Base directory for configuration file sub-directories. If not spcecified, the directory of the configuration file will be used.

    -c, --config-file <file>
    ETL configuration file [default: {$scriptOptions['config-file']}]

    -d, --delete <key>
    Delete the action state object identified by <key>

    -t, --dryrun
    Perform all steps except execution of the actions. Useful for validating the configuration or displaying queries.

    -e, --endpoint <data_endpoint_name>
    Use <data_endpoint_name> to connect to the database [default: {$scriptOptions['endpoint']}]

    -i, --info <key>
    Display the contents of the action state object identified by <key>

    -l, --list
    List available actions in the specified section, resources, data endpoints, or sections. If a section name is provided list all actions in that section.

    -v, --verbosity {debug, info, notice, warning, quiet} [default: $verbosityDefault]
    Level of verbosity to output from the ETL process

EOMSG
        );

    exit(1);
}
?>
