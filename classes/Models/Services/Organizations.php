<?php

namespace Models\Services;

use CCR\DB;

class Organizations
{

    /**
     * Retrieve the organization that the user identified by the $userId parameter is associated
     * with.
     *
     * @param int $userId the id of the user for whom an associated organization ( if any ) is to be
     *                    retrieved.
     *
     * @return int the id of the organization $userId is associated with. If one is not found then
     *             -1 is returned.
     *
     * @throws \Exception if there is a problem retrieving a db connection.
     * @throws \Exception if there is a problem executing sql statements.
     */
    public static function getOrganizationForUser($userId)
    {
        $sql = <<<SQL
    SELECT src.organization_id
    FROM (
      SELECT user_org.*
      FROM (
      SELECT DISTINCT
          u.organization_id,
          2
        FROM moddb.Users u
        JOIN modw.organization o ON o.id = u.organization_id
        WHERE u.id = :user_id
        UNION
        SELECT DISTINCT
          o.id organization_id,
          1
        FROM moddb.Users u
        JOIN modw.person p ON p.id = u.person_id
        JOIN modw.organization o ON o.id = p.organization_id
        WHERE u.id = :user_id
      ) user_org
      ORDER BY 2
      LIMIT 1
    ) src;
SQL;
        $db = DB::factory('database');

        $rows = $db->query(
            $sql,
            array(':user_id' => $userId)
        );

        return count($rows) > 0 ? $rows[0]['organization_id'] : -1;
    }
}
