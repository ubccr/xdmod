<?php namespace User;

class Acls
{

    public function userHasAcl(\PDO $connection, \XDUser $user, iAcl $acl)
    {
        if (!isset($connection, $user, $acl)) {
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
        $prepared = $connection->prepare($sql);
        $prepared->bindParam('acl_id', $aclId);
        $prepared->bindParam('user_id', $userId);

        $result = $prepared->execute();

        return $result[0] == 1;
    }

    public function userHasAcls(\PDO $connection, \XDUser $user, array $acls)
    {
        if (!isset($connection, $user, $acls)) {
            return false;
        }
        $userId = $user->getUserID();
        $aclIds = array_reduce($acls, function ($carry, iAcl $item) use ($connection) {
            $carry [] = $connection->quote($item->getAclId(), PDO::PARAM_INT);
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

        $prepared = $connection->prepare($sql);
        $prepared->bindParam('user_id', $userId);
        $prepared->bindParam('acl_ids', implode(', ', $aclIds));

        $result = $prepared->execute();

        return $result[0] == 1;

    }
}
