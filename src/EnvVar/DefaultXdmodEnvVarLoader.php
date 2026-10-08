<?php declare(strict_types=1);

namespace CCR\EnvVar;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\EnvVarLoaderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class DefaultXdmodEnvVarLoader implements EnvVarLoaderInterface
{
    public function __construct(
        protected ContainerBagInterface $parameters,
        protected LoggerInterface $logger
    ) {
    }

    public function loadEnvVars(): array
    {
        try {
            $portalSettings = \xd_utilities\loadConfiguration();
        } catch(\Exception $e) {
            throw new \RuntimeException('An error occurred while trying to load portal_settings.ini', 0, $e);
        }

        try {
            $appSecret = $portalSettings['general']['application_secret'];
            $debugMode = $portalSettings['general']['debug_mode'];
            $appEnv = $debugMode === null || $debugMode === 'off' ? 'prod' : 'dev';
        } catch(\Exception $e) {
            $appEnv = 'prod';
            $appSecret = hash('sha512', (string) time());
        }

        return [
            'APP_ENV' => $appEnv,
            'APP_SECRET' => $appSecret
        ];
    }
}
