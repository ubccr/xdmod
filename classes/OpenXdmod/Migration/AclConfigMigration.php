<?php

namespace OpenXdmod\Migration;

/**
 * A simple migration that just runs acl-config.
 *
 * @package OpenXdmod\Migration
 */
class AclConfigMigration extends Migration
{

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $cmd = BIN_DIR . '/acl-config';

        $output = shell_exec($cmd);
        $hadError = str_contains($output, 'error');

        if ($hadError) {
            $this->logger->error($output);
        }
    }
}
