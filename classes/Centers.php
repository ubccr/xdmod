<?php

use CCR\DB;
use CCR\DB\iDatabase;

class Centers
{

    public static function listStaffForUser(XDUser $user)
    {
        if (!isset($user)) {
            throw new Exception('A valid user must be provided.');
        }

        return self::_listStaffForUser(
            DB::factory('database'),
            $user
        );
    }

    /**
     * @param iDatabase $db
     * @param XDUser $user
     * @return array
     */
    private static function _listStaffForUser(iDatabase $db, XDUser $user)
    {
        $centerStaffName = ROLE_ID_CENTER_STAFF;
        $centerDirectorName = ROLE_ID_CENTER_DIRECTOR;
        $providerName = 'provider';

        $query = <<<SQL
SELECT DISTINCT
  u.id,
  CONCAT(u.last_name, ', ', u.first_name) AS name,
  uagbp.value
FROM user_acl_group_by_parameters uagbp
  JOIN acls a
    ON uagbp.acl_id = a.acl_id
  JOIN group_bys gb
    ON uagbp.group_by_id = gb.group_by_id
  JOIN Users u
    ON u.id = uagbp.user_id
WHERE a.name = :center_staff_name
      AND gb.name = :provider_name
      AND uagbp.value IN (
  SELECT uagbp.value
  FROM user_acl_group_by_parameters uagbp
    JOIN acls a ON uagbp.acl_id = a.acl_id
  WHERE uagbp.user_id = :user_id
        AND a.name = :center_director_name
);
SQL;
        $rows = $db->query($query, array(
            ':center_staff_name' => $centerStaffName,
            ':provider_name' =>$providerName,
            ':center_director_name' => $centerDirectorName,
            ':user_id' => $user->getUserID()
        ));

        if ($rows !== false && count($rows) > 0) {
            return $rows;
        }

        return array();
    }
}