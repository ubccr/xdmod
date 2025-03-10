<?php

$dir = __DIR__;

// Autoloader for test classes.
spl_autoload_register(
    function ($className) use ($dir) {
        // Replace the RegressionTests namespace prefix with the path to the
        // regression tests lib directory.
        $classPath = preg_replace(
            '/PostTests\\\\?/',
            "$dir/lib/",
            $className
        );
        // Replace the IntegrationTests namespace prefix with the path to the
        // integration tests lib directory.
        $classPath = preg_replace(
            '/IntegrationTests\\\\?/',
            "$dir/../integration/lib/",
            $classPath
        );
        // Replace namespace separators with directory separators.
        $classPath = str_replace('\\', '/', $classPath) . '.php';
        if (is_readable($classPath)) {
            return require_once $classPath;
        }
        return false;
    }
);

// Autoloader for XDMoD classes.
require_once __DIR__ . '/../../configuration/linker.php';
