#!/usr/bin/env php
<?php
/**
 * Saved metric explorer charts are stored in each user's profile. The profile is stored as a
 * serialized PHP object with a 'queries_store' property containing a 'data' array that stores
 * objects describing each chart. The 'data' array contains the chart name and a 'config'
 * property containing an encoded JSON representation of the chart configuration. For example:
 *
 * {
 *   'queries_store' => {
 *     'data' => [
 *       {
 *         'name' => 'my chart',
 *         'config' => '{"featured":false,"trend_line":false, ...'
 *       }
 *     ]
 *   }
 * }
 *
 * This tool provides a means to manipulate individual chart configurations and save them
 * back to a user's profile.
 */

// Process arguments

$scriptOptions = array(
    'destination' => null,
    'dryrun' => false,
    'legend-orig' => null,
    'legend-new' => null,
    'list' => null,
    'operation' => null,
    'source' => null,
    'user' => null,
    'verbose' => false,
    'xdmod-root' => '/usr/share/xdmod/'
);

$supportedOperations = array(
    'copy-filters',
    'force-update-legend'
);

$options = array(
    'a:' => 'legend-orig:',
    'b:' => 'legend-new:',
    'd:' => 'destination:',
    'h'  => 'help',
    'l:' => 'list:',
    'o:' => 'operation:',
    'r:' => 'xdmod-root:',
    's:' => 'source:',
    't'  => 'dry-run',
    'u:' => 'user:',
    'v'  => 'verbose'
);

$args = getopt(implode('', array_keys($options)), $options);

foreach ($args as $arg => $value) {
    switch ($arg) {
        case 'a':
        case 'legend-orig':
            $scriptOptions['legend-orig'] = $value;
            break;

        case 'b':
        case 'legend-new':
            $scriptOptions['legend-new'] = $value;
            break;

        case 'd':
        case 'destination':
            $scriptOptions['destination'] = $value;
            break;

        case 'h':
        case 'help':
            usage_and_exit();
            break;

        case 'l':
        case 'list':
            $scriptOptions['list'] = $value;
            break;

        case 'o':
        case 'operation':
            $scriptOptions['operation'] = $value;
            break;

        case 'r':
        case 'xdmod-root':
            $scriptOptions['xdmod-root'] = $value;
            break;

        case 's':
        case 'source':
            $scriptOptions['source'] = $value;
            break;

        case 't':
        case 'dry-run':
            $scriptOptions['dryrun'] = true;
            break;

        case 'u':
        case 'user':
            $scriptOptions['user'] = $value;
            break;

        case 'v':
        case 'verbose':
            $scriptOptions['verbose'] = true;
            break;


        default:
            usage_and_exit("Invalid option: $arg");
            break;
    }
}  // foreach ($args as $arg => $value)

if ( null === $scriptOptions['user'] ) {
    usage_and_exit("Must specify a user" . PHP_EOL);
}

if ( ! is_dir($scriptOptions['xdmod-root']) ) {
    usage_and_exit(sprintf("xdmod-root is not a directory: '%s'", $scriptOptions['xdmod-root']) . PHP_EOL);
}

$linker = $scriptOptions['xdmod-root'] . DIRECTORY_SEPARATOR . 'share/configuration/linker.php';

if ( ! is_readable($linker) ) {
    usage_and_exit(sprinf("XDMoD linker not readable: '%s'", $linker) . PHP_EOL);
}

require_once $linker;

if ( $scriptOptions['dryrun'] ) {
    print "Running in DRYRUN mode" . PHP_EOL;
}

// --------------------------------------------------------------------------------
// Verify user

if ( $scriptOptions['verbose'] ) {
    print sprintf("Search user profiles for user '%s'", $scriptOptions['user']) . PHP_EOL;
}

if ( INVALID === ($uid = XDUser::userExistsWithUsername($scriptOptions['user'])) ) {
    usage_and_exit(sprintf("User not found: '%s'", $scriptOptions['user']) . PHP_EOL);
}

// --------------------------------------------------------------------------------
// Display saved charts for user

$user = XDUser::getUserByID($uid);
$userProfile = $user->getProfile();

$charts = $userProfile->fetchValue('queries_store');

if ( ! isset($charts['data']) ) {
    print sprintf("No saved charts found for user: '%s'", $scriptOptions['user']) . PHP_EOL;
    exit(0);
}

if ( $scriptOptions['verbose'] ) {
    print sprintf("Found %d saved charts", count($charts['data'])) . PHP_EOL;
}

$chartInfoList = array();

// Loop over all of the charts and extract any information that we will need to perform operations.
// Wile we are looping, display any requested information.

foreach ( $charts['data'] as $chartIndex => $chart ) {
    if ( ! isset($chart['name']) ) {
        print sprintf("Chart name not set for index %s, skipping.", $chartIndex) . PHP_EOL;
    }

    // The chart properties are stored as encoded JSON

    $config = json_decode($chart['config'], true);

    // Store the chart and series indexes for verification during operations

    $chartInfoList[$chartIndex] = array(
        'name'    => $chart['name'],
        'config'  => $config,
        'series'  => array()
    );

    foreach ( $config['data_series']['data'] as $seriesIndex => $series ) {
        $chartInfoList[$chartIndex]['series'][$seriesIndex] = generateSeriesName($series);
    }

    if ( isset($scriptOptions['list']) ) {

        // If a chart id was provided restrict operations to that chart

        if ( isset($scriptOptions['source']) && $chartIndex != $scriptOptions['source'] ) {
            continue;
        }

        switch ( $scriptOptions['list'] ) {
            case 'chart-names':
                print sprintf("chart[%d] %s", $chartIndex, $chart['name']) . PHP_EOL;
                break;
            case 'series':
                foreach ( $config['data_series']['data'] as $index => $series ) {
                    print sprintf(
                        "chart[%d] %s series[%d] %s",
                        $chartIndex,
                        $chart['name'],
                        $index,
                        generateSeriesName($series)
                    ) . PHP_EOL;
                }
                break;
            case 'global-filters':
                foreach ( $config['global_filters']['data'] as $filterIndex => $filter ) {
                    print sprintf(
                        "chart[%d] %s global-filter[%d] %s",
                        $chartIndex,
                        $chart['name'],
                        $filterIndex,
                        generateFilterName($filter)
                    ) . PHP_EOL;
                }
                break;
            case 'local-filters':
                foreach ( $config['data_series']['data'] as $seriesIndex => $series ) {
                    $seriesName = generateSeriesName($series);
                    foreach ( $series['filters']['data'] as $filterIndex => $filter ) {
                        print sprintf(
                            "chart[%d] %s series[%d] %s local-filter[%d] %s",
                            $chartIndex,
                            $chart['name'],
                            $seriesIndex,
                            $seriesName,
                            $filterIndex,
                            generateFilterName($filter)
                        ) . PHP_EOL;
                    }
                }
                break;
            default:
                usage_and_exit(sprintf("Unsupported option for --list: '%s'", $scriptOptions['list']));
                break;
        }
    }
}

// Perform operations

if ( isset($scriptOptions['operation']) ) {

    if ( ! in_array($scriptOptions['operation'], $supportedOperations) ) {
        usage_and_exit(sprintf("Unsupported operation: %s", $scriptOptions['operation']));
    }

    $saveChart = false;
    $deestChartConfig = null;

    switch ( $scriptOptions['operation'] ) {
        case 'force-update-legend':
            if ( ! isset($scriptOptions['destination']) ) {
                usage_and_exit(sprintf("Operation %s requires destination", $scriptOptions['operation']));
            }

            if ( ! isset($scriptOptions['legend-orig']) || ! isset($scriptOptions['legend-new']) ) {
                usage_and_exit(sprintf("Operation %s requires legend-orig and legend-new options to be specified", $scriptOptions['operation']));
            }

            $destParts = explode(',', $scriptOptions['destination']);
            $destChartId = array_shift($destParts);
            $destSeriesId = ( count($destParts) > 0 ? array_shift($destParts) : null );

            // Validate the chart

            if ( ! array_key_exists($destChartId, $chartInfoList) ) {
                usage_and_exit(sprintf("Unknown destination chart id: %s", $destChartId));
            }

            $destChartConfig = $chartInfoList[$destChartId]['config'];
            $updatedLegend = false;

            // The legend is stored as an associative array where the key is the original
            // legend and the value is an object with the value of the 'title' property as
            // the new legend. If no legends have been modified, the original legend will
            // not be present in the array.

            foreach ( $destChartConfig['legend'] as $origLegend => &$modifiedLegend ) {
                if ( $origLegend == $scriptOptions['legend-orig'] ) {
                    $modifiedLegend['title'] = $scriptOptions['legend-new'];
                    $updatedLegend = true;
                    if ( $scriptOptions['verbose'] ) {
                        print sprintf(
                            "Updating existing legend override '%s' => '%s'",
                            $scriptOptions['legend-orig'],
                            $scriptOptions['legend-new']
                        ) . PHP_EOL;
                    }
                    break;
                }
            }

            if ( ! $updatedLegend ) {
                $destChartConfig['legend'][ $scriptOptions['legend-orig'] ] = array(
                    'title' => $scriptOptions['legend-new']
                );
                if ( $scriptOptions['verbose'] ) {
                    print sprintf(
                        "Adding new legend override '%s' => '%s'",
                        $scriptOptions['legend-orig'],
                        $scriptOptions['legend-new']
                    ) . PHP_EOL;
                }
            }

            $saveChart = true;
            break;

        case 'copy-filters':
            if ( ! isset($scriptOptions['source']) || ! isset($scriptOptions['destination']) ) {
                usage_and_exit(sprintf("Operation %s requires source and destination", $scriptOptions['operation']));
            }

            // The source and destination are expected to be in the following format. If no series
            // was provided assume global filters: <chart_id>[,<series_id>]

            $sourceParts = explode(',', $scriptOptions['source']);
            $sourceChartId = array_shift($sourceParts);
            $sourceSeriesId = ( count($sourceParts) > 0 ? array_shift($sourceParts) : null );

            $destParts = explode(',', $scriptOptions['destination']);
            $destChartId = array_shift($destParts);
            $destSeriesId = ( count($destParts) > 0 ? array_shift($destParts) : null );

            // Validate the chart

            if ( ! array_key_exists($sourceChartId, $chartInfoList) ) {
                usage_and_exit(sprintf("Unknown source chart id: %s", $sourceChartId));
            } elseif ( ! array_key_exists($destChartId, $chartInfoList) ) {
                usage_and_exit(sprintf("Unknown destination chart id: %s", $destChartId));
            }

            $sourceChartInfo = $chartInfoList[$sourceChartId];
            $sourceChartConfig = $chartInfoList[$sourceChartId]['config'];
            $destChartInfo = $chartInfoList[$destChartId];
            $destChartConfig = $chartInfoList[$destChartId]['config'];

            if ( $scriptOptions['verbose'] ) {
                print sprintf("Source chart name: '%s'", $sourceChartInfo['name']) . PHP_EOL;
                print sprintf("Destination chart name: '%s'", $destChartInfo['name']) . PHP_EOL;
            }

            // Validate the series if it has been provided. If no series was provided assume global filters.

            if ( isset($sourceSeriesId) && ! array_key_exists($sourceSeriesId, $sourceChartInfo['series']) ) {
                usage_and_exit(sprintf("Unknown source series id %s for chart id %s", $sourceSeriesId, $sourceChartId));
            } elseif ( isset($destSeriesId) && ! array_key_exists($destSeriesId, $destChartInfo['series']) ) {
                usage_and_exit(sprintf("Unknown destination series id %s for chart id %s", $destSeriesId, $destChartId));
            }

            $sourceFilterList = (
                isset($sourceSeriesId)
                ? $sourceChartConfig['data_series']['data'][$sourceSeriesId]['filters']['data']
                : $sourceChartConfig['global_filters']['data']
            );

            $destFilterList = (
                isset($destSeriesId)
                ? $destChartConfig['data_series']['data'][$destSeriesId]['filters']['data']
                : $destChartConfig['global_filters']['data']
            );

            // Only add filters that don't already exist in the destination

            $filtersToAdd = array_udiff(
                $sourceFilterList,
                $destFilterList,
                function (array $a, array $b) {
                    // If your compare function is not really comparing (ie. returns 0 if elements
                    // are equals, 1 otherwise), you will receive an unexpected result.
                    return strcmp(generateFilterName($a), generateFilterName($b));
                }
            );

            if ( $scriptOptions['verbose'] && count($filtersToAdd) > 0 ) {
                $filterNames = array_reduce(
                    $filtersToAdd,
                    function ($carry, array $filter) {
                        return $carry . PHP_EOL . generateFilterName($filter);
                    },
                    ''
                );
                print sprintf("Adding %d filters:%s", count($filtersToAdd), $filterNames) . PHP_EOL;
            }

            if ( 0 == count($filtersToAdd) ) {
                if ( $scriptOptions['verbose'] ) {
                    print "No new filters to add." . PHP_EOL;
                }
            } else {
                // Merge the filters into the destination and save the user's profile

                $destFilterList = array_merge($destFilterList, $filtersToAdd);

                // Now save the filter back to the user profile.  We will need to json_encode() the data.

                // Set the filters in the chart object. If a series has been specified update the local filters
                // in that series, otherwise update the global filters.

                if ( isset($destSeriesId) ) {
                    if ( $scriptOptions['verbose'] ) {
                        print sprintf(
                            "Updating filters for chart '%s' series '%s'.",
                            $destChartInfo['name'],
                            $destChartInfo['series'][$destSeriesId]
                        ) . PHP_EOL;
                    }
                    $destChartConfig['data_series']['data'][$destSeriesId]['filters']['data'] = $destFilterList;
                    $destChartConfig['data_series']['data'][$destSeriesId]['filters']['total'] = count($destFilterList);
                } else {
                    if ( $scriptOptions['verbose'] ) {
                        print sprintf(
                            "Updating global filters for chart '%s'.",
                            $destChartInfo['name']
                        ) . PHP_EOL;
                    }
                    $destChartConfig['global_filters']['data'] = $destFilterList;
                    $destChartConfig['global_filters']['total'] = count($destFilterList);
                }

                if ( $scriptOptions['verbose'] ) {
                    print "Destination filters:" . PHP_EOL;
                    foreach ($destFilterList as $filterIndex => $filter) {
                        print sprintf(
                            "filter[%d] %s",
                            $filterIndex,
                            generateFilterName($filter)
                        ) . PHP_EOL;
                    }
                }

                // Update the data in the chart

                $saveChart = true;
            }  // if ( count($filtersToAdd) > 0 )
            break;
        default:
            break;
    }

    if ( $saveChart &&  ! $scriptOptions['dryrun'] ) {
        if ( null === $destChartConfig) {
            fwrite(STDERR, "Destination chart config is NULL, nothing to save");
            exit(1);
        }
        $charts['data'][$destChartId]['config'] = json_encode($destChartConfig);
        $userProfile->setValue('queries_store', $charts);
        try {
            $userProfile->save();
            print "Saved." . PHP_EOL;
        } catch (Exception $e) {
            fwrite(
                STDERR,
                sprintf("Error saving profile for user %s: %s", $scriptOptions['user'], $e->getMessage())
            );
            exit(1);
        }
    }
}

exit(0);

/**
 * Display usage text and exit with error status.
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

        -a <text>, --legend-orig <text>
        When modifying a legend, this is the text of the original legend.

        -b <text>, --legend-new <text>
        When modifying a legend, this is the text of the new legend.

        -d <chart_id>[,<series_id>], --destination <chart_id>[,<series_id>]
        The destination for the specified operation. Only used if an operation has been specified.
        If only a chart identifier has been provided then global options for that chart will be
        used as the destination. If a series has been provided then options for that chart series
        will be used as the destination. The identifiers can be obtained using the --list option.

        -l <item>, --list <item>
        Display a list of the various items. If a source chart id was specified list only values
        for that chart.
        Supported items are: chart-names, series, global-filters, local-filters

        -o <operation>, --operation <operation>
        Operation to perform. Supported operations are:
        copy-filters: Copy the filters from the source to the destination chart
        force-update-legend: Force a legend in the destination chart to be updated

        -r <path>, --xdmod-root <path>
        The XDMoD root path where we will find share/configuration/linker.php

        -s <chart_id>[,<series_id>], --source <chart_id>[,<series_id>]
        The source for the specified operation. Only used if an operation has been specified.
        If only a chart identifier has been provided then global options for that chart will be
        used as the source. If a series has been provided then options for that chart series will
        be used as the source. The identifiers can be obtained using the --list option.

        -t, --dry-run
        Operate in DRYRUN mode and do not modify the database.

        -u <username>, --user <username>
        The user whose profile will be loaded.

        -v, --verbose
        Provide verbose output.

EOMSG
    );

    exit(1);

}

/**
 * Generate a series name.
 *
 * @param array $series An associative array based on the decoded JSON object representing a chart series.
 *
 * @return string The series name.
 */

function generateSeriesName(array $series)
{
    return sprintf("%s: %s by %s", $series['realm'], $series['metric'], $series['group_by']);
}

/**
 * Generate a filter name.
 *
 * @param array $filter An associative array based on the decoded JSON object representing a chart filter.
 *
 * @return string The filter name.
 */

function generateFilterName(array $filter)
{
    return sprintf("(%s) %s = %s", $filter['id'], $filter['dimension_id'], $filter['value_name']);
}
