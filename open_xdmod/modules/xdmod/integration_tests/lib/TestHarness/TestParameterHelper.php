<?php

namespace TestHarness;

class TestParameterHelper
{

    const RAND_REGEX = '/rand\((\\d+)\)/';

    const RAND_CHAR_REGEX = '/rand_char\((\\d+)\)/';

    private static $SOURCE = array(
        0,1,2,3,4,5,6,7,8,9,
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
    );


    /**
     * Process the provided test parameter and return the specified value.
     * The currently processed patterns are:
     *   - rand(n); where n <= 16
     *     - generates a random number of length n.
     *     - ex. rand(10) - 5489374568
     *   - rand_char(n)
     *     - generates a string of length n comprised of random characters.
     *     - ex. rand_char(10) - eSmCMOUiUo
     * @param mixed $param
     * @return mixed
     */
    public static function processParam($param)
    {
        if (is_string($param)) {
            if ('null' === $param) {
                return null;
            }
            $matches = array();
            preg_match(self::RAND_REGEX, $param, $matches);
            if (count($matches) > 0) {
                $length = (int) $matches[1];
                $length = min($length, 16);
                return self::randomNum($length);
            }
            preg_match(self::RAND_CHAR_REGEX, $param, $matches);
            if (count($matches) > 0) {
                $length = (int) $matches[1];
                return self::randomChar($length);
            }
        }

        return $param;
    }

    private static function randomChar($length)
    {
        $result = array();
        for ($i = 0; $i < $length; $i++) {
            $result[] = self::$SOURCE[rand(0, PHP_INT_MAX - 1) % 62];
        }

        return implode('', $result);

    }

    private static function randomNum($max = 16)
    {
        $result = '';
        for ($i = 0; $i < $max; $i++) {
            $result .= rand(0, PHP_INT_MAX - 1) % 10;
        }
        return (int) $result;
    }
}
