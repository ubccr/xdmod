<?php declare(strict_types=1);

namespace CCR\EnvVar;

use Symfony\Component\DependencyInjection\EnvVarLoaderInterface;

class ReCaptchaEnvVarLoader implements EnvVarLoaderInterface
{

    public function loadEnvVars(): array
    {
        try {
            $portalSettings = \xd_utilities\loadConfiguration();
        } catch(\Exception $e) {
            throw new \RuntimeException('An error occurred while trying to load portal_settings.ini', 0, $e);
        }


        try {
            $siteKey = $portalSettings['mailer']['captcha_public_key'];
            $privateKey = $portalSettings['mailer']['captcha_private_key'];
        } catch (\Exception $e) {
            throw new \RuntimeException('Missing Google ReCaptcha settings in portal_settings.ini', 0, $e);
        }

        return [
            'GOOGLE_RECAPTCHA_SITE_KEY' => $siteKey,
            'GOOGLE_RECAPTCHA_SECRET' => $privateKey
        ];
    }
}
