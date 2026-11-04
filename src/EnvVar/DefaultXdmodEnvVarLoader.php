<?php declare(strict_types=1);

namespace CCR\EnvVar;

use CCR\Configuration\IniConfiguration;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\EnvVarLoaderInterface;

/**
 * This class is responsible for populating required Symfony environment variables with values from
 * portal_settings.[ini|d]. If either of these values are not set, then we default to a `prod` environment and a
 */
class DefaultXdmodEnvVarLoader implements EnvVarLoaderInterface
{
    public function __construct(
        protected IniConfiguration $portalSettings,
        protected LoggerInterface $logger
    ) {
    }

    public function loadEnvVars(): array
    {
        try {
            $debugMode = $this->portalSettings->getConfigurationOption('general', 'debug_mode');
            $appEnv = $debugMode === null || $debugMode === 'off' ? 'prod' : 'dev';
        } catch(\Exception $e) {
            // We set the default to `prod` so that we do not expose any sensitive information.
            $appEnv = 'prod';
        }

        // While we can have a default value for the application environment, we cannot rely on a dynamically generated
        // value for the application secret. If this value is not set, then we must throw an exception.
        $appSecret = $this->portalSettings->getConfigurationOption('general', 'application_secret');

        return [
            'APP_ENV' => $appEnv,
            'APP_SECRET' => $appSecret
        ];
    }
}
