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
        // NOTE: we assume that acl-config is in PATH as that's how we have it setup.
        $cmd = 'acl-config';

        $output = shell_exec($cmd);
        $hadError = strpos($output, 'error') !== false;

        if ($hadError) {
            $this->logger->err($output);
        }
    }
}
