#!/usr/bin/env php
<?php
/**
 * Dump the Open XDMoD MySQL schemas and initial data set to files.
 */

try {
    main();
} catch (Exception $e) {
    _error($e->getMessage());
    _error($e->getTraceAsString());
    exit(1);
}

/**
 * Main function.
 */
function main()
{
    global $argv, $debug, $version;

    $opts = array(
        array('',   'debug'),
        array('u:', 'user:'),
        array('h:', 'host:'),
        array('p:', 'password:'),
    );

    $shortOptions = implode(
        '',
        array_map(function ($opt) { return $opt[0]; }, $opts)
    );
    $longOptions = array_map(function ($opt) { return $opt[1]; }, $opts);

    $args = getopt($shortOptions, $longOptions);

    if ($args === false) {
        fwrite(STDERR, "Failed to parse arguments\n");
        exit(1);
    }

    $debug = false;

    $user = $password = null;

    $host = 'localhost';

    foreach ($args as $key => $value) {
        if (is_array($value)) {
            fwrite(STDERR, "Multiple values not allowed for '$key'\n");
            exit(1);
        }

        switch ($key) {
            case 'debug':
                $debug = true;
                break;
            case 'u':
            case 'user':
                $user = $value;
                break;
            case 'p':
            case 'password':
                $password = $value;
                break;
            case 'h':
            case 'host':
                $host = $value;
                break;
            default:
                fwrite(STDERR, "Unexpected option '$key'\n");
                exit(1);
                break;
        }
    }

    $currentDir = __DIR__;
    $openSrcDir = realpath($currentDir . '/../..');
    $dbDir      = $openSrcDir . '/db';
    $schemaDir  = $dbDir . '/schema';
    $dataDir    = $dbDir . '/data';

    $portalConfig = $openSrcDir . '/configuration/portal_settings.ini';

    $version = getVersionString($portalConfig);

    $databases = array(
        'mod_hpcdb',
        'mod_logger',
        'mod_shredder',
        'moddb',
        'modw',
    );

    foreach ($databases as $database) {
        $schemaPath = "$schemaDir/$database.sql";
        dumpSchema($database, $schemaPath, $user, $password, $host);
    }

    // These databases have tables that contain data that should be
    // inserted during the initial installation.
    $dataTables = array(
        'mod_hpcdb' => array(
            'hpcdb_fields_of_science',
        ),
        'mod_logger' => array(
            'log_level',
        ),
        'mod_shredder' => array(
        ),
        'moddb' => array(
            'Colors',
            'Roles',
            'UserTypes',
        ),
        'modw' => array(
            'error_descriptions',
        ),
    );

    foreach ($dataTables as $database => $tables) {
        $dataPath = "$dataDir/$database.sql";
        dumpData($database, $tables, $dataPath, $user, $password, $host);
    }

    exit;
}

/**
 * Dump a MySQL database schema to file.
 *
 * @param string $dbName Database name.
 * @param string $outputPath Output file path.
 * @param string $user MySQL username.
 * @param string $password MySQL password.
 * @param string $host MySQL server hostname.
 */
function dumpSchema(
    $dbName,
    $outputPath,
    $user = null,
    $password = null,
    $host = null
) {
    $args = array(
        '--no-data',
        '--routines',
        '--skip-comments',
        escapeshellarg($dbName),
    );


    if ($host !== null) {
        array_unshift($args, '-h', escapeshellarg($host));
    }

    if ($password !== null) {
        array_unshift($args, '-p' . escapeshellarg($password));
    }

    if ($user !== null) {
        array_unshift($args, '-u', escapeshellarg($user));
    }

    $mysqlCmd = 'mysqldump ' . implode(' ', $args);

    $sedExpressions = array(
        's/ AUTO_INCREMENT=[0-9]*\b//',
        's/CREATE DEFINER=[^ ]* /CREATE /',
        's/DEFINER=[^*]*\*/\*/',
    );

    $sedCmd = 'sed ' . implode(' ', array_map(
        function ($expr) { return '-e ' . escapeshellarg($expr); },
        $sedExpressions
    ));

    $cmd = $mysqlCmd . ' | ' . $sedCmd . ' > ' . escapeshellarg($outputPath);

    $output = executeCommand($cmd);

    if (count($output) > 0) {
        throw new Exception(implode("\n", $output));
    }
}

/**
 * Dump data from a MySQL database to file.
 *
 * @param string $dbName Database name.
 * @param array $tables List of tables.
 * @param string $outputPath Output file path.
 * @param string $user MySQL username.
 * @param string $password MySQL password.
 * @param string $host MySQL server hostname.
 */
function dumpData(
    $dbName,
    array $tables,
    $outputPath,
    $user = null,
    $password = null,
    $host = null
) {
    global $version;

    $args = array(
        '--no-create-info',
        '--skip-comments',
        escapeshellarg($dbName),
    );

    if (count($tables) > 0) {
        $args = array_merge($args, $tables);

        if ($host !== null) {
            array_unshift($args, '-h', escapeshellarg($host));
        }

        if ($password !== null) {
            array_unshift($args, '-p' . escapeshellarg($password));
        }

        if ($user !== null) {
            array_unshift($args, '-u', escapeshellarg($user));
        }

        $mysqlCmd = 'mysqldump ' . implode(' ', $args);

        $cmd = $mysqlCmd . ' >' . escapeshellarg($outputPath);

        $output = executeCommand($cmd);

        if (count($output) > 0) {
            throw new Exception(implode("\n", $output));
        }

        $contents = file_get_contents($outputPath);
    } else {
        $contents = '';
    }

    // Append schema history statement.
    $contents .= "INSERT INTO `schema_version_history`"
        . " VALUES ('$dbName', '$version', NOW(), 'created', 'N/A');\n\n";

    file_put_contents($outputPath, $contents);
}

/**
 * Execute a command.
 *
 * @param string $cmd Command (must already be escaped).
 *
 * @return array Output (both STDOUT AND STDERR) or command.
 */
function executeCommand($cmd)
{
    _debug("Command: $cmd");

    $output = array();

    exec("$cmd 2>&1", $output, $returnVar);

    if ($returnVar != 0) {
        $msg = "Command returned non-zero value '$returnVar': "
            . implode("\n", $output);
        throw new Exception($msg);
    }

    return $output;
}

/**
 * Get the version number string from the config file.
 *
 * @param string $file Config file (portal_settings.ini) path.
 *
 * @return string
 */
function getVersionString($file)
{
    _debug("Parsing file '$file'");

    $config = parse_ini_file($file, true);

    if (!isset($config['general']) || !isset($config['general']['version'])) {
        throw new Exception('Failed to find version number');
    }

    return $config['general']['version'];
}

/**
 * Output a debugging message if the debug option was specified.
 *
 * @param string $text
 */
function _debug($text)
{
    global $debug;

    if (!$debug) {
        return;
    }

    fwrite(STDERR, "[DEBUG] $text\n");
}

/**
 * Output an error message.
 *
 * @param string $text
 */
function _error($text)
{
    fwrite(STDERR, "[ERROR] $text\n");
}
