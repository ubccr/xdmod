<?php

$dir = __DIR__;

// Autoloader for test classes.
spl_autoload_register(
    function ($className) use ($dir) {

        // We want to treat all classes residing in 'lib' as belonging to the
        // 'ComponentTests' namespace. Therefor, if ComponentTests is found in
        // $className we strip it from $className and look for that file instead.
        if (strpos($className, 'ComponentTests') !== false) {
            $parts = explode($className, '\\');
            if ($parts[0] === 'ComponentTests') {
                unset($parts[0]);
                $className = implode('\\', $parts);
            }
        }
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

// Autoloader for XDMoD classes.
require_once __DIR__ . '/../../../../configuration/linker.php';
