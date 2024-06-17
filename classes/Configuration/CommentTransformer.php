<?php
/* ==========================================================================================
 * Remove comments from configuration files. Comments are defined as any key starting with
 * a '#'.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-11
 * ==========================================================================================
 */

namespace Configuration;

use Psr\Log\LoggerInterface;
use stdClass;
use CCR\Loggable;

class CommentTransformer extends Loggable implements iConfigFileKeyTransformer
{
    const COMMENT_PREFIX = '#';

    /* ------------------------------------------------------------------------------------------
     * @see iConfigFileKeyTransformer::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }  // construct()

    /* ------------------------------------------------------------------------------------------
     * @see iConfigFileKeyTransformer::keyMatches()
     * ------------------------------------------------------------------------------------------
     */

    public function keyMatches($key)
    {
        return $key[0] === self::COMMENT_PREFIX;
    }  // keyMatches()

    /* ------------------------------------------------------------------------------------------
     * Comments remove both the key and the value from the configuration and stop processing of the
     * key.
     *
     * @see iConfigFileKeyTransformer::transform()
     * ------------------------------------------------------------------------------------------
     */

    public function transform(&$key, &$value, stdClass $obj, Configuration $config, $exceptionLogLevel)
    {
        $key = null;
        $value = null;

        return false;
    }  // transform()
}  // class CommentTransformer
