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

        if ($output === false) {
            $this->logger->error("Error executing acl-config");
        } else if ($output !== null) {
            $this->logger->error($output);
        }
    }
}
