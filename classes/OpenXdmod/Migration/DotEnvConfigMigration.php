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
            // .env doesn't need anything in it, but it does need to exist.
            file_put_contents(BASE_DIR . '/.env', '');
            SymfonyCommandHelper::dumpDotEnv();
        }
    }
}
