<?php

use CCR\DB;
use CCR\DB\iDatabase;
use Exception;
use Statistic;

/**
 * Class Statistics
 *
 * Statistics attempts to provide a central location for functionality that
 * pertains to the usage of the 'statistics' table and it's relations.
 *
 * The statistics table provides a location that can be queried at runtime for a
 * list of all the statistics currently supported by the system and their
 * associated information.
 *
 * The current iteration of this classes main function is to provide a method of
 * retrieving a users list of permitted statistics. It does lack full CRUD
 * functionality but that it is planned for latter addition.
 */
class Statistics
{
    public static function listStatistics()
    {
        return self::_listStatistics(
            DB::factory('database')
        );
    }

    public static function listPermittedStatistics(XDUser $user, $realmName, $groupByName)
    {
        if (!isset($user)) {
            throw new Exception('A valid user is required.');
        }

        if (!isset($realmName)) {
            throw new Exception('A valid realm is required.');
        }
        if (!isset($groupByName)) {
            throw new Exception('A valid group by is required.');
        }

        return self::_listPermittedStatistics(
            DB::factory('database'),
            $user->getUserID(),
            $realmName,
            $groupByName
        );
    }

    private static function _listStatistics(iDatabase $db)
    {
        $query = <<<SQL
SELECT s.*
FROM statistics s
SQL;
        $rows = $db->query($query);
        if ( count($rows) > 0 ) {
            return array_reduce(
                $rows,
                function ($carry, $item) {
                    $carry []= new Statistic($item);
                    return $carry;
                },
                array()
            );
        }
        return array();
    }

    private static function _listPermittedStatistics(iDatabase $db, $userId, $realmName, $groupByName)
    {
        $query = <<<SQL
SELECT DISTINCT s.name
FROM statistics s
  JOIN acl_group_bys agb
    ON s.statistic_id = agb.statistic_id
  JOIN user_acls ua
    ON agb.acl_id = ua.acl_id
  JOIN group_bys gb
    ON agb.group_by_id = gb.group_by_id
  JOIN realms r
    ON gb.realm_id = r.realm_id
  LEFT JOIN statistics_hierarchy sh
    ON sh.statistic_id = s.statistic_id
WHERE
  ua.user_id = :user_id
  AND r.name = :realm_name
  AND gb.name = :group_by_name
  AND agb.visible = TRUE
  AND agb.enabled = TRUE
ORDER BY COALESCE(sh.value, s.name);
SQL;

        $rows = $db->query($query, array(
            ':user_id' => $userId,
            ':realm_name' => $realmName,
            ':group_by_name' => $groupByName
        ));

        if ( count($rows) > 0 ) {
            return array_reduce(
                $rows,
                function ($carry, $item) {
                    $carry []= new Statistic($item);
                    return $carry;
                },
                array()
            );
        }
        return array();
    }
}
