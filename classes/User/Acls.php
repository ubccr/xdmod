<?php namespace User;

use CCR\DB\iDatabase;

class Acls
{

    public static function listAcls(iDatabase $db, \XDUser $user)
    {
        if (!isset($db, $user)) {
            return array();
        }

        $userId = $user->getUserID();

        $sql = <<<SQL
SELECT 
  ua.*
FROM user_acls ua 
  JOIN acls a 
    ON a.acl_id = ua.acl_id
WHERE ua.user_id = :user_id
SQL;
        return $db->query($sql, array('user_id' => $userId), true);
    }

    public static function userHasAcl(iDatabase $db, \XDUser $user, iAcl $acl)
    {
        if (!isset($db, $user, $acl)) {
            return false;
        }
        $userId = $user->getUserID();
        $aclId = $acl->getAclId();

        $sql = <<<SQL
SELECT 1
FROM user_acls ua
  JOIN acls a
    ON a.acl_id = ua.acl_id
WHERE
  ua.acl_id = :acl_id
  AND ua.user_id = :user_id
  AND a.enabled = TRUE
SQL;

        $results =  $db->query($sql, array('acl_id' => $aclId, 'user_id' => $userId), true);

        return $results[0] == 1;
    }

    public static function userHasAcls(iDatabase $db, \XDUser $user, array $acls)
    {
        if (!isset($db, $user, $acls)) {
            return false;
        }
        $handle = $db->handle();
        $userId = $user->getUserID();
        $aclIds = array_reduce($acls, function ($carry, iAcl $item) use ($handle) {
            $carry [] = $handle->quote($item->getAclId(), PDO::PARAM_INT);
        }, array());

        $sql = <<<SQL
SELECT 1
FROM user_acls ua
  JOIN acls a
    ON a.acl_id = ua.acl_id
WHERE
  ua.acl_id IN (:acl_ids)
  AND ua.user_id = :user_id
  AND a.enabled = TRUE
SQL;
        $results = $db->query($sql, array('user_id' => $userId, 'acl_ids' => $aclIds), true);

        return $results[0] == 1;
    }
}
