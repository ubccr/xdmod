<?php

namespace Configuration;

use CCR\Loggable;
use Log;
use stdClass;

class PlusKeyTransformer extends Loggable implements iConfigFileKeyTransformer
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