<?php
/* ==========================================================================================
 * Evaluate JSON references (https://tools.ietf.org/html/draft-pbryan-zyp-json-ref-03) where
 * the `$ref` gets logically replaced with the thing that it points to. For example,
 * { "$ref": "http://example.com/example.json#/foo/bar" }
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-11
 * ==========================================================================================
 */

namespace ETL\Configuration;

// PEAR logger
use Log;
use stdClass;
use ETL\Loggable;

class JsonReferenceTransformer extends Loggable implements iConfigFileKeyTransformer
{
    const REFERENCE_KEY = '$ref';

    /* ------------------------------------------------------------------------------------------
     * @see iConfigFileKeyTransformer::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(Log $logger = null)
    {
        parent::__construct($logger);
    }  // construct()

    /* ------------------------------------------------------------------------------------------
     * @see iConfigFileKeyTransformer::keyMatches()
     * ------------------------------------------------------------------------------------------
     */

    public function keyMatches($key)
    {
        return (self::REFERENCE_KEY == $key);
    }  // keyMatches()

    /* ------------------------------------------------------------------------------------------
     * Comments remove both the key and the value from the configuration and stop processing of the
     * key.
     *
     * @see iConfigFileKeyTransformer::transform()
     * ------------------------------------------------------------------------------------------
     */

    public function transform(&$key, &$value, stdClass $obj, Configuration $config)
    {
        // JSON references (see https://tools.ietf.org/html/draft-pbryan-zyp-json-ref-0)
        // constitute an entire object, for example:
        // { "$ref": "http://example.com/example.json#/foo/bar" }
        //
        // Because a reference is replaced with the entity that it points to, it must be
        // the ONLY key in an object. Otherwise, we may end up with a mix of objects,
        // scalars, and arrays in the same object which is not valid JSON.  For example,
        //
        // {
        //    "job_task": {
        //        "name": "Steve",
        //        "$ref": "etl_tables.d/jobs/job_task.json#/table_definition/columns",
        //    }
        // }
        //
        // might resolve to
        //
        // {
        //    "job_task": {
        //        "name": "Steve",
        //        [ 0, 1, 2 ]
        //    }
        // }
        //
        // or
        //
        // {
        //    "job_task": {
        //        "name": "Steve",
        //        {
        //            "name": "job_tasks",
        //            "engine": "MyISAM",
        //            "comment": "Consumption for resources by a user
        //        }
        //    }
        // }

        if( count(get_object_vars($obj)) != 1 ) {
            $this->logAndThrowException(
                sprintf('References cannot be mixed with other keys in an object: "%s": "%s"', $key, $value)
            );
        }

        $parsedUrl = parse_url($value);
        $path = $this->qualifyPath($parsedUrl['path'], $config);
        $this->logger->debug("Resolve JSON reference '$value' to file '$path'");

        $fragment = ( array_key_exists('fragment', $parsedUrl) ? $parsedUrl['fragment'] : '' );

        // If no scheme was provided, default to the file scheme. Also ensure that the
        // file path is properly formatted.

        $scheme = ( array_key_exists('scheme', $parsedUrl) ? $parsedUrl['scheme'] : 'file' );
        if ( 'file' == $scheme ) {
            $path = 'file://' . $path;
        }

        // Open the file and return the contents.

        $contents = @file_get_contents($path);
        if ( false === $contents ) {
            $this->logAndThrowException('Failed to open file: ' . $path);
        }

        $key = null;
        $value = $this->extractJsonFragment($contents, $fragment);

        return true;

    }  // transform()

    /* ------------------------------------------------------------------------------------------
     * Qualify the path using the base directory from the configuration object if it is
     * not already fully qualified.
     *
     * @param string $path The path to qualify
     * @param Configuration $config $The configuration object that called the transformer
     *
     * @returns A fully qualified path
     * ------------------------------------------------------------------------------------------
     */

    protected function qualifyPath($path, Configuration $config)
    {
        $this->logger->debug(get_class($this));
        return \xd_utilities\qualify_path($path, $config->getBaseDir());
    }

    /* ------------------------------------------------------------------------------------------
     * Extract an RFC 6901 (https://tools.ietf.org/html/rfc6901) JSON pointer from the document.
     *
     * @param string $json The JSON document
     * @param string $pointer The JSON pointer
     *
     * @returns The portion of the JSON document referenced by the pointer
     *
     * @throws Exception if the pointer is invalid or if there is an error traversing the document.
     * ------------------------------------------------------------------------------------------
     */

    private function extractJsonFragment($json, $pointer)
    {
        // Based in part on https://github.com/raphaelstolt/php-jsonpointer
        // Replace with this package once we support PHP 5.4

        // Validate the JSON pointer

        if ( "" !== $pointer && ! is_string($pointer) ) {
            $this->logAndThrowException("Invalid JSON pointer: '$pointer'");
        }

        if ( 0 !== strpos($pointer, '/') ) {
            $this->logAndThrowException("JSON pointer must start with '/'");
        }

        // Validate the json

        $jsonObj = json_decode($json);

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $this->logAndThrowException('Invalid JSON');
        }

        // An empty pointer references the entire document

        if ( '' == $pointer ) {
            return $jsonObj;
        }

        // Urldecode and extract the pointer after the '/'

        $pointerParts = array_slice(
            array_map('urldecode', explode('/', $pointer)),
            1
        );

        // Convert encoded characters (see https://tools.ietf.org/html/rfc6901#section-3)

        $parts = array();
        array_filter(
            $pointerParts,
            function ($p) use (&$parts) {
                return $parts[] = str_replace(array('~1', '~0'), array('/', '~'), $p);
            }
        );

        return $this->traverseJson($jsonObj, $pointer, $pointerParts);

    }  // extractJsonFragment()

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

    private function traverseJson(&$json, $pointer, array $pointerParts)
    {
        $pointerPart = array_shift($pointerParts);

        if ( is_array($json) && isset($json[$pointerPart]) ) {

            if ( count($pointerParts) === 0 ) {
                return $json[$pointerPart];
            }

            if ( (is_array($json[$pointerPart]) || is_object($json[$pointerPart])) && is_array($pointerParts) ) {
                return $this->traverseJson($json[$pointerPart], $pointer, $pointerParts);
            }

        } elseif ( is_object($json) && in_array($pointerPart, array_keys(get_object_vars($json))) ) {

            if ( count($pointerParts) === 0 ) {
                return $json->{$pointerPart};
            }

            if ( (is_object($json->{$pointerPart}) || is_array($json->{$pointerPart})) && is_array($pointerParts) ) {
                return $this->traverseJson($json->{$pointerPart}, $pointer, $pointerParts);
            }

        } elseif ( is_object($json) && empty($pointerPart) && array_key_exists('_empty_', get_object_vars($json)) ) {

            $pointerPart = '_empty_';

            if ( count($pointerParts) === 0 ) {
                return $json->{$pointerPart};
            }

            if ( (is_object($json->{$pointerPart}) || is_array($json->{$pointerPart})) && is_array($pointerParts) ) {
                return $this->traverseJson($json->{$pointerPart}, $pointer, $pointerParts);
            }

        } elseif ( $pointerPart === self::LAST_ARRAY_ELEMENT_CHAR && is_array($json) ) {
            return end($json);
        } elseif ( is_array($json) && count($json) < $pointerPart ) {
            // Do nothing, let Exception bubble up
        } elseif ( is_array($json) && array_key_exists($pointerPart, $json) && $json[$pointerPart] === null ) {
            return $json[$pointerPart];
        }

        $this->logAndThrowException(
            sprintf("JSON pointer '%s' references a nonexistent value", $pointer)
        );
    } // traverseJson()
}  // class JsonReferenceTransformer
