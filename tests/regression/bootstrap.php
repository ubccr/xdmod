<?php

$dir = __DIR__;

// Autoloader for test classes.
spl_autoload_register(
    function ($className) use ($dir) {
        $classPath
            = $dir
            . '/lib/'
            . str_replace('\\', '/', $className)
            . '.php';

        if (is_readable($classPath)) {
            return require_once $classPath;
        } else {
            return false;
        }
    }
);

// Autoloader for integration test harness
spl_autoload_register(
    function ($className) use ($dir) {
        $classPath
            = $dir
            . '/../integration/lib/'
            . str_replace('\\', '/', $className)
            . '.php';

        if (is_readable($classPath)) {
            return require_once $classPath;
        } else {
            return false;
        }
    }
);

// Autoloader for XDMoD classes.
require_once __DIR__ . '/../../configuration/linker.php';
