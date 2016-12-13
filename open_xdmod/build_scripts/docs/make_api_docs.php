#!/usr/bin/env php
<?php

try {
    main();
} catch (Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    fwrite(STDERR, $e->getTraceAsString() . "\n");
    exit(1);
}

function main()
{
    $longOptions = array(
        'base-dir:',
        'output-dir:',
    );

    $args = getopt('', $longOptions);

    if ($args === false) {
        fwrite(STDERR, "Failed to parse arguments\n");
        exit(1);
    }

    $baseDir = $outputDir = null;

    foreach ($args as $key => $value) {
        if (is_array($value)) {
            fwrite(STDERR, "Multiple values not allowed for '$key'\n");
            exit(1);
        }

        switch ($key) {
            case 'base-dir':
                $baseDir = $value;
                break;
            case 'output-dir':
                $outputDir = $value;
                break;
            default:
                fwrite(STDERR, "Unexpected option '$key'\n");
                exit(1);
                break;
        }
    }

    if (exec('which phpdoc 2>/dev/null') == '') {
        fwrite(STDERR, "phpdoc command not found\n");
        exit(1);
    }

    if ($baseDir === null) {
        fwrite(STDERR, "No base directory specified\n");
        exit(1);
    }

    if ($outputDir === null) {
        fwrite(STDERR, "No output directory specified\n");
        exit(1);
    }

    if (is_file($outputDir)) {
        fwrite(STDERR, "'$outputDir' is not a directory\n");
        exit(1);
    } elseif (is_dir($outputDir)) {
        system("rm -rf $outputDir");
    }

    mkdir($outputDir);

    $sourceDirs = array(
        "$baseDir/classes",
        "$baseDir/libraries",
    );

    $dirs = escapeshellarg(implode(',', $sourceDirs));

    $ignorePaths = array(
        "$baseDir/classes/FPDF.php",
    );

    $ignore = escapeshellarg(implode(',', $ignorePaths));

    $cmd = "phpdoc run -n --title 'Open XDMoD' --defaultpackagename 'XDMoD'"
        . " -d $dirs -i $ignore -t $outputDir";

    system($cmd);

    system("rm -rf $outputDir/phpdoc-cache-*");
}

