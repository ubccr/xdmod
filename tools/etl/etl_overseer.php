#!/usr/bin/env php
<?php
/**
 * Perform ETL on federated resources.  This is different than the traditional ETL process in that
 * it uses a new mechanism for passing options to the ingesters and is (hopefully) more flexible.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 */

require __DIR__ . '/../../configuration/linker.php';
restore_exception_handler();

// Disable PHP's memory limit.
ini_set('memory_limit', -1);

// Character to use when separating list output
const LIST_SEPARATOR = "\t";

use CCR\Log;
use CCR\DB;
use ETL\EtlOverseer;
use ETL\iEtlOverseer;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\Utilities;

// ==========================================================================================
// Script options with defaults

// Allow initialization of some options from the configuration file

$scriptOptions = array(
    // List of individual actions to execute
    'actions'           => array(),
    // Base path for relative file locations
    'base-dir'          => null,
    // ETL configuration file
    'config-file'       => CONFIG_DIR . '/etl/etl.json',
    // Run in dryrun mode performing all operations except execution of the actions
    'dryrun'            => false,
    // ETL end date
    'end-date'          => null,
    // Force operation
    'force'             => false,
    // Groups to run
    'groups'            => array(),
    // Chunk size (in days) for breaking up the process
    'chunk-size-days'   => null,
    // Default module name if not specified in a configuration file
    'default-module-name' => null,
    // ETL last modified start date, used by some actions. Defaults to the start of the ETL process.
    'last-modified-start-date' => date('Y-m-d H:i:s'),
    // ETL last modified end date, used by some actions.
    'last-modified-end-date' => null,
    // List available actions
    'list-actions'      => false,
    // List available aggregators
    'list-aggregators'  => false,
    // List the available data endpoint types (e.g., classes)
    'list-endpoint-types'    => false,
    // List endpoints that have been configured
    'list-configured-endpoints'    => false,
    // List available ETL groups
    'list-groups'       => false,
    // List available Ingestors
    'list-ingestors'    => false,
    // List available resources
    'list-resources'    => false,
    // Directory for storing ETL lock files
    'lock-dir'          => null,
    // Optional ETL lock file prefix
    'lock-file-prefix'  => null,
    // Previous number of days to ingest
    'number-of-days'    => null,
    // List of options to add to or override individual action options
    'option-overrides'  => array(),
    // Act on only these resources (empty array is all resources)
    'include-only-resource-codes' => array(),
    // Exclude these resources (empty array excludes no resources)
    'exclude-resource-codes' => array(),
    // List of sections to process (e.g., ingestors, aggregators)
    'process-sections'  => array(),
    // SQL query to use when generating the resource code map
    'resource-code-map-sql' => null,
    // ETL start date
    'start-date'        => null,
    // Variables defined on the command line
    'variable-overrides' => array(),
    // Log verbosity
    'verbosity'         => Log::NOTICE
);

// Override defaults with values from the configuration file, if there are any

try {
    $etlConfigOptions = \xd_utilities\getConfigurationSection("etl");

    foreach ( $etlConfigOptions as $configKey => $configValue ) {

        // Allow options to be separated with underscores or dashes
        $dashKey = str_replace('_', '-', $configKey);

        switch ( $dashKey )
        {
            case 'lock-dir':
            case 'lock-file-prefix':
            case 'default-module-name':
                $scriptOptions[$dashKey] = $configValue;
                break;
            default:
                break;
        }

    }  // foreach ( $etlConfigOptions as $configkey => $configValue )

} catch ( Exception $e ) {
    // Simply ignore the exception if there is no [etl] section in the config file
}

$showList = false;

// ==========================================================================================
// Process command line arguments

$options = array(
    'h'   => 'help',
    'a:'  => 'action:',
    'b:'  => 'base-dir:',
    'c:'  => 'config-file:',
    'd:'  => 'define:',
    'e:'  => 'end-date:',
    'f'   => 'force',
    'g:'  => 'group:',
    'k:'  => 'chunk-size:',
    'l:'  => 'list:',
    'm:'  => 'last-modified-start-date:',
    'n:'  => 'number-of-days:',
    'o:'  => 'option:',
    'p:'  => 'process-section:',
    'r:'  => 'only-resource-codes:',
    's:'  => 'start-date:',
    't'   => 'dryrun',
    'v:'  => 'verbosity:',
    'x:'  => 'exclude-resource-codes:',
    'y:'  => 'last-modified-end-date:',
    ''    => 'lock-dir',
    ''    => 'lock-file-prefix'
    );

$args = getopt(implode('', array_keys($options)), $options);

foreach ($args as $arg => $value) {
    switch ($arg) {

        case 'a':
        case 'action':
            // Merge array because long and short options are grouped separately
            $scriptOptions['actions'] = array_merge(
                $scriptOptions['actions'],
                ( is_array($value) ? $value : array($value) )
            );
            break;

        case 'b':
        case 'base-dir':
            if ( ! is_dir($value) ) {
                usage_and_exit("Base directory does not exist: '$value'");
            }
            $scriptOptions['base-dir'] = $value;
            break;

        case 'k':
        case 'chunk-size':
            switch ( $value ) {
                case 'none':
                    $scriptOptions['chunk-size-days'] = null;
                    break;
                case 'day':
                    $scriptOptions['chunk-size-days'] = 1;
                    break;
                case 'week':
                    $scriptOptions['chunk-size-days'] = 7;
                    break;
                case 'month':
                    $scriptOptions['chunk-size-days'] = 30;
                    break;
                case 'quarter':
                    $scriptOptions['chunk-size-days'] = 91;
                    break;
                case 'year':
                    $scriptOptions['chunk-size-days'] = 365;
                    break;
                default:
                    usage_and_exit("Invalid chunk size: $value");
                    break;
            }
            break;

        case 'c':
        case 'config-file':
            $scriptOptions['config-file'] = $value;
            break;

        case 'd':
        case 'define':
            $value = ( is_array($value) ? $value : array($value) );
            foreach ( $value as $variable ) {
                $parts = explode("=", $variable);
                if ( 2 != count($parts) ) {
                    usage_and_exit("Variables must be of the form variable=value: '$variable'");
                }
                $scriptOptions['variable-overrides'][trim($parts[0])] = trim($parts[1]);
            }
            break;

        case 't':
        case 'dryrun':
            $scriptOptions['dryrun'] = true;
            break;

        case 'e':
        case 'end-date':
            if ( false === strtotime($value) ) {
                usage_and_exit("Could not parse end date: '$value'");
            }
            $scriptOptions['end-date'] = $value;
            break;

        case 'f':
        case 'force':
            $scriptOptions['force'] = true;
            break;

        case 'g':
        case 'group':
            $scriptOptions['groups'] = ( is_array($value) ? $value : array($value) );
            break;

        case 'l':
        case 'list':
            $showList = true;
            $value = ( is_array($value) ? $value : array($value) );
            foreach ( $value as $type ) {
                $key = "list-" . $type;
                $scriptOptions[$key] = true;
            }  // foreach ( $value as $type )
            break;

        case 'm':
        case 'last-modified-start-date':
            if ( false === strtotime($value) ) {
                usage_and_exit("Could not parse last modified start date: '$value'");
            }
            $scriptOptions['last-modified-start-date'] = $value;
            break;

        case 'n':
        case 'number-of-days':
            $scriptOptions['number-of-days'] = filter_var($value, FILTER_VALIDATE_INT);
            if ($scriptOptions['number-of-days'] < 1) {
                usage_and_exit("$arg must be an integer greater than 0");
            }
            break;

        case 'o':
        case 'option':
            $value = ( is_array($value) ? $value : array($value) );
            foreach ( $value as $option ) {
                $parts = explode("=", $option);
                if ( 2 != count($parts) ) {
                    usage_and_exit("Options must be of the form option=value: '$option'");
                }
                $scriptOptions['option-overrides'][trim($parts[0])] = trim($parts[1]);
            }
            break;

        case 'r':
        case 'only-resource-codes':
            // Merge array because long and short options are grouped separately
            $scriptOptions['include-only-resource-codes'] = array_merge(
                $scriptOptions['include-only-resource-codes'],
                ( is_array($value) ? $value : array($value) )
            );
            break;

        case 'p':
        case 'process-section':
            // Merge array because long and short options are grouped separately
            $scriptOptions['process-sections'] = array_merge(
                $scriptOptions['process-sections'],
                ( is_array($value) ? $value : array($value) )
            );
            break;

        case 's':
        case 'start-date':
            if ( false === strtotime($value) ) {
                usage_and_exit("Could not parse start date: '$value'");
            }
            $scriptOptions['start-date'] = $value;
            break;

        case 'v':
        case 'verbosity':
            switch ( $value ) {
                case 'trace':
                    $scriptOptions['verbosity'] = Log::TRACE;
                    break;
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

        case 'x':
        case 'exclude-resource-codes':
            // Merge array because long and short options are grouped separately
            $scriptOptions['exclude-resource-codes'] = array_merge(
                $scriptOptions['exclude-resource-codes'],
                ( is_array($value) ? $value : array($value) )
            );
            break;

        case 'y':
        case 'last-modified-end-date':
            if ( false === strtotime($value) ) {
                usage_and_exit("Could not parse last modified end date: '$value'");
            }
            $scriptOptions['last-modified-end-date'] = $value;
            break;

        case 'lock-dir':
            $scriptOptions['lock-dir'] = $value;
            break;

        case 'lock-preifx':
            $scriptOptions['lock-prefix'] = $value;
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
    } elseif ( 0 === strpos($arg, "-") ) {
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

if ( null !== $scriptOptions['verbosity'] ) {
    $conf['consoleLogLevel'] = $scriptOptions['verbosity'];
}

$logger = Log::factory('DWI', $conf);

$cmd = implode(' ', array_map('escapeshellarg', $argv));
$logger->info("Command: $cmd");

if ( null === $scriptOptions['config-file'] ) {
    usage_and_exit("Config file required");
} elseif ( null !== $scriptOptions['config-file'] && ! is_file($scriptOptions['config-file']) ) {
    usage_and_exit("Config file not found: '" . $scriptOptions['config-file'] . "'");
}

// NOTE: "process_start_time" is needed for log summary.

if ( $scriptOptions['dryrun']) {
    $logger->notice("Running in DRYRUN mode");
}

if ( ! $showList)  {
    $logger->notice(array(
        'message'            => 'dw_extract_transform_load start',
        'process_start_time' => date('Y-m-d H:i:s'),
    ));
}

try {
    $overseerOptions = new EtlOverseerOptions($scriptOptions, $logger);
} catch ( Exception $e ) {
    log_error_and_exit(
        sprintf("%s%s%s", $e->getMessage(), PHP_EOL, $e->getTraceAsString())
    );
}

// ------------------------------------------------------------------------------------------
// Parse the ETL configuration. We will need it for listing available ingestors, aggregators, etc.

try {
    $etlConfig = EtlConfiguration::factory(
        $scriptOptions['config-file'],
        $scriptOptions['base-dir'],
        $logger,
        array(
            'option_overrides'   => $scriptOptions['option-overrides'],
            'config_variables' => $scriptOptions['variable-overrides'],
            'default_module_name' => $scriptOptions['default-module-name']
        )
    );
    $etlConfig->setLogger($logger);
} catch ( Exception $e ) {
    log_error_and_exit(
        sprintf("%s%s%s", $e->getMessage(), PHP_EOL, $e->getTraceAsString())
    );
}

Utilities::setEtlConfig($etlConfig);

// ------------------------------------------------------------------------------------------
// List any requested resources. After listing, exit.

if ( false === ($utilityEndpoint = $etlConfig->getGlobalEndpoint('utility')) ) {
    log_error_and_exit(sprintf(
        "%s%s%s",
        "Global utility endpoint not defined, cannot query database for resource code mapping",
        PHP_EOL,
        $e->getTraceAsString()
    ));
}
$utilitySchema = $utilityEndpoint->getSchema();

$listOptions = array_filter(
    array_keys($scriptOptions),
    function ($key) {
        return "list-" == substr($key, 0, 5);
    }
);

if ( $showList ) {
    foreach ( $listOptions as $opt ) {
        if ( ! $scriptOptions[$opt] ) {
            continue;
        }
        switch ( $opt ) {

            case 'list-resources':
                $sql = "SELECT code, start_date, end_date from {$utilitySchema}.resourcefact WHERE resourcetype_id NOT IN (0,4) ORDER BY CODE ASC";
                try {
                    $result = $utilityEndpoint->getHandle()->query($sql);
                } catch (Exception $e) {
                    log_error_and_exit(
                        sprintf("%s%s%s", $e->getMessage(), PHP_EOL, $e->getTraceAsString())
                    );
                }
                $headings = array("Resource Code","Start Date","End Date");
                print implode(LIST_SEPARATOR, $headings) . "\n";

                foreach ( $result as $row ) {
                    $fields = array($row['code'], $row['start_date'], $row['end_date']);
                    print implode(LIST_SEPARATOR, $fields) . "\n";
                }
                break;

            case 'list-sections':
                $sectionNames = $etlConfig->getSectionNames();
                sort($sectionNames);
                print "Section\n";
                foreach ( $sectionNames as $name ) {
                    print "$name\n";
                }
                break;

            case 'list-actions':
                $sectionNames = $etlConfig->getSectionNames();
                sort($sectionNames);
                $headings = array("Action","Status","Description");
                print implode(LIST_SEPARATOR, $headings) . "\n";

                foreach ( $sectionNames as $sectionName ) {
                    $actions = $etlConfig->getConfiguredActionNames($sectionName);
                    foreach ( $actions as $actionName ) {
                        $options = $etlConfig->getActionOptions($actionName, $sectionName);
                        $fields = array($actionName, ( $options->enabled ? "enabled" : "disabled"), $options->description);
                        print implode(LIST_SEPARATOR, $fields) . "\n";
                    }
                }
                break;

            case 'list-endpoint-types':
                \ETL\DataEndpoint::discover(false, $logger);
                $endpointInfo = \ETL\DataEndpoint::getDataEndpointInfo(false, $logger);
                $headings = array("Name", "Class");
                print implode(LIST_SEPARATOR, $headings) . "\n";
                ksort($endpointInfo);
                foreach ( $endpointInfo as $name => $class) {
                    print "$name\t$class\n";
                }
                break;

            case 'list-configured-endpoints':
                $endpoints = $etlConfig->getDataEndpoints();

                $endpointSummary = array();
                foreach ( $endpoints as $endpoint ) {
                    $endpointSummary[0][] = $endpoint->getType();
                    $endpointSummary[1][] = $endpoint->getName();
                    $endpointSummary[2][] = $endpoint->getKey();
                    $endpointSummary[3][] = (string) $endpoint;

                }
                array_multisort($endpointSummary[0], $endpointSummary[1], $endpointSummary[2], $endpointSummary[3]);

                $headings = array("Type","Name","Key","Description");
                print implode(LIST_SEPARATOR, $headings) . "\n";

                for ($i=0; $i < count($endpointSummary[0]); $i++) {
                    $a = array(
                        $endpointSummary[0][$i],
                        $endpointSummary[1][$i],
                        $endpointSummary[2][$i],
                        $endpointSummary[3][$i]
                    );
                    print implode(LIST_SEPARATOR, $a) . "\n";
                }
                break;

            case 'list-groups':
                // Groups are not supported yet.
                break;

            default:
                // Remove the "list-" prefix to get the section name
                $sectionName = substr($opt, 5);
                if ( false !== ($actions = $etlConfig->getConfiguredActionNames($sectionName)) ) {
                    $headings = array("Action","Status","Description");
                    print implode(LIST_SEPARATOR, $headings) . "\n";
                    foreach ( $actions as $actionName ) {
                        $options = $etlConfig->getActionOptions($actionName, $sectionName);
                        $fields = array($actionName, $sectionName, ( $options->enabled ? "enabled" : "disabled"), $options->description);
                        print implode(LIST_SEPARATOR, $fields) . "\n";
                    }
                } else {
                    print "Unknown section name: '$sectionName'\n";
                }
                break;
        }
    }
    exit(0);
}  // if ( $showList )

// Set the SQL query used to look up resource ids and generate the mapping for resource codes to ids.

$overseerOptions->setResourceCodeToIdMapSql(sprintf("SELECT id, code from %s.resourcefact", $utilitySchema));

// If nothing was requested, exit.

if ( count($scriptOptions['process-sections']) == 0 &&
     count($scriptOptions['actions']) == 0 ) {
    $logger->notice("No actions or sections requested, exiting.");
    $logger->notice(array('message'          => 'dw_extract_transform_load end',
                          'process_end_time' => date('Y-m-d H:i:s') ));
    exit(0);
}

// ------------------------------------------------------------------------------------------
// Create the overseer and perform the requested operations for each date interval

$overseer = new EtlOverseer($overseerOptions, $logger);
if ( ! ($overseer instanceof iEtlOverseer ) )
{
    log_error_and_exit(
        sprintf("EtlOverseer (%s) is not an instance of iEtlOverseer", get_class($overseer))
    );
}

try {
    $overseer->execute($etlConfig);
} catch ( Exception $e ) {
    log_error_and_exit(
        sprintf("%s%s%s", $e->getMessage(), PHP_EOL, $e->getTraceAsString())
    );
}

// NOTE: "process_end_time" is needed for log summary."

$logger->notice(array('message'          => 'dw_extract_transform_load end',
                      'process_end_time' => date('Y-m-d H:i:s') ));

exit(0);

// ==========================================================================================

/**
 * Log an error message and exit with a status indicating an error.
 */

function log_error_and_exit($msg)
{
    global $logger;
    $logger->err($msg);
    fwrite(STDERR, $msg . PHP_EOL);
    exit(1);
}  // log_error_and_exit()

/**
 * Display usage text and exit with error status.
 */

function usage_and_exit($msg = null)
{
    global $argv, $scriptOptions;

    if ($msg !== null) {
        fwrite(STDERR, "\n$msg\n\n");
    }

    $chunkDefault     = $scriptOptions['chunk-size-days'];
    $verbosityDefault = Log::NOTICE;

    fwrite(
        STDERR,
        <<<"EOMSG"
Usage: {$argv[0]}

    -h, --help
    Display this help

    -a, --action <action_name>
    Specify an individual action to execute. May be used multiple times. To disambiguate action names found in multiple sections, use "section:action".

    -b, --base-dir <directory>
    Base directory for configuration file sub-directories. If not spcecified, the directory of the configuration file will be used.

    -c, --config-file <file>
    ETL configuration file [default {$scriptOptions['config-file']}]

    -d, --define <variable>=<value>
    Define an ETL variable that will be set for all actions, possibly overriding existing values.  Note that variable names are case sensitive.

    -t, --dryrun
    Perform all steps except execution of the actions. Useful for validating the configuration or displaying queries.

    -e, --end-date <date>
    End date of the ETL period. Supports relative time formats (see below)

    -f, --force
    Force ingestion/aggregation even if the data has already been processed. Job end-date is used to identify jobs.

    -g, --group
    Process the specified ETL group. May use multiple times.

    -k, --chunk-size {none, day, week, month, quarter, year}
    Break up ingestion into chunks of this size. Helps to make more recent data available faster. [default year]

    -l, --list {resources, sections, actions, endpoint-types, configured-endpoints} | <etl_section_name>
    List available actions in the specified section, resources, data endpoints, or sections. If a section name is provided list all actions in that section.

    -m, --last-modified-start-date
    ETL last modified start date, used by some actions. Defaults to the start of the ETL process (e.g., "now").

    -n, --number-of-days
    Number of days that the action will operate on.

    -o, --option "<tag>=<value>"
    Add or override a top-level configuration option FOR ALL ACTIONS. May be used multiple times. This has the effect of directly modifying the action configuration.

    -p, --process-section
    Specify a configured section to process. May be used multiple times.

    -r, --only-resource-code <code>
    Include only this resource code during action execution. May be used multiple times, default is all resources.

    -s, --start-date <date>
    Start date of the ETL period. Supports relative time formats (see below).

    -v, --verbosity {debug, info, notice, warning, quiet} [default $verbosityDefault]
    Level of verbosity to output from the ETL process

    -x, --exclude-resource-code <code>
    Exclude this resource code from action execution. May be used multiple times, default is no exclusions.

    -y, --last-modified-end-date
    ETL last modified end date, used by some actions.

    NOTE: Date and time options support relative notation such as "+1 day", "now", "now - 1 week", "now -10 days 00:00:00", etc.

EOMSG
    );

    exit(1);
}
?>
