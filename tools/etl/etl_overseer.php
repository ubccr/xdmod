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

// Character to use when separating list output
const LIST_SEPARATOR = "\t";

use \Exception;
use CCR\Log;
use CCR\DB;
use ETL\EtlOverseer;
use ETL\iEtlOverseer;
use ETL\EtlConfiguration;
use ETL\EtlConfigurationOptions;
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
    'config-file'       => null,
    // Run in dryrun mode performing all operations except execution of the actions
    'dryrun'            => false,
    // ETL end date
    'end-date'          => null,
    // Force operation
    'force'             => false,
    // Groups to run
    'groups'            => array(),
    // Chunk size (in days) for breaking up the process
    'chunk-size-days'   => 365,
    // ETL last modified start date, used by some actions. Defaults to the start of the ETL process.
    'last-modified-start-date' => date('Y-m-d H:i:s'),
    // ETL last modified end date, used by some actions.
    'last-modified-end-date' => null,
    // List available actions
    'list-actions'      => false,
    // List available aggregators
    'list-aggregators'  => false,
    // List available data endpoints
    'list-endpoints'    => false,
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
    // ETL start date
    'start-date'        => null,
    'verbosity'         => Log::NOTICE
);

// Override defaults with values from the configuration file, if there are any

try {
    $etlConfigOptions = \xd_utilities\getConfigurationSection("etl");

    foreach ( $etlConfigOptions as $configKey => $configValue ) {

        // Allow options to be separated with underscores or dashes
        $dashKey = str_replace('_', '-', $configKey);

        if ( array_key_exists($configKey, $scriptOptions) ) {
            $scriptOptions[$configKey] = $configValue;
        } elseif ( array_key_exists($dashKey, $scriptOptions) ) {
            $scriptOptions[$dashKey] = $configValue;
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
        case 'chunk-size-days':
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

if ( ! $showList &&
     ( null === $scriptOptions['start-date'] &&
       null === $scriptOptions['end-date'] &&
       null === $scriptOptions['number-of-days']) )
{
    usage_and_exit("Must provide start/end date or number of days");
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

// ------------------------------------------------------------------------------------------
// Parse the ETL configuration. We will need it for listing available ingestors, aggregators, etc.

try {
    $etlConfig = new EtlConfiguration(
        $scriptOptions['config-file'],
        $scriptOptions['base-dir'],
        $scriptOptions['option-overrides']
    );
    $etlConfig->setLogger($logger);
    $etlConfig->initialize();
} catch ( Exception $e ) {
    exit($e->getMessage() . "\n". $e->getTraceAsString() . "\n");
}

Utilities::setEtlConfig($etlConfig);

if ( Log::DEBUG == $scriptOptions['verbosity'] ) {
    // print_r($etlConfig);
}

// ------------------------------------------------------------------------------------------
// Verify requested actions and sections

if ( count($scriptOptions['process-sections']) > 0 ) {

    $missing = array();

    foreach ( $scriptOptions['process-sections'] as $sectionName ) {
        if ( ! $etlConfig->sectionExists($sectionName) ) {
            $missing[] = $sectionName;
        }
    }

    if ( count($missing) > 0 ) {
        fwrite(STDERR, "Unknown sections: " . implode(", ", $missing) . "\n");
        exit();
    }
}  // if ( count($scriptOptions['process-sections'] > 0) )

// ------------------------------------------------------------------------------------------
// List any requested resources. After listing, exit.

if ( false === ($utilityEndpoint = $etlConfig->getGlobalEndpoint('utility')) ) {
    $msg = "Global utility endpoint not defined, cannot query database for resource code mapping";
    exit("$msg\n". $e->getTraceAsString() . "\n");
    throw new Exception($msg);
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
                    exit($e->getMessage() . "\n". $e->getTraceAsString() . "\n");
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
                $headings = array("Section","Action","Status","Description");
                print implode(LIST_SEPARATOR, $headings) . "\n";

                foreach ( $sectionNames as $sectionName ) {
                    $actions = $etlConfig->getConfiguredActionNames($sectionName);
                    foreach ( $actions as $actionName ) {
                        $options = $etlConfig->getActionOptions($actionName, $sectionName);
                        $fields = array($sectionName, $actionName, ( $options->enabled ? "enabled" : "disabled"), $options->description);
                        print implode(LIST_SEPARATOR, $fields) . "\n";
                    }
                }
                break;

            case 'list-endpoints':
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

                for ( $i=0; $i < count($endpointSummary[0]); $i++ ) {
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
    exit();
}  // if ( $showList )

// ------------------------------------------------------------------------------------------
// Calculate start & end dates. Using the -s and -e flags take precedence over the -n flag.

if ( null !== $scriptOptions['start-date'] || null !== $scriptOptions['end-date'] ) {

    if ( null === $scriptOptions['end-date'] ) {
        // If there is no end date, assume today
        $scriptOptions['end-date'] = date("Y-m-d 23:59:59");
        $logger->info("No end date set, assuming " . $scriptOptions['end-date']);
    } elseif ( null === $scriptOptions['start-date'] ) {
        // If no start date assume epoch (1970-01-01 00:00:00) in the current timezone
        $scriptOptions['start-date'] = "1970-01-01 00:00:00";
        $logger->info("No start date set, assuming " . $scriptOptions['start-date']);
    }

    // If a time was not provided along with the start or end dates (i.e., it does not fall on the
    // 86400 seconds in a day boundary) assume that we will use the start and end of the day,
    // respectively.  We must use UTC because we don't care about timezones in this calculation.

    if ( 0 == (strtotime($scriptOptions['start-date'] . " UTC") % 86400)
         && '00:00:00' != substr($scriptOptions['start-date'], -8) )
    {
        $scriptOptions['start-date'] .= " 00:00:00";
    }

    if ( 0 == (strtotime($scriptOptions['end-date'] . " UTC") % 86400) ) {
        $scriptOptions['end-date'] .= " 23:59:59";
    }

} else {

    // If start/end dates were not provided us the number of days. Note that the current day is
    // considered the first day so subtract 1.

    $today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

    $scriptOptions['start-date'] = date("Y-m-d 00:00:00", $today - (86400 * ($scriptOptions['number-of-days'] - 1)));
    $scriptOptions['end-date']   = date("Y-m-d 23:59:59");

}  // else ($scriptOptions['start-date'] || $scriptOptions['end-date'] )

// ------------------------------------------------------------------------------------------
// Look up resource ids and generate the mapping for resource codes to ids. This can be stored in
// the overseer and used by actions if needed.

try {
    $result = $utilityEndpoint->getHandle()->query("SELECT id, code from {$utilitySchema}.resourcefact");
} catch (Exception $e) {
    exit($e->getMessage() . "\n". $e->getTraceAsString() . "\n");
}
$scriptOptions['resource-code-map'] = array();

foreach ( $result as $row ) {
    $scriptOptions['resource-code-map'][ $row['code'] ] = $row['id'];
}

try {
    $overseerOptions = new EtlOverseerOptions($scriptOptions, $logger);
} catch ( Exception $e ) {
    exit($e->getMessage() . "\n". $e->getTraceAsString() . "\n");
}

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
    $msg = "EtlOverseer is not an instance of iEtlOverseer";
    exit($msg);
}

try {
    $logger->notice(array('message'         => 'ETL time period',
                          'data_start_time' => $overseerOptions->getStartDate(),
                          'data_end_time'   => $overseerOptions->getEndDate()));
    $overseer->execute($etlConfig);
} catch ( Exception $e ) {
    exit($e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
}

// NOTE: "process_end_time" is needed for log summary."

$logger->notice(array('message'          => 'dw_extract_transform_load end',
                      'process_end_time' => date('Y-m-d H:i:s') ));

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

    -t, --dryrun
    Perform all steps except execution of the actions. Useful for validating the configuration or displaying queries.

    -e, --end-date <date>
    End date of the ETL period. Overrides --number-of-days.

    -f, --force
    Force ingestion/aggregation even if the data has already been processed. Job end-date is used to identify jobs.

    -g, --group
    Process the specified ETL group. May use multiple times.

    -k, --chunk-size {none, day, week, month, year}
    Break up ingestion into chunks of this size. Helps to make more recent data available faster. [default year]

    -l, --list {resources, sections, actions, endpoints} | <etl_section_name>
    List available actions in the specified section, resources, data endpoints, or sections. If a section name is provided list all actions in that section.

    -m, --last-modified-start-date
    ETL last modified start date, used by some actions. Defaults to the start of the ETL process (e.g., "now").

    -n, --number-of-days
    Days to ingest from the source (the current day is included)

    -o, --option "<tag>=<value>"
    Add or override a top-level configuration option FOR ALL ACTIONS. May be used multiple times. This has the effect of directly modifying the action configuration.

    -p, --process-section
    Specify a configured section to process. May be used multiple times.

    -r, --only-resource-code <code>
    Include only this resource code during action execution. May be used multiple times, default is all resources.

    -s, --start-date <date>
    Start date of the ETL period. Overrides --number-of-days

    -v, --verbosity {debug, info, notice, warning, quiet} [default $verbosityDefault]
    Level of verbosity to output from the ETL process

    -x, --exclude-resource-code <code>
    Exclude this resource code from action execution. May be used multiple times, default is no exclusions.

    -y, --last-modified-end-date
    ETL last modified end date, used by some actions.

    NOTE: Date and time options support "+1 day", "now", "now - 1 day", etc. notation.

EOMSG
    );

    exit(1);
}
?>
