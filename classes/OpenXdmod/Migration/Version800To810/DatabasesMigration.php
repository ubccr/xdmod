<?php

namespace OpenXdmod\Migration\Version800To810;

use CCR\DB;
use OpenXdmod\DataWarehouseInitializer;
use OpenXdmod\Setup\Console;
use XDUser;
use UserStorage;
use ETL\Configuration\EtlConfiguration;
use ETL;
use ETL\OverseerOptions;
use ETL\Overseer;
use ETL\Utilities;

/**
* Migrate databases from version 8.0.0 to 8.1.0.
*/
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    /**
     * The identifier that is used to store 'queries' in the user profile.
     *
     * @var string
     */
    const _QUERIES_STORE = 'queries_store';

    /**
     * The identifier that was used to store 'queries' in the user profile.
     * This will be used to find old values to migrate to the new storage
     * methodology.
     *
     * @var string
     */
    const _OLD_QUERIES_STORE = 'queries';

    /**
     * The identifier that is used to store metadata about the queries.
     *
     * @var string
     */
    const _QUERY_METADATA = 'query_metadata';

    /**
     *
     * @var string
     */
    const _QUERIES_MIGRATED = 'queries_migrated';

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

        $db = DB::factory('database');

        $result = $db->query('SELECT id FROM Users');
        foreach ($result as $row)
        {
            $user = XDUser::getUserByID($row['id']);
            $this->migrateMetricExplorerQueries($user);
        }
    }

    /**
     * migrate the chart store query data for the provided user.
     * @param XDUser $user
     */
    private function migrateMetricExplorerQueries(XDUser $user)
    {
        $metaData = new UserStorage($user, self::_QUERY_METADATA);
        $migrated = $this->isMetaDataValid($metaData);

        $queries = new UserStorage($user, self::_QUERIES_STORE);

        if (!$migrated) {
            $this->migrateOldQueries($user, $queries);
        }

        $oldQueryProfile = $user->getProfile();
        $oldQueryProfile->dropValue(self::_OLD_QUERIES_STORE);
        $oldQueryProfile->save();

        $metaData->del();
    }

    /**
     * A helper function that is used to migrate / clean up users old query
     * stores to the new ones.
     *
     * @param XDUser $user
     * @param UserStorage $queries
     *
     * @throws \Exception
     */
    private function migrateOldQueries(XDUser $user, UserStorage $queries)
    {
        $profile = $user->getProfile();
        $oldQueries = $profile->fetchValue(self::_OLD_QUERIES_STORE);

        if (isset($oldQueries) && !is_array($oldQueries)) {
            $oldQueries = json_decode($oldQueries, true);
        }

        if (isset($oldQueries['data'])) {
            $oldQueries = $oldQueries['data'];
            if (isset($oldQueries) && !is_array($oldQueries)) {
                $oldQueries = json_decode($oldQueries, true);
            }
        }

        if (isset($oldQueries) && count($oldQueries) > 0) {
            $validQueries = $this->retrieveValidQueries($oldQueries);
            if (count($validQueries) > 0) {
                foreach ($validQueries as $query) {
                    $queries->insert($query);
                }
            }
        }
    }

    /**
     * Another helper function that just encapsulates the logic of what it means
     * to filter valid queries from the old queries.
     *
     * @param array $oldQueries the array that will be used as a source for valid
     *                          queries.
     *
     * @return array of queries that contain non-null and non-empty string name
     *               and config properties.
     */
    private function retrieveValidQueries(array $oldQueries)
    {
        $results = array();
        foreach ($oldQueries as $oldQuery) {
            $hasName = !empty($oldQuery['name']);
            $hasConfig = !empty($oldQuery['config']);
            $isValid = $hasName && $hasConfig;

            if ($isValid) {
                $results[] = $oldQuery;
            }
        }
        return $results;
    }

    /**
     * @param UserStorage $metaData
     * @return bool
     */
    public function isMetaDataValid(UserStorage $metaData)
    {

        $meta = $this->toArray($metaData->get());

        if (count($meta) < 1 || (count($meta) > 1 && !isset($meta[0][self::_QUERIES_MIGRATED]))) {
            return false;
        }

        return true;
    }

    /**
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function toArray($value)
    {
        return is_string($value) ? json_decode($value) : $value;
    }
}
