<?php

namespace TestHarness;

use CCR\Loggable;
use Configuration\Configuration;
use Configuration\iConfigFileKeyTransformer;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * Transform "$ref" pointers whose values are fragments only (i.e., that start
 * with "#", i.e., that refer to a section within the configuration file
 * itself) to prepend the name of the configuration file, since the default
 * JsonReferenceTransformer would otherwise fail to parse such a value because
 * it has an empty URL path.
 */
class JsonFragmentOnlyReferenceTransformer extends Loggable implements iConfigFileKeyTransformer
{
    const REFERENCE_KEY = '$ref';

    /**
     * @see iConfigFileKeyTransformer::keyMatches()
     */
    public function keyMatches($key)
    {
        return self::REFERENCE_KEY === $key;
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
        // If the value is a string that starts with a hashmark, prepend it
        // with the name of the configuration file.
        if ('string' === gettype($value) && '#' === substr($value, 0, 1)) {
            $value = $config->getFilename() . $value;
        }
        // Continue processing so the JsonReferenceTransformer can properly
        // transform the reference.
        return true;
    }
}
