<?php

$dir = __DIR__;

// Autoloader for test classes.
spl_autoload_register(
    function ($className) use ($dir) {
        // Look for classes relative to the `lib` directory; each namespace has
        // its own directory in `lib` with the exception of the
        // `IntegrationTests` namespace whose classes are defined in files
        // directly in `lib`. For example, `TestHarness\XdmodTestHelper`
        // resolves to `$dir/lib/TestHarness/XdmodTestHelper.php`, whereas
        // `IntegrationTests\TokenAuthTest` resolves to
        // `$dir/lib/TokenAuthTest.php`.
        $classPath
            = $dir
            . '/lib/'
            . str_replace(
                '\\',
                '/',
                preg_replace(
                    '/IntegrationTests\\\\?/',
                    '',
                    $className
                )
            ) . '.php';

        if (is_readable($classPath)) {
            return require_once $classPath;
        } else {
            return false;
        }
    }
);

// Autoloader for XDMoD classes.
require_once __DIR__ . '/../../configuration/linker.php';
