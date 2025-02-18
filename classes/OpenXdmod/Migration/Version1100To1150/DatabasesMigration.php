<?php
/**
 * Update database from version 11.0.0 to 11.5.0
 */

namespace OpenXdmod\Migration\Version1100To1150;

use OpenXdmod\Migration\DatabasesMigration as AbstractDatabasesMigration;
use ETL\Utilities;

/**
 * Migrate databases from version 11.0.0 to 11.5.0
 */
class DatabasesMigration extends AbstractDatabasesMigration
{
    public function execute()
    {
        parent::execute();

        Utilities::runEtlPipeline(['ingest-organizations'], $this->logger);
    }
}
