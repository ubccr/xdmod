<?php

namespace OpenXdmod\Migration\Version800To810;

use CCR\DB;
use OpenXdmod\DataWarehouseInitializer;
use OpenXdmod\Setup\Console;

/**
* Migrate databases from version 8.0.0 to 8.1.0.
*/
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    /**
     * @see \OpenXdmod\Migration\Migration::execute
     **/
    public function execute()
    {
        parent::execute();

        $hpcdbDb = DB::factory('hpcdb');
        $dwDb = DB::factory('datawarehouse');
        $dwi = new DataWarehouseInitializer($hpcdbDb, $dwDb);

        if($dwi->isRealmEnabled('Cloud')){
            $console = Console::factory();
            $console->displayMessage(<<<"EOT"
There have been updates to cloud aggregation statistics to make the data more accurate.
If you have the Cloud realm enabled it is recommended that you re-ingest and aggregate
your cloud data using the commands recommended in our documentation.
EOT
            );
        }
    }
}
