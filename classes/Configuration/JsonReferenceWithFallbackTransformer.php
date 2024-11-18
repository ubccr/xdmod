<?php
/** =========================================================================================
 * This is the same as the JsonReferenceTransformer, but if the JSON pointer cannot be
 * transformed, it will fall back to transforming a different JSON pointer, and if that fails,
 * it will fall back to transforming a different JSON pointer, and so on.
 * ==========================================================================================
 */

namespace Configuration;

use CCR\Log;
use Exception;
use stdClass;

class JsonReferenceWithFallbackTransformer extends JsonReferenceTransformer implements iConfigFileKeyTransformer
{
    const REFERENCE_KEY = '$ref-with-fallback';

    /** -----------------------------------------------------------------------------------------
     * @see iConfigFileKeyTransformer::keyMatches()
     * ------------------------------------------------------------------------------------------
     */

    public function keyMatches($key)
    {
        return (self::REFERENCE_KEY == $key);
    }

    /** -----------------------------------------------------------------------------------------
     * Transform the first JSON pointer in the array into the actual JSON that it references.
     * If that fails, transform the next JSON pointer in the array into the actual JSON that it
     * references. Keep trying until a JSON pointer is successfully transformed or the array
     * of JSON pointers runs out (throw any exception caused by trying to transform the last
     * pointer in the array).
     *
     * @see iConfigFileKeyTransformer::transform()
     *
     * @throws Exception if there are multiple keys in $obj or if $value is not a non-empty,
     *                   non-associative array of strings.
     * ------------------------------------------------------------------------------------------
     */

    public function transform(&$key, &$value, stdClass $obj, Configuration $config, $exceptionLogLevel)
    {
        if (1 !== count(get_object_vars($obj))) {
            $this->logAndThrowException(
                sprintf(
                    'References cannot be mixed with other keys in an object: "%s"',
                    $key
                )
            );
        }
        $exceptionMessage = sprintf(
            'Value of "%s" must be a non-empty, non-associative array of strings',
            $key
        );
        if (!is_array($value) || array_keys($value) !== range(0, count($value) - 1)) {
            $this->logAndThrowException($exceptionMessage);
        }
        foreach ($value as $v) {
            if (!is_string($v)) {
                $this->logAndThrowException($exceptionMessage);
            }
        }
        for ($i = 0; $i < count($value); $i++) {
            try {
                $keepGoing = parent::transform(
                    $key,
                    $value[$i],
                    $obj,
                    $config,
                    Log::DEBUG
                );
                $value = $value[$i];
                return $keepGoing;
            } catch (Exception $e) {
                if ($i === count($value) - 1) {
                    $this->logAndThrowException($e->getMessage());
                } else {
                    $this->logger->debug('Falling back to next file');
                }
            }
        }
        return true;
    }
}
