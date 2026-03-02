<?php

namespace OpenXdmod\Migration;

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

            $cmdBase = 'APP_ENV=prod APP_DEBUG=0';
            $console = BIN_DIR .'/console';

            // Make sure to clear the cache before dumping the dotenv so we start clean.
            $this->executeCommand("$cmdBase $console cache:clear");

            // Dump dotenv data so we don't read .env each time in prod.
            // Note: this means that if you want to start debugging stuff you'll need to delete the generated .env.
            $this->executeCommand("$cmdBase $console dotenv:dump");
        }
    }

    protected function executeCommand($command)
    {
        $output    = array();
        $returnVar = 0;

        exec($command . ' 2>&1', $output, $returnVar);

        if ($returnVar != 0) {
            $msg = "Command exited with non-zero return status:\n"
                . "command = $command\noutput =\n" . implode("\n", $output);
            throw new \Exception($msg);
        }

        return $output;
    }


}
