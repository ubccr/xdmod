<?php

namespace ETL\Viewer;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class OOPFinder
{
    /**
     * @var array
     */
    protected static $storage;

    protected static $tokens = array(T_CLASS, T_INTERFACE, T_TRAIT);

    public static function findClassIn($directory, $name)
    {
        return self::_findIn($directory, $name, T_CLASS);
    }

    public static function findInterfaceIn($directory, $name)
    {
        return self::_findIn($directory, $name, T_INTERFACE);
    }

    public static function findTraitIn($directory, $name)
    {
        return self::_findIn($directory, $name, T_TRAIT);
    }

    public static function findAllIn($directory)
    {
        $results = array();
        $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $phpFiles = new RegexIterator($allFiles, '/\.php$/');

        foreach($phpFiles as $phpFile) {
            $results[] = $phpFile->getFilename();
        }

        return $results;
    }

    public static function findIn($directory, $name)
    {
        return self::_findIn($directory, $name, T_CLASS | T_INTERFACE | T_TRAIT);
    }

    protected static function _findIn($directory, $name, $types)
    {
        self::initializeStorage($directory, $types);

        // If we've already found this entity than return it.
        if (array_key_exists($name, self::$storage[$directory][$types])) {
            return self::$storage[$directory][$types][$name];
        }
        $type = 'unknown';

        $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $phpFiles = new RegexIterator($allFiles, '/\.php$/');
        foreach ($phpFiles as $phpFile) {
            $content = file_get_contents($phpFile->getRealPath());
            $tokens = token_get_all($content);

            $namespace = '';
            for ($index = 0; isset($tokens[$index]); $index++) {
                if (!isset($tokens[$index][0])) {
                    continue;
                }
                if (T_NAMESPACE === $tokens[$index][0]) {
                    $index += 2; // Skip namespace keyword and whitespace
                    while (isset($tokens[$index]) && is_array($tokens[$index])) {
                        $namespace .= $tokens[$index++][1];
                    }
                }

                if ($index + 2 < count($tokens) && $types & $tokens[$index][0] && T_WHITESPACE === $tokens[$index + 1][0] && T_STRING === $tokens[$index + 2][0]) {
                    $index += 2; // Skip class keyword and whitespace
                    $foundName = $tokens[$index][1];

                    $type = $tokens[$index][0];
                    $fqn = $namespace . '\\' . $foundName;
                    if (strtolower($foundName) === strtolower($name)) {
                        self::$storage[$directory][$type][$name] = $fqn;
                        # break if you have one class per file (psr-4 compliant)
                        # otherwise you'll need to handle class constants (Foo::class)
                        break;
                    }
                }
            }
        }
        return self::$storage[$directory][$type][$name];
    }

    protected static function initializeStorage($directory, $types)
    {
        if (!isset(self::$storage)) {
            self::$storage = array();
        }

        if (!array_key_exists($directory, self::$storage)) {
            self::$storage[$directory] = array();
        }

        foreach(self::$tokens as $token) {
            if ($types & $token && !array_key_exists($token, self::$storage)) {
                self::$storage[$directory][$token] = array();
            }
        }
    }
}
