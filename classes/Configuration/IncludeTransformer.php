<?php
/** =========================================================================================
 * Process the "$include" directive to include the contents of a file.
 * ==========================================================================================
 */

namespace Configuration;

use CCR\Log;
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
     * Include the contents of the file as the value of the specified key.
     *
     * @see iConfigFileKeyTransformer::transform()
     * ------------------------------------------------------------------------------------------
     */

    public function transform(&$key, &$value, stdClass $obj, Configuration $config, $exceptionLogLevel)
    {

        if( count(get_object_vars($obj)) != 1 ) {
            $this->logAndThrowException(
                sprintf('References cannot be mixed with other keys in an object: "%s": "%s"', $key, $value),
                array('log_level' => $exceptionLogLevel)
            );
        }

        $parsedUrl = null;
        $contents = $this->getContentsFromUrl($value, $config, $exceptionLogLevel);
        $key = null;
        $value = $contents;

        return false;

    }  // transform()
}  // class IncludeTransformer
