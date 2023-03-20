<?php

namespace TestHarness;

use stdClass;

class Utilities
{
    public static function getCombinations($arrays) {
        $result = array(array());
        foreach ($arrays as $property => $property_values) {
            $tmp = array();
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, array($property => $property_value));
                }
            }
            $result = $tmp;
        }
        return $result;
    }

    /**
     * returns an array containing the names of the realms that are
     * available to be tested. This comes from the XDMOD_REALMS environment
     * variable
     */
    public static function getRealmsToTest()
    {
        $realm_list = getenv('XDMOD_REALMS');
        if ($realm_list === false) {
            return array();
        }
        return explode(',', $realm_list);
    }

    /**
     * A helper function that will recursively merge the contents of $defaults to $source.
     *
     * @param stdClass|array $source
     * @param stdClass|array $defaults
     * @return stdClass|array
     */
    public static function applyDefaults($source, $defaults)
    {
        if (is_object($source) && is_object($defaults)) {
            return self::applyDefaultsObjects($source, $defaults);
        } elseif (is_array($source) && is_array($defaults)) {
            return self::applyDefaultsArray($source, $defaults);
        }
        return $source;
    }

    private static function applyDefaultsObjects(stdClass $source, stdClass $defaults) {
        foreach($defaults as $property => $defaultValue) {
            if (!isset($source->$property)) {
                $source->$property = $defaultValue;
            }

            $sourceValue = &$source->$property;
            if (is_object($sourceValue) && is_object($defaultValue)) {
                $source->$property = self::applyDefaults($sourceValue, $defaultValue);
            } elseif (is_array($sourceValue) && is_array($defaultValue)) {
                foreach($defaultValue as $default) {
                    if (!in_array($default, $sourceValue)) {
                        $sourceValue[] = $default;
                    }
                }
            }
            // note, we do not want to replace scalar values if they already exist in $source.
        }

        return $source;
    }

    private static function applyDefaultsArray(array $source, array $defaults)
    {
        foreach($defaults as $property => $defaultValue) {
            if (!isset($source[$property])) {
                $source[$property] = $defaultValue;
            }

            $sourceValue = $source[$property];
            if (is_object($sourceValue) && is_object($defaultValue)) {
                $source[$property] = self::applyDefaults($sourceValue, $defaultValue);
            } elseif (is_array($sourceValue) && is_array($defaultValue)) {
                foreach($defaultValue as $default) {
                    if (!in_array($default, $sourceValue)) {
                        $sourceValue[] = $default;
                    }
                }
            }
        }
        return $source;
    }
}
