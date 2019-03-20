<?php

namespace OpenXdmod\Migration\Version800To810;

use CCR\DB;
use OpenXdmod\DataWarehouseInitializer;
use OpenXdmod\Setup\Console;
use XDUser;
use UserStorage;
use FilterListBuilder;

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

        $console = Console::factory();

        $hpcdbDb = DB::factory('hpcdb');
        $dwDb = DB::factory('datawarehouse');
        $dwi = new DataWarehouseInitializer($hpcdbDb, $dwDb);
        $db = DB::factory('database');
        if($dwi->isRealmEnabled('Cloud')){
            $console->displayMessage(<<<"EOT"

!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

There have been updates to cloud data the current data is INVALID.
If you need the current data back it up NOW.
After you press enter it will be removed.
Canceling the upgrade process now will break XDMoD.

After the upgrade is complete re-ingest and aggregate your cloud data using the
commands recommended in our documentation.

!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

EOT
            );
            $console->prompt("Press ENTER to continue.");

            $db->execute('
                DROP TABLE IF EXISTS `modw_cloud`.`account`;
                DROP TABLE IF EXISTS `modw_cloud`.`asset`;
                DROP TABLE IF EXISTS `modw_cloud`.`asset_type`;
                DROP TABLE IF EXISTS `modw_cloud`.`avail_zone`;
                DROP TABLE IF EXISTS `modw_cloud`.`cloud_events_transient`;
                DROP TABLE IF EXISTS `modw_cloud`.`cloud_resource_metadata`;
                DROP TABLE IF EXISTS `modw_cloud`.`cloudfact_by_day`;
                DROP TABLE IF EXISTS `modw_cloud`.`cloudfact_by_month`;
                DROP TABLE IF EXISTS `modw_cloud`.`cloudfact_by_quarter`;
                DROP TABLE IF EXISTS `modw_cloud`.`cloudfact_by_year`;
                DROP TABLE IF EXISTS `modw_cloud`.`event`;
                DROP TABLE IF EXISTS `modw_cloud`.`event_asset`;
                DROP TABLE IF EXISTS `modw_cloud`.`event_reconstructed`;
                DROP TABLE IF EXISTS `modw_cloud`.`event_type`;
                DROP TABLE IF EXISTS `modw_cloud`.`generic_cloud_raw_event`;
                DROP TABLE IF EXISTS `modw_cloud`.`generic_cloud_raw_instance_type`;
                DROP TABLE IF EXISTS `modw_cloud`.`generic_cloud_raw_volume`;
                DROP TABLE IF EXISTS `modw_cloud`.`generic_cloud_staging_event`;
                DROP TABLE IF EXISTS `modw_cloud`.`host`;
                DROP TABLE IF EXISTS `modw_cloud`.`image`;
                DROP TABLE IF EXISTS `modw_cloud`.`instance`;
                DROP TABLE IF EXISTS `modw_cloud`.`instance_data`;
                DROP TABLE IF EXISTS `modw_cloud`.`instance_type`;
                DROP TABLE IF EXISTS `modw_cloud`.`job_record_event`;
                DROP TABLE IF EXISTS `modw_cloud`.`memory_buckets`;
                DROP TABLE IF EXISTS `modw_cloud`.`openstack_event_map`;
                DROP TABLE IF EXISTS `modw_cloud`.`openstack_raw_event`;
                DROP TABLE IF EXISTS `modw_cloud`.`openstack_raw_instance_type`;
                DROP TABLE IF EXISTS `modw_cloud`.`openstack_staging_event`;
                DROP TABLE IF EXISTS `modw_cloud`.`processor_buckets`;
                DROP TABLE IF EXISTS `modw_cloud`.`record_type`;
                DROP TABLE IF EXISTS `modw_cloud`.`region`;
                DROP TABLE IF EXISTS `modw_cloud`.`user`;
            ');
        }

        $result = $db->query('SELECT id FROM Users');
        foreach ($result as $row)
        {
            $user = XDUser::getUserByID($row['id']);
            $this->migrateMetricExplorerQueries($user);
        }

        $console->displayMessage(<<<"EOT"
This release includes an update that enables the Job Viewer tab and the 'show raw
data' drilldown in the Metric Explorer for Jobs realm data. It is recommended
to reaggregate all jobs. Depending on the amount of data this could take multiple
hours. If the job data is not reaggregated then existing jobs will not be viewable
in the Job Viewer.
EOT
        );
        $runaggregation = $console->promptBool(
            'Do you want to run aggregation now?',
            false
        );
        if (true === $runaggregation) {
            $this->runEtlv2(
                array(
                    'process-sections' => array('jobs-xdw-aggregate'),
                    'last-modified-start-date' => date('Y-m-d', strtotime('2000-01-01')),
                )
            );
            $this->logger->notice('Rebuilding filter lists');
            try {
                $builder = new FilterListBuilder();
                $builder->setLogger($this->logger);
                $builder->buildRealmLists('Jobs');
            } catch (Exception $e) {
                $this->logger->notice('Failed BuildAllLists: '  . $e->getMessage());
                $this->logger->crit(array(
                    'message'    => 'Filter list building failed: ' . $e->getMessage(),
                    'stacktrace' => $e->getTraceAsString(),
                ));
                throw new \Exception('Filter list building failed: ' . $e->getMessage());
            }
            $this->logger->notice('Done building filter lists');
        }
        else {
            $console->displayMessage(<<<"EOT"
Aggregation not run.  Aggregation may be run manually with the following command:
xdmod-ingestor --aggregate --last-modified-start-date '2000-01-01'
EOT
            );
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
