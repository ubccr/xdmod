<?php

namespace Rest\Utilities;

class Conversions
{


    public static function toInt($value)
    {
        return isset($value) ? intval($value) : $value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public static function toString($value)
    {
        $isObject = is_object($value);
        $hasToString = method_exists($value, '__toString');
        $isArray = is_array($value);
        $isAssociativeArray = self::isAssoc($value);

        $result = "";
        if ($isObject && !$hasToString) {
            $result = (string)$value;
        } elseif ($isArray && $isAssociativeArray) {
            $result .= "( ";
            foreach ($value as $key => $value) {
                $result .= "$key: $value, ";
            }
            $result .= " )";
        } elseif ($isArray && !$isAssociativeArray) {
            $result .= "( ";
            $result .= implode(", ", $value);
            $result .= " )";
        } else {
            $result = strval($value);
        }

        return $result;
    }

    private static function isAssoc($values)
    {
        if (!is_array($values)) {
            return false;
        }
        return (bool)count(array_filter(array_keys($values), 'is_string'));
    }
}
