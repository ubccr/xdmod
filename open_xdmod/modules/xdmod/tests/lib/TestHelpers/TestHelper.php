<?php

namespace TestHelpers;

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
     * @param  mixed  $classOrObject A class or object with a method to unlock.
     * @param  string $methodName    The name of the method to unlock.
     * @return ReflectionMethod      A reflection of the unlocked method.
     */
    public static function unlockMethod($classOrObject, $methodName)
    {
        $reflection = new \ReflectionClass($classOrObject);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * Unlock and return a protected or private function on a class or object.
     *
     * Some properties may not be designed to be used externally. Use with caution!
     *
     * @param mixed  $classOrObject A class or object with a property to unlock.
     * @param string $propertyName  The name of the property to unlock.
     * @return ReflectionProperty   A reflection of the unlocked property.
     **/
    public static function unlockProperty($classOrObject, $propertyName)
    {
        $property = new \ReflectionProperty($classOrObject, $propertyName);
        $property->setAccessible(true);
        return $property;
    }
}
