#!/usr/bin/env php
<?php
/**
 * Check to see if a newer version of Open XDMoD is available.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

require_once __DIR__ . '/../configuration/linker.php';

use CCR\Log;
use Xdmod\Version;

$conf = array(
    'file' => false,
    'mail' => false,
);
$logger = Log::factory('update-check', $conf);

try {
    main();
} catch (Exception $e) {
    $logger->err(array(
        'message'    => $e->getMessage(),
        'stacktrace' => $e->getTraceAsString(),
    ));
    exit(1);
}

function main()
{
    global $logger;

    $updateConfig = \Configuration\XdmodConfiguration::assocArrayFactory(
        'update_check.json',
        CONFIG_DIR,
        $logger
    );

    if (!$updateConfig['enabled']) {
        exit;
    }

    Version::setLogger($logger);
    Version::setTimeout(60);

    $currentVersion = Version::getCurrentVersionNumber();
    $logger->info("Current version: $currentVersion");

    unset($updateConfig['enabled']);
    $updateConfig['current_version'] = $currentVersion;

    $latestVersion = Version::getLatestVersionNumber($updateConfig);
    $logger->info("Latest version: $latestVersion");

    if (Version::isNewerVersionAvailable()) {
        $logger->info('Found newer version');

        // Don't do anything.  The remote server will send the
        // notification email.
    }

    exit;
}
