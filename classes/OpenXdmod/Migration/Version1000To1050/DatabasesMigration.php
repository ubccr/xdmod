<?php
/**
 * Update database from version 10.0.0 to 10.5.0.
 */

namespace OpenXdmod\Migration\Version1000To1050;

use OpenXdmod\Migration\DatabasesMigration as AbstractDatabasesMigration;
use OpenXdmod\Setup\Console;
use CCR\DB;
use ETL\Utilities;

/**
 * Migrate databases from version 10.0.0 to 10.5.0.
 */
class DatabasesMigration extends AbstractDatabasesMigration
{
    public function execute()
    {
        parent::execute();

    }
}
