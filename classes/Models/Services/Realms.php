<?php namespace Models\Services;

use CCR\DB;
use Models\Realm;

class Realms
{
    public static function getRealms()
    {
        $db = DB::factory('database');

        $results = $db->query("SELECT r.* FROM realms r ORDER BY r.realm_id");
        return array_reduce($results, function ($carry, $item) {
            $carry[] = new Realm($item);
            return $carry;
        }, array());
    }

    /**
     * Retrieve the list of realms that the provided $user is authorized to view.
     *
     * @param \XDUser $user the user for whom the realms should be retrieved.
     *
     * @return array
     *
     * @throws \Exception if there is a problem retrieving a db connection
     * @throws \Exception if there is a problem executing the sql statement.
     */
    public static function getRealmsForUser(\XDUser $user)
    {
        $query = <<<SQL
            SELECT DISTINCT
              r.display AS realm
            FROM acl_group_bys agb
              JOIN user_acls ua ON agb.acl_id = ua.acl_id
              JOIN realms r ON r.realm_id = agb.realm_id
            WHERE ua.user_id = :user_id
            ORDER BY r.realm_id
SQL;
        $params = array(
            ':user_id'=> $user->getUserID()
        );

        $db = DB::factory('database');
        $rows = $db->query($query, $params);

        return array_reduce($rows, function ($carry, $item) {
            $carry[] = $item['realm'];
            return $carry;
        }, array());
    }
}
