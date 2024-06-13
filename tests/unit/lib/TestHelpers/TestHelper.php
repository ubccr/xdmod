<?php

namespace UnitTests\TestHelpers;

use ReflectionException;
use ReflectionMethod;

/**
 * Provides helper functions for completing tests.
 */
class TestHelper
{
    /**
     * Unlock and return a protected or private function on a class or object.
     *
     * Some methods may not be designed to be used externally. Use with caution!
     *
     * See: https://stackoverflow.com/a/2798203
     *
     * @param mixed $classOrObject A class or object with a method to unlock.
     * @param string $methodName The name of the method to unlock.
     * @return ReflectionMethod      A reflection of the unlocked method.
     * @throws ReflectionException
     */
    public static function unlockMethod($classOrObject, $methodName)
    {
        $reflection = new \ReflectionClass($classOrObject);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }
}
