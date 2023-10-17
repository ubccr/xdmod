<?php

namespace IntegrationTests\TestHarness;

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
}
