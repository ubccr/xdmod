<?php

namespace Models\Services;

use CCR\DB;

class Organizations
{

    /**
     * @param int $userId
     * @throws \Exception
     */
    public static function getOrganizationForUser($userId)
    {
        $sql = <<<SQL
SELECT src.organization_id
    FROM (
      SELECT user_org.*
      FROM (
        SELECT DISTINCT
          uagbp.value organization_id,
          1
        FROM moddb.user_acl_group_by_parameters uagbp
        JOIN modw.organization o ON uagbp.value = o.id
        JOIN modw.resourcefact rf ON o.id = rf.organization_id
        JOIN moddb.group_bys gb
          ON uagbp.group_by_id = gb.group_by_id AND gb.name = 'provider'
        JOIN moddb.acls a ON uagbp.acl_id = a.acl_id AND a.name = 'cd'
        WHERE uagbp.user_id = :user_id
        UNION
        SELECT DISTINCT
          u.organization_id,
          2
        FROM moddb.Users u
        JOIN modw.organization o ON o.id = u.organization_id
        JOIN modw.resourcefact rf ON o.id = rf.organization_id
        WHERE u.id = :user_id
        UNION
        SELECT DISTINCT
          o.id organization_id,
          3
        FROM moddb.Users u
        JOIN modw.person p ON p.id = u.person_id
        JOIN modw.organization o ON o.id = p.organization_id
        JOIN modw.resourcefact rf ON o.id = rf.organization_id
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

        return count($rows) > 0 ? $rows[0]['organization_id'] : UNKNOWN_ORGANIZATION_ID;
    }
}