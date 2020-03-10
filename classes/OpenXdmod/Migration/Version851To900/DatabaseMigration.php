<?php
namespace OpenXdmod\Migration\Version851To900;

use Exception;
use OpenXdmod\Setup\Console;
use FilterListBuilder;
use CCR\DB;

/**
* Migrate databases from version 8.5.1 to 9.0.0.
*/
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    public function execute()
    {
        parent::execute();

        $dbh = DB::factory('datawarehouse');
        $dbh->execute("UPDATE moddb.batch_export_requests SET realm = 'Jobs' WHERE realm = 'jobs'");
        $dbh->execute("UPDATE moddb.batch_export_requests SET realm = 'SUPREMM' WHERE realm = 'supremm'");

        $dbh->execute('DROP TABLE IF EXISTS modw_filters.Storage_person___username');
        $dbh->execute('DROP TABLE IF EXISTS modw_filters.Storage_pi___username');
        $dbh->execute('DROP TABLE IF EXISTS modw_filters.Storage_username');
        $this->logger->notice('Rebuilding filter lists');
        try {
            $builder = new FilterListBuilder();
            $builder->setLogger($this->logger);
            $builder->buildRealmLists('Storage');
            $this->logger->notice('Done building filter lists');
        } catch (Exception $e) {
            $this->logger->warning('Failed to build filter list: '  . $e->getMessage());
            $this->logger->warning('You may need to run xdmod-build-filter-lists manually');
        }
    }
}
