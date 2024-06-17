<?php
/**
 * This transformer is a modification of the JsonReferenceTransformer to support cases where we want
 * to be able to replace the reference with the JSON that it points to and at the same time
 * overwrite or add to the referenced JSON supporting greater re-use of JSON configuraiton files.
 * The original JsonReferenceTransformer was not modified because with these changes it would no
 * longer conform to RFC-6901.
 *
 * Evaluate a JSON reference pointer (see https://tools.ietf.org/html/rfc6901) and allow specified
 * keys in the referenced JSON to be overwritten (or added if they do not exist). The entire object
 * containing the "$ref-with-overwrite" key is replaced with the thing that it points to with any
 * overwrites applied.
 *
 * For example:
 *
 * {
 *     "$ref-with-overwrite": "http://example.com/example.json#/foo/bar",
 *     "$overwrite" : {
 *         "key1": "new value"
 *     }
 * }
 */

namespace Configuration;

use CCR\Log;
use stdClass;

class JsonReferenceWithOverwriteTransformer extends JsonReferenceTransformer implements iConfigFileKeyTransformer
{
    const REFERENCE_KEY = '$ref-with-overwrite';

    /**
     * @see iConfigFileKeyTransformer::keyMatches()
     */

    public function keyMatches($key)
    {
        return (self::REFERENCE_KEY == $key);
    }

    /**
     *  Transform the JSON pointer and then apply any overwrite directives.
     *
     * @see iConfigFileKeyTransformer::transform()
     */

    public function transform(&$key, &$value, stdClass $obj, Configuration $config, $exceptionLogLevel)
    {
        $overwriteKey = '$overwrite';
        $overwriteDirectives = array();

        // Need to look into the rest of the object here, do we have access to it?

        if ( isset($obj->$overwriteKey) ) {
            $overwriteDirectives = $obj->$overwriteKey;
            // Remove the overwrite key from the object because the JsonReferenceTransformer will
            // not allow it.
            unset($obj->$overwriteKey);
        } else {
            // If the reference with overwrite was specified, it must contain the overwrite
            // directive!
            $this->logAndThrowException(
                sprintf("Expected '%s' directive not found", $overwriteKey),
                array('log_level' => $exceptionLogLevel)
            );
        }

        $jsonRefUrl = $value;
        parent::transform($key, $value, $obj, $config, $exceptionLogLevel);

        if ( ! is_object($value) ) {
            $this->logger->warning(
                sprintf("JSON reference '%s' does not generate object, got %s. Skipping.", $jsonRefUrl, gettype($value))
            );
        } else {
            foreach ( $overwriteDirectives as $overwriteKey => $overwriteValue ) {
                if ( ! isset($value->$overwriteKey) ) {
                    $this->logger->debug(sprintf("Overwrite key '%s' not found in reference, adding.", $overwriteKey));
                } else {
                    $this->logger->debug(sprintf("Overwriting '%s' in reference.", $overwriteKey));
                }
                $value->$overwriteKey = $overwriteValue;
            }
        }

        return true;
    }
}
