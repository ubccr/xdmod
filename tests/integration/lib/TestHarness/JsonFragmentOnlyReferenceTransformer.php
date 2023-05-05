<?php

namespace TestHarness;

use Configuration\Configuration;
use Configuration\iConfigFileKeyTransformer;
use Configuration\JsonReferenceTransformer;
use stdClass;

/**
 * Prevent the JsonReferenceTransformer from attempting to transform references
 * that contain only fragments (e.g., "$ref": "#/foo"), since it would fail to
 * do so due to the path being empty. The reference should be preserved so
 * that, e.g., a JSON schema validator can process it later.
 */
class JsonFragmentOnlyReferenceTransformer extends JsonReferenceTransformer implements iConfigFileKeyTransformer
{
    /**
     * @see iConfigFileKeyTransformer::transform()
     */
    public function transform(
        &$key,
        &$value,
        stdClass $obj,
        Configuration $config
    ) {
        // If the reference starts with a fragment, stop further processing.
        if ('#' === substr($value, 0, 1)) {
            return false;
        }
        // Otherwise, ignore the reference and continue processing.
        return true;
    }
}
