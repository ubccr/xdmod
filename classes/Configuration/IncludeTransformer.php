<?php
/** =========================================================================================
 * Process the "$include" directive to include the contents of a file as a JSON encoded string.
 * ==========================================================================================
 */

namespace Configuration;

use stdClass;

class IncludeTransformer extends aUrlTransformer implements iConfigFileKeyTransformer
{
    const REFERENCE_KEY = '$include';

    /** -----------------------------------------------------------------------------------------
     * @see iConfigFileKeyTransformer::keyMatches()
     * ------------------------------------------------------------------------------------------
     */

    public function keyMatches($key)
    {
        return (self::REFERENCE_KEY == $key);
    }  // keyMatches()

    /** -----------------------------------------------------------------------------------------
     * Include the JSON-endoced contents of the file as the value of the specified key.
     *
     * @see iConfigFileKeyTransformer::transform()
     * ------------------------------------------------------------------------------------------
     */

    public function transform(&$key, &$value, stdClass $obj, Configuration $config)
    {

        if( count(get_object_vars($obj)) != 1 ) {
            $this->logAndThrowException(
                sprintf('References cannot be mixed with other keys in an object: "%s": "%s"', $key, $value)
            );
        }

        $parsedUrl = null;
        $contents = $this->getContentsFromUrl($value, $parsedUrl, $config);
        $key = null;
        $value = json_encode($contents);

        return false;

    }  // transform()
}  // class IncludeTransformer
