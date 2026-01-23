<?php

namespace OpenXdmod\Migration;

use CCR\Helper\SymfonyCommandHelper;
use Xdmod\Template;

class DotEnvConfigMigration extends Migration
{

    public function execute()
    {
        $dotEnvPath = BASE_DIR . '/.env';
        if (!file_exists($dotEnvPath)) {
            $envTemplate = new Template('env');
            $envTemplate->apply([
                'app_secret' => hash('sha512', time())
            ]);
            file_put_contents(BASE_DIR . '/.env', $envTemplate->getContents());

            // Make sure to clear the cache before dumping the dotenv so we start clean.
            try {
                SymfonyCommandHelper::executeCommand('cache:clear');
            } catch (\Exception $e) {
                throw new \RuntimeException('Error occurred executing cache:clear', $e);
            }


            // Dump dotenv data so we don't read .env each time in prod.
            // Note: this means that if you want to start debugging stuff you'll need to delete the generated .env.
            try {
                SymfonyCommandHelper::executeCommand("dotenv:dump");
            } catch (\Exception $e) {
                throw new \RuntimeException('Error occurred executing dotenv:dump', $e);
            }

        }
    }

}
