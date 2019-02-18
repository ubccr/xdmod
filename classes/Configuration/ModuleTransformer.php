<?php

namespace Configuration;

use CCR\Loggable;
use Log;
use stdClass;

/**
 * Class ModuleTransformer
 *
 * This transformer will, if the `Configuration` object being transformed is an instance of
 * `ModuleConfiguration` and if there exists a `module` key, set this `ModuleConfigurations`
 * `module` property to the `module` key's value if is not the same as its current module value.
 *
 * @package Configuration
 */
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
        if ($config instanceof ModuleConfiguration ) {
            $currentModule = $config->getModule();
            // Only set the module if it has not been set yet.
            if ($currentModule !== $value) {
                $config->setModule($value);
            }
        }
    }
}
