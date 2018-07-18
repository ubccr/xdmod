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
      SELECT
          u.organization_id,
          2
        FROM moddb.Users u
        JOIN modw.organization o ON o.id = u.organization_id
        WHERE u.id = :user_id
        UNION
        SELECT
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

    /**
     * Retrieve the name for the organization identified by the provided id.
     *
     * @param int $organizationId the id of the organization whose name is to be retrieved.
     *
     * @return string|null Returns null if the organization could not be found, else the `name`
     *                     value is returned.
     *
     * @throws \Exception if there is a problem retrieving a db connection
     * @throws \Exception if there is a problem executing sql
     */
    public static function getNameById($organizationId)
    {
        $query = "SELECT o.name FROM modw.organization o WHERE o.id = :organization_id";
        $params = array(
            ':organization_id' => $organizationId
        );

        $db = DB::factory('database');

        $rows = $db->query($query, $params);
        return count($rows) > 0 ? $rows[0]['name'] : UNKNOWN_ORGANIZATION_NAME;
    }
}
