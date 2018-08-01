<?php
/* ==========================================================================================
 * The Configuration class supports transforming configuration keys and their associated
 * values by defining transformers for keys matching a particular pattern. If a key in the
 * configuration file matches the pattern supported by a particular transformer, that
 * transforer will be called to modify the matching key and/or value in the
 * configuration. This is useful for removing comments and including references.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-11
 *
 * @see aAction
 * ==========================================================================================
 */

namespace Configuration;

use Log;
use stdClass;

interface iConfigFileKeyTransformer
{
    /* ------------------------------------------------------------------------------------------
     * Set up the transformer.
     *
     * @param Log $logger Optional Log object to support logging during transformation.
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(Log $logger = null);

    /* ------------------------------------------------------------------------------------------
     * Return TRUE if the key is supported by this transformer
     *
     * @param string $key Key to check
     *
     * @return TRUE if they key is processed by this transformer
     * ------------------------------------------------------------------------------------------
     */

    public function keyMatches($key);

    /* ------------------------------------------------------------------------------------------
     * Transform the data. Both the key and the value may be modified and will be returned
     * by reference.
     *
     * @param string $key Reference to the key, may be altered.
     * @param mixed $value Reference to the value (scalar, object, array), may be altered.
     * @param stdClass $obj The object where the key was found.
     * @param Configuration $config The Configuration object that called this method.
     *
     * @return FALSE if transfomer processing should stop for this key, TRUE otherwise.
     * ------------------------------------------------------------------------------------------
     */
    public function transform(&$key, &$value, stdClass $obj, Configuration $config);
}  // interface iConfigFileKeyTransformer
