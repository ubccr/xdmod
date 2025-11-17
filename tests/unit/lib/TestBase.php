<?php
/**
 * Abstract base class to encapsulate funcationality common to unit tests.
 */

namespace UnitTests;

abstract class TestBase extends \PHPUnit\Framework\TestCase
{
    /**
     * Recursively filter out any keys matching one in $keyList. This is a helper function to
     * address the issue specified in Asana https://app.asana.com/0/807629084565719/1101232922862525
     * where the key generated for JSON file DataEndpoints is unstable. Note that 2-dimensional
     * arrays are not handled.
     *
     * @param  array $keyList The list of keys to remove
     * @param  array $input The input object being filtered.
     *
     * @return array The filtered object with specified keys removed
     */

    protected function filterKeysRecursive(array $keyList, \stdClass $input)
    {
        foreach ($input as $key => &$value)
        {
            if ( in_array($key, $keyList) ) {
                unset($input->$key);
            } elseif ( is_object($value) ) {
                $this->filterKeysRecursive($keyList, $value);
            } elseif ( is_array($value) ) {
                foreach ( $value as $element ) {
                    if ( is_object($element) ) {
                        $this->filterKeysRecursive($keyList, $element);
                    }
                }
            }
        }
        return $input;
    }
}
