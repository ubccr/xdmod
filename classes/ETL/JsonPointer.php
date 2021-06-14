<?php
/** -----------------------------------------------------------------------------------------
 * Singleton class for implementing RFC 6901 JSON Pointers. Pointers allow us to extract individual
 * fragments from a JSON document.
 *
 * @see https://tools.ietf.org/html/rfc6901
 * @author Steve Gallo 2017-07-18
 * ------------------------------------------------------------------------------------------
 */

namespace ETL;

use Exception;
use \CCR\Loggable;

class JsonPointer
{
    /**
     * Character representing the last element of an array.
     *
     * @var string
     */

    const LAST_ARRAY_ELEMENT_CHAR = '-';

    /**
     * A JSON pointer must start with this character or be be an empty string.
     *
     * @var string
     */

    const POINTER_CHAR = '/';

    /**
     * An object extending Loggable that can be used to log error messages.
     *
     * @var \CCR\Loggable
     */

    private static $loggable = null;

    /** -----------------------------------------------------------------------------------------
     * This is a singleton class.
     * ------------------------------------------------------------------------------------------
     */

    private function __construct()
    {
    }

    /** -----------------------------------------------------------------------------------------
     * Set the loggable object to use when logging.
     *
     * @param Loggable $loggable An object that has access to the logger, or NULL to unset the
     *   logger.
     * ------------------------------------------------------------------------------------------
     */

    public static function setLoggable(Loggable $loggable = null)
    {
        self::$loggable = $loggable;
    }  // loggable()

    /** -----------------------------------------------------------------------------------------
     * Check to see if a string is a valid JSON pointer. Optionally verify that the first token is
     * an expected value.
     *
     * @param string $pointer The pointer to validate.
     * @param string $expectedFirstToken Optional first token for verification
     *
     * @return TRUE if the string is a valid JSON pointer (and optionally that the first token is
     *   the expected token), FALSE otherwise.
     * ------------------------------------------------------------------------------------------
     */

    public static function isValidPointer($pointer, $expectedFirstToken = null)
    {
        if ( '' !== $pointer && ! is_string($pointer) ) {
            return false;
        }

        if ( '' !== $pointer && 0 !== strpos($pointer, self::POINTER_CHAR) ) {
            return false;
        }

        if ( null !== $expectedFirstToken ) {
            $tokens = array_slice(array_map('urldecode', explode('/', $pointer)), 1);
            $firstToken = array_shift($tokens);
            return ( $firstToken == $expectedFirstToken );
        }
        return true;
    }  // isValidPointer()

    /** -----------------------------------------------------------------------------------------
     * Return the first token (e.g., top level object property) of a valid JSON pointer.
     *
     * @param string $pointer The pointer to process.
     *
     * @return string The first token in the pointer or FALSE if the pointer was not valid.
     * ------------------------------------------------------------------------------------------
     */

    public static function getFirstToken($pointer)
    {
        if ( ! static::isValidPointer($pointer) ) {
            return false;
        }
        $tokens = array_slice(array_map('urldecode', explode('/', $pointer)), 1);
        return array_shift($tokens);
    }  // getFirstToken()

    /** -----------------------------------------------------------------------------------------
     * Extract a JSON fragment from a document referenced by an RFC 6901 JSON pointer (see
     * https://tools.ietf.org/html/rfc6901).
     *
     * @param mixed $json The JSON document string or a decoded JSON object
     * @param string $pointer The JSON pointer
     *
     * @returns The portion of the JSON document referenced by the pointer
     *
     * @throws Exception if the pointer is invalid or if there is an error traversing the document.
     * ------------------------------------------------------------------------------------------
     */

    public static function extractFragment($json, $pointer)
    {
        // Based in part on https://github.com/raphaelstolt/php-jsonpointer
        // Replace with this package once we support PHP 5.4

        if ( ! static::isValidPointer($pointer) ) {
            return false;
        }

        $jsonObj = null;

        if ( is_string($json) ) {
            $jsonObj = json_decode($json);

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                if ( null !== self::$loggable ) {
                    self::$loggable->getLogger()->err('Invalid JSON');
                }
                throw new Exception('Invalid JSON');
            }
        } else {
            $jsonObj = $json;
        }

        // An empty pointer references the entire document

        if ( '' == $pointer ) {
            return $jsonObj;
        }

        // Urldecode and extract the pointer after the '/'

        $pointerParts = array_slice(array_map('urldecode', explode('/', $pointer)), 1);

        // Convert encoded characters (see https://tools.ietf.org/html/rfc6901#section-3)

        $parts = array();
        array_filter(
            $pointerParts,
            function ($p) use (&$parts) {
                return $parts[] = str_replace(array('~1', '~0'), array('/', '~'), $p);
            }
        );

        return static::traverseJson($jsonObj, $pointer, $parts);

    }  // extractFragment()

    /* ------------------------------------------------------------------------------------------
     * Recursively traverse the JSON document, looking for the portion that is referenced by
     * the pointer.
     *
     * @param mixed $json The JSON document or a portion of the document
     * @param string $pointer The full JSON pointer
     * @param array $pointerParts An array containing the portions of the pointer that have
     *   yet to be traversed.
     *
     * @returns The portion of the JSON document referenced by the pointer
     *
     * @throws Exception if the value referenced by the pointer does not exist in the document.
     * ------------------------------------------------------------------------------------------
     */

    private static function traverseJson(&$json, $pointer, array $pointerParts)
    {
        $pointerPart = array_shift($pointerParts);

        if ( is_array($json) && isset($json[$pointerPart]) ) {

            if ( count($pointerParts) === 0 ) {
                return $json[$pointerPart];
            }

            if ( (is_array($json[$pointerPart]) || is_object($json[$pointerPart])) && is_array($pointerParts) ) {
                return static::traverseJson($json[$pointerPart], $pointer, $pointerParts);
            }

        } elseif ( is_object($json) && in_array($pointerPart, array_keys(get_object_vars($json))) ) {

            if ( count($pointerParts) === 0 ) {
                return $json->{$pointerPart};
            }

            if ( (is_object($json->{$pointerPart}) || is_array($json->{$pointerPart})) && is_array($pointerParts) ) {
                return static::traverseJson($json->{$pointerPart}, $pointer, $pointerParts);
            }

        } elseif ( is_object($json) && empty($pointerPart) && array_key_exists('_empty_', get_object_vars($json)) ) {

            $pointerPart = '_empty_';

            if ( count($pointerParts) === 0 ) {
                return $json->{$pointerPart};
            }

            if ( (is_object($json->{$pointerPart}) || is_array($json->{$pointerPart})) && is_array($pointerParts) ) {
                return static::traverseJson($json->{$pointerPart}, $pointer, $pointerParts);
            }

        } elseif ( $pointerPart === self::LAST_ARRAY_ELEMENT_CHAR && is_array($json) ) {
            return end($json);
        } elseif ( is_array($json) && count($json) < $pointerPart ) {
            // Do nothing, let Exception bubble up
        } elseif ( is_array($json) && array_key_exists($pointerPart, $json) && $json[$pointerPart] === null ) {
            return $json[$pointerPart];
        }

        if ( null !== self::$loggable ) {
            self::$loggable->getLogger()->warning(
                sprintf("JSON pointer '%s' references a nonexistent value", $pointer)
            );
        }

        // Throw an exception rather than returning NULL or FALSE because both of those are valid
        // JSON values.

        throw new Exception(sprintf("JSON pointer '%s' references a nonexistent value", $pointer));

    } // traverseJson()
}  // class JsonPointer
