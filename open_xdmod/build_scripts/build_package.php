#!/usr/bin/env php
<?php
/**
 * Build the Open XDMoD distribution tarball.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

require_once __DIR__ . '/../../configuration/linker.php';

ini_set('memory_limit', -1);

use CCR\Logging;
use Monolog\Logger;
use OpenXdmod\Build\Packager;
use Psr\Log\LoggerInterface;

// Catch unexpected exceptions.
try {
    main();
    exit;
} catch (Exception $e) {
    $logger = getLogger();
    $logger->err($e->getMessage());
    $logger->err($e->getTraceAsString());
    exit(1);
}

/**
 * Main function.
 */
function main()
{
    global $argv, $debug, $config, $logger;

    $opts = array(
        array('h', 'help'),

        // Logging options.
        array('q', 'quiet'),
        array('v', 'verbose'),
        array('',  'debug'),

        array('',  'module:'),

        array('',  'run-tests'),
        array('',  'clone'),
        array('',  'branch:'),
        array('',  'skip-assets'),
    );

    $shortOptions = implode(
        '',
        array_map(
            function ($opt) {
                return $opt[0];
            },
            $opts
        )
    );
    $longOptions = array_map(
        function ($opt) {
            return $opt[1];
        },
        $opts
    );

    $args = getopt($shortOptions, $longOptions);

    // Default values.
    $help = $runTests = $clone = false;
    $extractAssets = true;
    $module = $branch = null;

    // Using -1 to indicate that no log level has been specified.
    $logLevel = -1;

    foreach ($args as $key => $value) {
        if (is_array($value)) {
            fwrite(STDERR, "Multiple values not allowed for '$key'\n");
            exit(1);
        }

        switch ($key) {
            case 'h':
            case 'help':
                $help = true;
                break;
            case 'q':
            case 'quiet':
                $logLevel = max($logLevel, Logger::WARNING);
                break;
            case 'v':
            case 'verbose':
                $logLevel = max($logLevel, Logger::INFO);
                break;
            case 'debug':
                $logLevel = max($logLevel, Logger::DEBUG);
                break;
            case 'run-tests':
                $runTests = true;
                break;
            case 'module':
                $module = $value;
                break;
            case 'clone':
                $clone = true;
                break;
            case 'branch':
                $branch = $value;
                break;
            case 'skip-assets':
                $extractAssets = false;
                break;
            default:
                throw new Exception("Unexpected option '$key'");
                exit(1);
                break;
        }
    }

    if ($help) {
        displayHelpText();
        exit;
    }

    if ($logLevel === -1) {
        $logLevel = Logger::NOTICE;
    }

    $logger = Logging::factory('xdmod-packager', array(
        'console' => array('level' => $logLevel)
    ));

    $cmd = implode(' ', array_map('escapeshellarg', $argv));
    $logger->info("Command: $cmd");
    $logger->debug(json_encode(array_merge(
        array('message' => 'Parsed args'),
        $args
    )));

    if ($module === null) {
        $logger->err('No module specified');
        exit(1);
    }

    $packager = Packager::createFromModuleName($module);

    $packager->setLogger($logger);
    $packager->setRunTests($runTests);
    $packager->setGitClone($clone);
    $packager->setExtractAssets($extractAssets);

    if ($branch !== null) {
        $packager->setGitBranch($branch);
    }

    $packager->createPackage();
}

/**
 * Get the global logger.
 *
 * Makes sure that the global logger exists or creates a simple console
 * logger.  This function should only be used in the global scope where
 * the logger may not have already been created.
 *
 * @return LoggerInterface
 */
function getLogger()
{
    global $logger;

    if (!isset($logger)) {
        $logger = Logging::singleton('xdmod-packager', array('console' => array()));
    }

    return $logger;
}

function displayHelpText()
{
}
