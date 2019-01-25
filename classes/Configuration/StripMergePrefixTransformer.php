<?php

namespace Configuration;

use CCR\Loggable;
use Log;
use stdClass;

/**
 * Class StripMergePrefixTransformer
 *
 * This class allows config files designed to work with `Config` to work with the new
 * `Configuration` class. It accomplishes this by stripping the merge prefix "+" from keys. This
 * works because the default merge behavior of `Configuration` is the same as `Config` w/ '+'
 * prefixed keys.
 *
 * @package Configuration
 */
class StripMergePrefixTransformer extends Loggable implements iConfigFileKeyTransformer
{
    const MERGE_PREFIX = '+';

    /**
     * @see iConfigFileKeyTransformer::__construct()
     */
    public function __construct(Log $logger = null)
    {
        parent::__construct($logger);
    }

    /**
     * @see iConfigFileKeyTransformer::keyMatches()
     */
    public function keyMatches($key)
    {
        return substr($key, 0, 1) === self::MERGE_PREFIX;
    }

    /**
     * @see iConfigFileKeyTransformer::transform()
     */
    public function transform(&$key, &$value, stdClass $obj, Configuration $config)
    {
        $key = substr($key, 1, strlen($key) - 1);

        return true;
    }
}
