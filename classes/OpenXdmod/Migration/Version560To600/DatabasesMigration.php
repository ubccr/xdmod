<?php

namespace OpenXdmod\Migration\Version560To600;

/**
 * Migrate databases from version 5.6.0 to 6.0.0.
 */
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        parent::execute();

        $this->migrateSearchHistory();
    }

    private function modifySearchTerms($data)
    {
        $modified = array();
        foreach ($data as $datum) {
            if (count($datum['searchterms']) == 0) {
                // No search terms were saved for this entry
                continue;
            }

            if (isset($datum['searchterms']['realm'])) {
                $output = array("params" => $datum['searchterms']);

                if (isset($output['params']['params']) && !is_string($output['params']['params'])) {
                    $output['params']['params'] = json_encode($datum['searchterms']['params']);
                }

                $datum['searchterms'] = $output;
                $modified[] = $datum;
            }
        }
        return $modified;
    }

    private function migrateSearchHistory()
    {
        $dbhandle= \CCR\DB::factory('datawarehouse');

        $userlist = $dbhandle->query('SELECT u.id FROM moddb.UserProfiles up, moddb.Users u 
            WHERE up.serialized_profile_data like "%searchhistory-SUPREMM%" and u.id = up.user_id');

        for ($i = 0; $i < count($userlist); $i++) {
            $user_id = $userlist[$i]['id'];

            $user = \XDUser::getUserByID($user_id);
            $storage = new \UserStorage(
                $user,
                \NewRest\Controllers\WarehouseControllerProvider::_HISTORY_STORE . '-SUPREMM'
            );

            $modifiedSearches = $this->modifySearchTerms($storage->get());

            foreach ($modifiedSearches as $update) {
                $storage->upsert($update['recordid'], $update);
            }

            if (count($modifiedSearches) > 0) {
                $this->logger->info('Updated ' . count($modifiedSearches) .
                    ' JobViewer SearchHistory records for user ' . $user->getUsername() . "\n");
            }
        }
    }
}
