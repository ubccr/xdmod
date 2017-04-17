<?php namespace User;

use CCR\DB;
use CCR\DB\iDatabase;
use Exception;
use PDO;
use XDUser;

/**
 * Class Acls
 * @package User
 *
 * It is the intended purpose of this class to provide a host of functions to
 * ease working with and within the Acl framework. It provides basic CRUD
 * functionality in addition to a number of other functions related to Acls and
 * their associated pieces of data.
 *
 */
class Acls
{

    /**
     * @return Acl[]
     */
    public static function getAcls()
    {
        return self::_getAcls(DB::factory('database'));
    }

    /**
     * Attempt to retrieve the acl identified by the '$aclId' provided.
     *
     * @param integer $aclId
     * @return null|Acl null if an acl could not be found for the provided '$aclId'
     *                  else a fully populated Acl.
     * @throws Exception if an '$aclId' is not provided.
     */
    public static function getAcl($aclId)
    {
        if (null === $aclId) {
            throw new Exception('Must provide an acl id.');
        }

        return self::_getAcl(
            DB::factory('database'),
            $aclId
        );
    }

    public static function createAcl(Acl $acl)
    {
        if (null === $acl) {
            throw new Exception('Must provide an acl');
        }

        if (null != $acl->getAclId()) {
            throw new Exception('acl must not have an id.');
        }

        return self::_createAcl(
            DB::factory('database'),
            $acl
        );
    }

    public static function updateAcl(Acl $acl)
    {
        if (null === $acl) {
            throw new Exception('Acl must be provided to complete requested update.');
        }

        if (null == $acl->getAclId()) {
            throw new Exception('Acl must have an id to be updated.');
        }

        return self::_updateAcl(
            DB::factory('database'),
            $acl
        );
    }

    public static function deleteAcl(Acl $acl)
    {
        if (null === $acl) {
            throw new Exception('Acl must be provided to complete requested deletion.');
        }

        if (null == $acl->getAclId()) {
            throw new Exception('Acl must have an id to be deleted.');
        }

        return self::_deleteAcl(
            DB::factory('database'),
            $acl
        );
    }

    /**
     * Retrieve a list of a user's current acls.
     *
     * @param XDUser $user
     * @return array
     * @throws Exception
     */
    public static function listUserAcls(XDUser $user)
    {
        if (null == $user->getUserID()) {
            throw new Exception('A valid user id must be provided.');
        }

        return self::_listUserAcls(
            DB::factory('database'),
            $user
        );
    }

    public static function addUserAcl(XDUser $user, $aclId)
    {
        if (null == $user->getUserID()) {
            throw new Exception('A valid user id must be provided.');
        }

        if (null === $aclId) {
            throw new Exception('A valid acl id must be provided.');
        }

        return self::_addUserAcl(
            DB::factory('database'),
            $user,
            $aclId
        );
    }

    public static function deleteUserAcl(XDUser $user, $aclId)
    {
        if (null == $user->getUserID()) {
            throw new Exception('A valid user id must be provided.');
        }
        if (null === $aclId) {
            throw new Exception('A valid acl id must be provided.');
        }

        return self::_deleteUserAcl(
            DB::factory('database'),
            $user,
            $aclId
        );
    }

    /**
     * @param XDUser $user
     * @param integer $aclId
     * @return bool
     * @throws Exception
     */
    public static function userHasAcl(XDUser $user, $aclId)
    {
        if (null == $user->getUserID()) {
            throw new Exception('A valid user id must be provided.');
        }

        if (null === $aclId) {
            throw new Exception('A valid acl id must be provided.');
        }

        return self::_userHasAcl(
            DB::factory('database'),
            $user,
            $aclId
        );
    }


    public static function userHasAcls(XDUser $user, array $acls)
    {
        if (null == $user->getUserID()) {
            throw new Exception('A valid user id must be provided.');
        }

        return self::_userHasAcls(
            DB::factory('database'),
            $user,
            $acls
        );
    }

    public static function getDisabledMenus(XDUser $user, array $realms)
    {
        if (null === $user->getUserID()) {
            throw new Exception('A valid user id must be provided.');
        }

        if (count($realms) < 1) {
            throw new Exception('At least one realm expected must be provided.');
        }

        return self::_getDisabledMenus(
            DB::factory('database'),
            $user,
            $realms
        );

    }

    public static function getAclByName($name)
    {
        if (null === $name) {
            throw new Exception('A valid acl name is required');
        }

        return self::_getAclByName(
            DB::factory('database'),
            $name
        );
    }

    public static function getDescriptorsForUser(XDUser $user)
    {
        if ($user->getUserID() == null) {
            throw new Exception('A valid user must be provided.');
        }

        return self::_getDescriptors(
            DB::factory('database'),
            $user
        );
    }

    public static function getGroupBysForRealm($realmName)
    {
        if (null === $realmName) {
            throw new Exception('A valid realm name must be provided. (null)');
        }
        if (is_string($realmName) == false) {
            throw new Exception('A valid realm name must be provided. (string)');
        }
        if (strlen($realmName) < 1) {
            throw new Exception('A valid realm name must be provided. (length)');
        }

        return self::_getGroupBysForRealm(
            DB::factory('database'),
            $realmName
        );
    }

    public static function getDescriptorParamValue(XDUser $user, $aclName, $groupByName)
    {
        if (null == $user->getUserID()) {
            throw new Exception('A valid user id must be supplied.');
        }
        if (null === $aclName) {
            throw new Exception('A valid acl name is required.');
        }
        if (null === $groupByName) {
            throw new Exception('A valid group by name is required.');
        }
        return self::_getDescriptorParamvalue(
            DB::factory('database'),
            $user,
            $aclName,
            $groupByName
        );
    }


    public static function getDescriptorParamValues(XDUser $user, $aclName, $groupByName)
    {
        if (null == $user->getUserID()) {
            throw new Exception('A valid user id must be supplied.');
        }
        if (null === $aclName) {
            throw new Exception('A valid acl name is required.');
        }
        if (null === $groupByName) {
            throw new Exception('A valid group by name is required.');
        }
        return self::_getDescriptorParamValues(
            DB::factory('database'),
            $user,
            $aclName,
            $groupByName
        );
    }



    /**
     * @param iDatabase $db
     * @return array
     */
    private static function _getAcls(iDatabase $db)
    {
        $results = $db->query("SELECT a.* FROM acls a");
        return array_reduce($results, function ($carry, $item) {
            $carry [] = new Acl($item);
            return $carry;
        }, array());
    }

    /**
     * Attempt to create a database representation of the provided '$acl'. Note,
     * the 'aclId' property of '$acl' must not be set. If it is, it will be
     * overridden.
     *
     * @param iDatabase $db that will be used to create '$acl'
     * @param Acl $acl that will be created
     * @return Acl with the $aclId populated.
     */
    private static function _createAcl(iDatabase $db, Acl $acl)
    {
        $query = <<<SQL
INSERT INTO acls(module_id, acl_type_id, name, display, enabled) 
VALUES(:module_id, :acl_type_id, :name, :display, :enabled);
SQL;
        $aclId = $db->insert($query, array(
            ':module_id' => $acl->getModuleId(),
            ':acl_type_id' => $acl->getAclTypeId(),
            ':name' => $acl->getName(),
            ':display' => $acl->getDisplay(),
            ':enabled' => $acl->getEnabled()
        ));

        $acl->setAclId($aclId);

        return $acl;
    }

    /**
     * Attempt to retrieve the Acl identified by the provided '$aclId'.
     *
     * @param iDatabase $db to be used in the Acl retrieval.
     * @param integer $aclId to be retrieved.
     * @return null|Acl null if no Acl could be found with the provided aclId
     *                       else a fully populated Acl object.
     */
    private static function _getAcl(iDatabase $db, $aclId)
    {
        $query = <<<SQL
SELECT 
  a.*
FROM acls a 
WHERE a.acl_id = :acl_id
SQL;
        $results = $db->query($query, array(':acl_id' => $aclId));

        if ( count($results) > 0 ) {
            return new Acl($results[0]);
        }
        return null;
    }

    /**
     * Attempt to update the database representation of the provided '$acl' such
     * that the information in the database corresponds to the data in the
     * object provided.
     *
     * @param iDatabase $db the database to be used for the update procedure.
     * @param Acl $acl to be used when updating the database table.
     *
     * @return bool true iff the number of rows updated equals 1.
     */
    private static function _updateAcl(iDatabase $db, Acl $acl)
    {
        $query = <<<SQL
UPDATE acls a 
SET 
  a.module_id = :module_id,
  a.acl_type_id = :acl_type_id,
  a.name = :name,
  a.display = :display,
  a.enabled = :enabled
WHERE 
  a.acl_id = :acl_id
SQL;
        $rows = $db->execute($query, array(
            ':module_id' => $acl->getModuleId(),
            ':acl_type_id' => $acl->getAclTypeId(),
            ':name' => $acl->getName(),
            ':display' => $acl->getDisplay(),
            ':enabled' => $acl->getEnabled()
        ));

        return $rows === 1;
    }

    /**
     * Attempt to delete the acl identified by the provided '$aclId'.
     *
     * @param iDatabase $db the database to use when performing the delete.
     * @param integer $aclId the id of the acl to be deleted.
     * @return bool true iff the number of rows deleted = 1.
     */
    private static function _deleteAcl(iDatabase $db, $aclId)
    {
        $query = "DELETE FROM acls a WHERE a.acl_id = :acl_id";
        $rows = $db->execute($query, array(
            ':acl_id' => $aclId
        ));
        return $rows === 1;
    }

    private static function _addUserAcl(iDatabase $db, XDUser $user, $aclId)
    {
        $params = array(
            ':user_id' => $user->getUserId(),
            ':acl_id' => $aclId
        );

        $query = "SELECT 1 FROM user_acls WHERE user_id = :user_id AND acl_id = :acl_id";
        $results = $db->query($query, $params);

        $success = count($results) >= 1;
        if (!$success) {
            $query = "INSERT INTO user_acls(user_id, acl_id) VALUES(:user_id, :acl_id)";
            $rows = $db->execute($query, $params);
            $success = $rows === 1;
        }

        return $success;
    }

    private static function _deleteUserAcl(iDatabase $db, XDUser $user, $aclId)
    {
        $query = "DELETE FROM user_acls WHERE user_id = :user_id AND acl_id = :acl_id";
        $rows = $db->execute($query, array(
            ':user_id' => $user->getUserId(),
            ':acl_id' => $aclId
        ));
        return $rows <= 1;
    }

    /**
     * @param iDatabase $db
     * @param XDUser $user
     * @return array
     */
    private static function _listUserAcls(iDatabase $db, XDUser $user)
    {
        $userId = $user->getUserID();

        $sql = <<<SQL
SELECT
  a.*
FROM user_acls ua
  JOIN acls a
    ON a.acl_id = ua.acl_id
WHERE ua.user_id = :user_id
SQL;
        return $db->query($sql, array('user_id' => $userId));
    }

    /**
     * @param iDatabase $db
     * @param XDUser $user
     * @param integer $aclId
     * @return bool
     */
    private static function _userHasAcl(iDatabase $db, XDUser $user, $aclId)
    {
        if (!isset($db, $user, $aclId)) {
            return false;
        }
        $userId = $user->getUserID();

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

        $results = $db->query($sql, array('acl_id' => $aclId, 'user_id' => $userId));

        return count($results) > 0;
    }

    private static function _userHasAcls(iDatabase $db, XDUser $user, array $acls)
    {
        if (count($acls) < 1) {
            return false;
        }

        $handle = $db->handle();
        $userId = $user->getUserID();
        $aclIds = array_reduce($acls, function ($carry, Acl $item) use ($handle) {
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
        $results = $db->query($sql, array('user_id' => $userId, 'acl_ids' => $aclIds));

        return count($results) > 0;
    }


    private static function _getDisabledMenus(iDatabase $db, XDUser $user, array $realmNames)
    {
        // Needed because we have 'IN' clauses.
        $handle = $db->handle();

        // PDO can't handle 'IN' for prepared statements so create some suitable strings
        // for substitution. ( making sure to quote where appropriate ).
        $acls = implode(',', array_reduce($user->getAcls(), function ($carry, Acl $item) use ($handle) {
            $carry [] = $handle->quote($item->getAclId(), PDO::PARAM_INT);
            return $carry;
        }, array()));

        $realms = implode(',', array_reduce($realmNames, function ($carry, $item) use ($handle) {
            $value = null;
            if ($item instanceof \Realm) {
                $value = $item->getName();
            } elseif(is_string($item)) {
                $value = $item;
            } else {
                $value = (string)$item;
            }
            $carry [] = $handle->quote($value);
            return $carry;
        }, array()));

        $sql = <<<SQL
SELECT DISTINCT
  a.name,
  CASE WHEN agb.enabled = TRUE THEN NULL ELSE CONCAT('group_by_',r.name,'_',gb.name) END AS id,
  CASE WHEN agb.enabled = TRUE THEN NULL ELSE gb.name END as group_by,
  CASE WHEN agb.enabled = TRUE THEN NULL ELSE r.name END as realm
FROM acl_group_bys agb
  JOIN acls a
    ON a.acl_id = agb.acl_id
  JOIN group_bys gb
    ON gb.group_by_id = agb.group_by_id
  JOIN realms r
    ON gb.realm_id = r.realm_id
WHERE agb.acl_id IN ($acls)
      AND r.name IN ($realms)
ORDER BY a.name
SQL;
        $results = array();

        /* By retrieving all of the query_descripters ( acl_group_bys ) for all
         * of a users acls / the provided realms in one go we do not need the
         * 'foreach role ... role->getDisabledMenus()' we then take care of
         * formatting the results as the XDUser->getDisabledMenus function
         * expects by including the group_by name / ordering by group_by name
         * and constructing an associative array based on said group_by name.
         * The code in XDUser->getDisabledMenus is still responsible for detecting
         * whether or not any given disabled menu is present for all other acls.
         */
        $rows = $db->query($sql);

        $previousName = null;
        foreach ($rows as $row) {
            $name = $row['name'];
            if ($name != $previousName) {
                $previousName = $name;
            }
            if (!isset($results[$name])) {
                $results[$name] = array();
            }
            if ($row['id'] != null) {
                $results[$name] []= array(
                    'id' => $row['id'],
                    'group_by' => $row['group_by'],
                    'realm' => $row['realm']
                );
            }
        }

        return array_filter($results, function ($item) {
            return count($item) > 0;
        });
    }

    private static function _getAclByName(iDatabase $db, $name)
    {
        $sql = "SELECT * FROM acls a WHERE a.name = :name";

        $rows = $db->query($sql, array(':name' => $name));

        if ( count($rows) > 0 ) {
            return new Acl($rows[0]);
        }

        return null;
    }


    /**
     * @param iDatabase $db
     * @param XDUser $user
     * @return array
     */
    private static function _getDescriptors(iDatabase $db, XDUser $user)
    {
        $query = <<<SQL
SELECT DISTINCT
  r.display                    AS realm,
  gb.name                      AS dimension_name,
  gb.display                   AS dimension_text,
  gb.description               AS dimension_info,
  s.name                       AS metric_name,
  CASE WHEN INSTR(s.display, s.unit) < 0
    THEN CONCAT(s.display, ' (', s.unit, ')')
  ELSE s.display
  END                          AS metric_text,
  s.description                AS metric_info,
  sem.statistic_id IS NOT NULL AS metric_std_err
FROM acl_group_bys agb
  JOIN user_acls ua
    ON agb.acl_id = ua.acl_id
  JOIN group_bys gb
    ON gb.group_by_id = agb.group_by_id
  JOIN realms r
    ON r.realm_id = gb.realm_id
  JOIN statistics s
    ON s.statistic_id = agb.statistic_id
  LEFT JOIN statistics sem
    ON sem.name = CONCAT('sem_', s.name)
WHERE
  ua.user_id = :user_id
  AND agb.enabled = TRUE
  AND agb.visible = TRUE
  AND s.visible = TRUE
ORDER BY r.name, gb.display, s.display;
SQL;
        $realms = array();

        $rows = $db->query($query, array(':user_id' => $user->getUserID()));
        if ( count($rows) > 0 ) {
            foreach ($rows as $row) {
                $realm = $row['realm'];

                if (!array_key_exists($realm, $realms)) {
                    $realms[$realm] = array(
                        'metrics' => array(),
                        'dimensions' => array(),
                        'text' => $realm
                    );
                }
                $dimensions = &$realms[$realm]['dimensions'];
                $metrics = &$realms[$realm]['metrics'];

                $dimensionName = $row['dimension_name'];
                $metricName = $row['metric_name'];

                // Dimension Processing
                if (isset($dimensionName) && !array_key_exists($dimensionName, $dimensions)) {
                    $dimensions[$dimensionName] = array(
                        'info' => $row['dimension_info'],
                        'text' => $row['dimension_text']
                    );
                }

                // Statistic Processing
                if (isset($metricName) && !array_key_exists($metricName, $metrics)) {
                    $metrics[$metricName] = array(
                        'info' => $row['metric_info'],
                        'text' => $row['metric_text'],
                        'std_err' => $row['metric_std_err']
                    );
                }
            }
        }

        return array('realms' => $realms);
    }

    /**
     * @param iDatabase $db
     * @param string $realmName
     *
     * @return \GroupBy[]|array()
     */
    private static function _getGroupBysForRealm(iDatabase $db, $realmName)
    {
        $query = <<< SQL
SELECT DISTINCT
  gb.*
FROM group_bys gb
  JOIN realms r ON gb.realm_id = r.realm_id
WHERE r.name = :realm_name
SQL;
        $rows = $db->query($query, array(
            ':realm_name' => $realmName
        ));
        if ( count($rows) > 0 ) {
            return array_reduce($rows, function ($carry, $item) {
                $carry [] = new \GroupBy($item);
                return $carry;
            }, array());
        }
        return array();
    }

    /**
     * @param iDatabase $db
     * @param XDUser $user
     * @param $aclName
     * @param $groupByName
     * @return null|string
     */
    private static function _getDescriptorParamValue(iDatabase $db, XDUser $user, $aclName, $groupByName)
    {
        $query = <<<SQL
SELECT DISTINCT uagbp.value
FROM user_acl_group_by_parameters uagbp
  JOIN group_bys gb ON gb.group_by_id = uagbp.group_by_id
  JOIN acls a ON a.acl_id = uagbp.acl_id
WHERE uagbp.user_id = :user_id 
  AND a.name = :acl_name 
  AND gb.name = :group_by_name;
SQL;
        $rows = $db->query($query, array(
            ':user_id' => $user->getUserID(),
            ':acl_name' => $aclName,
            ':group_by_name' => $groupByName
        ));
        if ( count($rows) > 0 ) {
            return $rows[0]['value'];
        }
        return null;
    }

    private static function _getDescriptorParamValues(iDatabase $db, XDUser $user, $aclName, $groupByName)
    {
        $query = <<<SQL
SELECT DISTINCT uagbp.value
FROM user_acl_group_by_parameters uagbp
  JOIN group_bys gb ON gb.group_by_id = uagbp.group_by_id
  JOIN acls a ON a.acl_id = uagbp.acl_id
WHERE uagbp.user_id = :user_id 
  AND a.name = :acl_name 
  AND gb.name = :group_by_name;
SQL;
        $rows = $db->query($query, array(
            ':user_id' => $user->getUserID(),
            ':acl_name' => $aclName,
            ':group_by_name' => $groupByName
        ));
        if ( count($rows) > 0 ) {
            return array_reduce($rows, function ($carry, $item) {
                $carry []= $item['value'];
                return $carry;
            }, array());
        }
        return array();
    }
}
