<?php

namespace Configuration;

use CCR\Loggable;
use Log;
use stdClass;

class ModuleTransformer extends Loggable implements iConfigFileKeyTransformer
{

    const KEY = 'module';

    public function __construct(Log $logger = null)
    {
        parent::__construct($logger);
    }

    public function keyMatches($key)
    {
        return self::KEY === $key;
    }

    public function transform(&$key, &$value, stdClass $obj, Configuration $config)
    {
        if ($config instanceof ModuleConfiguration) {
            $config->setModule($value);
        }
    }
}