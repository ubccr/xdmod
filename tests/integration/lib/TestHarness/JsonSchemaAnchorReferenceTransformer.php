<?php

namespace TestHarness;

use CCR\Loggable;
use Configuration\Configuration;
use Configuration\iConfigFileKeyTransformer;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * When encountering "$ref" pointers within JSON Schema objects (i.e., objects
 * that have a "$schema" property) for which the value of the pointer refers to
 * a named anchor within the schema (i.e., is only a fragment, i.e., starts
 * with "#"), stop further processing so that the
 * @see Configuration\JsonReferenceTransformer does not try to parse such a
 * value, because it will fail due to the URL path being empty.
 *
 * For example, in the following object, the "$ref" pointer will not undergo
 * any transformation.
 * {
 *     "$schema": "http://json-schema.org/draft-07/schema#",
 *     "foo": {
 *         "$ref": "#/defs/bar"
 *     },
 *     "$defs": {
 *         "bar": "baz"
 *     }
 * }
 *
 * This makes it so that, e.g., a JSON Schema validator can later parse the
 * "$ref".
 */
class JsonSchemaAnchorReferenceTransformer extends Loggable implements iConfigFileKeyTransformer
{
    /**
     * @see iConfigFileKeyTransformer::keyMatches()
     */
    public function keyMatches($key)
    {
        return '$ref' === $key;
    }

    /**
     * @see iConfigFileKeyTransformer::transform()
     */
    public function transform(
        &$key,
        &$value,
        stdClass $obj,
        Configuration $config
    ) {
        // If we are in a schema object, and the value is a string that starts
        // with a hashmark, stop further processing of the key.
        if (
            method_exists($config, 'inSchema')
            && $config->inSchema()
            && 'string' === gettype($value)
            && '#' === substr($value, 0, 1)
        ) {
            return false;
        }
        // Otherwise, let the JsonReferenceTransformer do its transformation.
        return true;
    }
}
