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
        $appSecret = $this->parameters->get('xdmod.portal_settings.general.application_secret');
        $debugMode = $this->parameters->get('xdmod.portal_settings.general.debug_mode');
        $appEnv = $debugMode === null || $debugMode === 'off' ? 'prod' : 'dev';
        return [
            'APP_ENV' => $appEnv,
            'APP_SECRET' => $appSecret ?? hash('sha512', (string) time())
        ];
    }
}
