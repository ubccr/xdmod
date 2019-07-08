#!/usr/bin/env php
<?php
/**
 * Process data warehouse batch export requests.
 */

require_once __DIR__ . '/../configuration/linker.php';

use CCR\Log;
use DataWarehouse\Export\BatchProcessor;

try {
    $help = false;
    $dryRun = false;
    $logLevel = -1;

    $args = getopt('hqvd', ['help', 'dry-run', 'quiet', 'verbose', 'debug']);

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
            case 'dry-run':
                $dryRun = true;
                break;
            case 'q':
            case 'quiet':
                $logLevel = max($logLevel, Log::WARNING);
                break;
            case 'v':
            case 'verbose':
                $logLevel = max($logLevel, Log::INFO);
                break;
            case 'd':
            case 'debug':
                $logLevel = max($logLevel, Log::DEBUG);
                break;
            default:
                fwrite(STDERR, "Unexpected option '$key'\n");
                exit(1);
                break;
        }
    }

    // Set default log level if none was specified.
    if ($logLevel === -1) {
        $logLevel = Log::NOTICE;
    }

    if ($help) {
        displayHelpText();
        exit;
    }

    $logConf = array(
        'file' => false,
        'mail' => false,
        'consoleLogLevel' => $logLevel
    );
    $logger = Log('batch-export', $logConf);
    $logger->info('Command: ' . implode(' ', array_map('escapeshellarg', $argv)));
    // NOTE: "process_start_time" is needed for the log summary.
    $logger->notice(['message' => 'batch_export_manager start', 'process_start_time' => date('Y-m-d H:i:s')]);
    $batchProcessor = new BatchProcessor();
    $batchProcessor->setDryRun($dryRun);
    $batchProcessor->setLogger($logger);
    $batchProcessor->processRequests();
    // NOTE: "process_end_time" is needed for the log summary.
    $logger->notice(['message' => 'batch_export_manager end', 'process_end_time' => date('Y-m-d H:i:s')]);
    exit;
} catch (Exception $e) {
    // Write any unexpected exceptions directly to STDERR since they may not
    // have been logged and it may not be able to create a log instance.
    fwrite(STDERR, "Data warehouse batch export failed\n");
    do {
        fwrite(STDERR, $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
    } while ($e = $e->getPrevious());
    exit(1);
}

function displayHelpText()
{
    global $argv;

    echo <<<"EOMSG"
Usage: {$argv[0]}

    -h, --help
        Display this message and exit.

    -v, --verbose
        Output info level logging.

    -d, --debug
        Output debug level logging.

    -q, --quiet
        Output warning level logging.

    --dry-run
        Perform all the processing steps, but don't generate or remove any
        files, send any emails, or change the status of any export requests.
EOMSG;
}
