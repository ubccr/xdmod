<?php namespace User;

use CCR\DB;
use DBObject;
use JsonSerializable;
use Module;

/**
 * Class Acl
 *
 * Represents a named grouping under which a selection of 'Assets' can be
 * secured and to which a number of users can be belong. Data for this class
 * is stored in the 'acls' table while the relationship of user to acl is stored
 * in 'user_acls'.
 *
 * @package User
 *
 * The 'getters' and 'setters' for this class:
 * @method integer getAclId()
 * @method void    setAclId($aclId)
 * @method integer getModuleId()
 * @method void    setModuleId($moduleId)
 * @method integer getAclTypeId()
 * @method void    setAclTypeId($aclTypeId)
 * @method string  getName()
 * @method void    setName($name)
 * @method string  getDisplay()
 * @method void    setDisplay($display)
 * @method boolean getEnabled()
 * @method void    setEnabled($enabled)
 * @method integer getUserId()
 * @method void    setUserId($userId)
 */
class Acl extends DBObject implements JsonSerializable
{
    protected $PROP_MAP = array(
        'acl_id' => 'aclId',
        'module_id' => 'moduleId',
        'acl_type_id' => 'aclTypeId',
        'name' => 'name',
        'display' => 'display',
        'enabled' => 'enabled',

        // Needed for getParameters
        'user_id' => 'userId'
    );

    /**
     * @return array|null
     * @throws \Exception
     */
    public function getParameters()
    {
        $userId = $this->getUserId();
        if (!isset($userId)) {
            throw new \Exception('Acl has no user_id. Cannot retrieve parameters');
        }
        $aclId = $this->getAclId();
        if (!isset($aclId)) {
            throw new \Exception('Acl has no acl_id. Cannot retrieve parameters');
        }

        $db = DB::factory('database');

        $query =<<< SQL
SELECT uap.user_id,
  uap.acl_id,
  uap.name,
  uap.operation,
  uap.value
FROM user_acl_parameters uap
  JOIN user_acls ua
    ON uap.user_id = ua.user_id
       AND uap.acl_id = ua.acl_id
WHERE
  ua.user_id = :user_id
AND ua.acl_id = :acl_id
SQL;
        $rows = $db->query($query, array(':user_id' => $userId, ':acl_id' => $aclId));
        if (false !== $rows) {
            $results = array_reduce($rows, function($carry, $item) {
                $carry[$item['name']] = $item['value'];
                return $carry;
            }, $rows);
            return $results;
        }

        return null;
    }

    /**
     * Determine if the user who has access to this acl
     *
     * @param $query_groupname
     * @param null $realm_name
     * @param null $group_by_name
     * @param null $statistic_name
     * @return bool
     * @throws \Exception
     */
    public function hasDataAccess($query_groupname, $realm_name = null, $group_by_name = null, $statistic_name = null)
    {
        $userId = $this->getUserId();
        if (!isset($userId)) {
            throw new \Exception('Acl has no user_id. Cannot check data access');
        }

        $hasRealm = isset($realm_name);
        $hasGroupBy = isset($group_by_name);
        $hasStatistic = isset($statistic_name);

        if ($hasRealm == true && $hasGroupBy == true && $hasStatistic == true) {
            $query =<<<SQL
SELECT
  agb.*,
  r.realm_id,
  r.name  AS realm,
  gb.group_by_id,
  gb.name AS group_by
FROM acl_group_bys agb
  JOIN user_acls ua
    ON agb.acl_id = ua.acl_id
  LEFT JOIN realms r
    ON agb.realm_id = r.realm_id
  LEFT JOIN group_bys gb
    ON agb.group_by_id = gb.group_by_id
LEFT JOIN statistics s
  ON agb.statistic_id = s.statistic_id
WHERE
  ua.user_id = :user_id
  AND agb.visible = TRUE
  AND agb.enabled = TRUE
  AND r.name = :realm_name
  AND gb.name = :group_by_name
  AND s.name = :statistic_name
SQL;
            $params = array(
                ':user_id' => $userId,
                ':realm_name' => $realm_name,
                ':group_by_name' => $group_by_name,
                ':statistic_name' => $statistic_name
            );
        } else if($hasRealm == true && $hasGroupBy == true) {
            $query =<<<SQL
SELECT
  agb.*,
  r.realm_id,
  r.name  AS realm,
  gb.group_by_id,
  gb.name AS group_by
FROM acl_group_bys agb
  JOIN user_acls ua
    ON agb.acl_id = ua.acl_id
  LEFT JOIN realms r
    ON agb.realm_id = r.realm_id
  LEFT JOIN group_bys gb
    ON agb.group_by_id = gb.group_by_id
LEFT JOIN statistics s
  ON agb.statistic_id = s.statistic_id
WHERE
  ua.user_id = :user_id
  AND agb.visible = TRUE
  AND agb.enabled = TRUE
  AND r.name = :realm_name
  AND gb.name = :group_by_name
SQL;
            $params = array(
                ':user_id' => $userId,
                ':realm_name' => $realm_name,
                ':group_by_name' => $group_by_name
            );
        } else if ($hasRealm == true) {
            $query =<<<SQL
SELECT
  agb.*,
  r.realm_id,
  r.name  AS realm,
  gb.group_by_id,
  gb.name AS group_by
FROM acl_group_bys agb
  JOIN user_acls ua
    ON agb.acl_id = ua.acl_id
  LEFT JOIN realms r
    ON agb.realm_id = r.realm_id
  LEFT JOIN group_bys gb
    ON agb.group_by_id = gb.group_by_id
LEFT JOIN statistics s
  ON agb.statistic_id = s.statistic_id
WHERE
  ua.user_id = :user_id
  AND agb.visible = TRUE
  AND agb.enabled = TRUE
  AND r.name = :realm_name
SQL;
            $params = array(
                ':user_id' => $userId,
                ':realm_name' => $realm_name
            );
        } else {
            $query =<<<SQL
SELECT
  agb.*,
  r.realm_id,
  r.name  AS realm,
  gb.group_by_id,
  gb.name AS group_by
FROM acl_group_bys agb
  JOIN user_acls ua
    ON agb.acl_id = ua.acl_id
  LEFT JOIN realms r
    ON agb.realm_id = r.realm_id
  LEFT JOIN group_bys gb
    ON agb.group_by_id = gb.group_by_id
LEFT JOIN statistics s
  ON agb.statistic_id = s.statistic_id
WHERE
  ua.user_id = :user_id
  AND agb.visible = TRUE
  AND agb.enabled = TRUE
SQL;
            $params = array(
                ':user_id' => $userId
            );
        }

        $db = DB::factory('database');
        $rows = $db->query($query, $params);

        return $rows !== false && count($rows) > 0;
    }
}

