<?php namespace Models\Services;

use Exception;
use PDO;

use CCR\DB;
use Models\Acl;
use Models\GroupBy;
use Models\Realm;
use Models\Statistic;
use User\Elements\QueryDescripter;
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
     * Attempt to retrieve a list of all Acls that currently exist in the system
     *
     * @return Acl[]
     */
    public static function getAcls()
    {

        $db = DB::factory('database');

        $results = $db->query("SELECT a.* FROM acls a");
        return array_reduce($results, function ($carry, $item) {
            $carry [] = new Acl($item);
            return $carry;
        }, array());
    }

    /**
     * Attempt to retrieve the acl identified by the '$aclId' provided.
     *
     * @param integer $aclId
     * @return null|Acl null if an acl could not be found for the provided '$aclId'
     *                  else a fully populated Acl.
     * @throws Exception if the aclId is null
     */
    public static function getAcl($aclId)
    {
        if (null === $aclId) {
            throw new Exception('Must provide an acl id.');
        }

        $db = DB::factory('database');

        $query = <<<SQL
SELECT
  a.*
FROM acls a
WHERE a.acl_id = :acl_id
SQL;
        $results = $db->query($query, array(':acl_id' => $aclId));

        if (count($results) > 0) {
            return new Acl($results[0]);
        }
        return null;
    }

    /**
     * Attempt to create a database representation of the provided '$acl'. Note,
     * the 'aclId' property of '$acl' must not be set. If it is then an
     * exception will be thrown.
     *
     * @param Acl $acl that will be created
     * @return Acl with the $aclId populated.
     * @throws Exception if the provided acls aclId is not null
     */
    public static function createAcl(Acl $acl)
    {
        if (null != $acl->getAclId()) {
            throw new Exception('acl must not have been saved.');
        }

        $db = DB::factory('database');

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
     * Attempt to update the database representation of the provided '$acl' such
     * that the information in the database corresponds to the data in the
     * object provided.
     *
     * @param Acl $acl to be used when updating the database table.
     * @return bool true iff the number of rows updated equals 1.
     * @throws Exception if the provided acl's aclId is null
     */
    public static function updateAcl(Acl $acl)
    {
        if (null == $acl->getAclId()) {
            throw new Exception('Acl must have an id to be updated.');
        }

        $db = DB::factory('database');

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
     * @param Acl $acl
     * @return bool true iff the number of rows deleted = 1.
     * @throws Exception if the provided acls aclId is null
     */
    public static function deleteAcl(Acl $acl)
    {
        if (null == $acl->getAclId()) {
            throw new Exception('Acl must have an id to be deleted.');
        }

        $db = DB::factory('database');

        $query = "DELETE FROM acls WHERE acl_id = :acl_id";
        $rows = $db->execute($query, array(
            ':acl_id' => $acl->getAclId()
        ));
        return $rows === 1;
    }

    /**
     * Retrieve a list of a user's current acls.
     *
     * @param XDUser $user
     * @return array[]
     *
     * @throws Exception if the user's userId is null
     */
    public static function listUserAcls(XDUser $user)
    {
        if (null == $user->getUserID()) {
            throw new Exception('A valid user id must be provided.');
        }

        $db = DB::factory('database');

        $userId = $user->getUserID();

        $sql = <<<SQL
SELECT
  a.*,
  req.acl_id IS NOT NULL requires_center
FROM user_acls ua
  JOIN acls a
    ON a.acl_id = ua.acl_id
  LEFT JOIN (
    SELECT acl_id FROM acls WHERE name IN ('cd', 'cs')
    ) req ON req.acl_id = ua.acl_id
WHERE ua.user_id = :user_id
SQL;
        return $db->query($sql, array('user_id' => $userId));
    }

    /**
     * Attempt to relate the provided XDUser to the Acl identified by the $aclId.
     *
     * @param XDUser $user the user that should have the Acl identified by the
     * provided $aclId related to it.
     * @param integer $aclId the unique numeric identifier for the Acl to be
     * added to the provided user.
     *
     * @return bool true if the insert was successful else false
     *
     * @throws Exception if the user's userId is null
     * @throws Exception if the aclId is null
     */
    public static function addUserAcl(XDUser $user, $aclId)
    {
        if (null == $user->getUserID()) {
            throw new Exception('A valid user id must be provided.');
        }

        if (null === $aclId) {
            throw new Exception('A valid acl id must be provided.');
        }
        $db = DB::factory('database');
        $params = array(
            ':user_id' => $user->getUserId(),
            ':acl_id' => $aclId
        );
        $query = <<<SQL
INSERT INTO user_acls(user_id, acl_id)
SELECT inc.*
FROM (
    SELECT
        :user_id as user_id,
        :acl_id  as acl_id
) inc
LEFT JOIN user_acls cur
   ON cur.user_id = inc.user_id AND
      cur.acl_id  = inc.acl_id
WHERE cur.user_acl_id IS NULL;
SQL;
        $rows = $db->execute($query, $params);

        return $rows === 1;
    }

    /**
     * Attempt to remove the relation between the provided user and acl.
     *
     * @param XDUser $user   the user that will have their relation to acl
     *                       removed.
     * @param integer $aclId the unique identifier for the acl that will be removed
     *                       from the provided user.
     *
     * @return boolean true if 1 or less rows were deleted as a result of this
     * action.
     *
     * @throws Exception if the user's userId is null
     * @throws Exception if the aclId is null
     **/
    public static function deleteUserAcl(XDUser $user, $aclId)
    {
        if (null == $user->getUserID()) {
            throw new Exception('A valid user id must be provided.');
        }
        if (null === $aclId) {
            throw new Exception('A valid acl id must be provided.');
        }

        $db = DB::factory('database');

        $query = "DELETE FROM user_acls WHERE user_id = :user_id AND acl_id = :acl_id";
        $rows = $db->execute($query, array(
            ':user_id' => $user->getUserId(),
            ':acl_id' => $aclId
        ));
        return $rows <= 1;
    }

    /**
     * Attempt to determine if the provided user has a relation to the acl
     * identified by the provided aclId.
     *
     * @param XDUser  $user  the user checked for a relation to aclId
     * @param integer $aclId the id of the acl checked for a relation to user
     *
     * @return boolean true if there is one or more results returned
     *
     * @throws Exception if the users userId is null
     * @throws Exception if the aclId provided is null
     */
    public static function userHasAcl(XDUser $user, $aclId)
    {
        if (null == $user->getUserID()) {
            throw new Exception('A valid user id must be provided.');
        }

        if (null === $aclId) {
            throw new Exception('A valid acl id must be provided.');
        }
        $db = DB::factory('database');

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


    /**
     * Similar to userHasAcl but instead of checking if the user has a relation
     * to a single acl, we instead check if they have a relation to each acl
     * provided in the array acls.
     *
     * @param XDUser $user the user being interrogated for relations to the
     *                     provided acls
     * @param array  $acls the array of acls being checked for a relation to
     *                     user
     *
     * @return boolean true if the user has all of the provided acls
     *
     * @throws Exception if the provided user's userId is null
     **/
    public static function userHasAcls(XDUser $user, array $acls)
    {
        if (null === $user->getUserID()) {
            throw new Exception('A valid user id must be provided.');
        }
        $db = DB::factory('database');
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

    /**
     * Attempt to retrieve an array that will be used by the front end to disable particular
     * menu options on a user by user basis.
     *
     * @param XDUser $user  the user for whom the disabled menus are to be
     *                      retrieved
     * @param array $realms the realms to which the disabled menus are to be
     *                      retrieved.
     *
     * @return array
     *
     * @throws Exception if the provided user's userId is null
     * @throws Exception if the provided array of realms is empty
     **/
    public static function getDisabledMenus(XDUser $user, array $realms)
    {
        if (null === $user->getUserID()) {
            throw new Exception('A valid user id must be provided.');
        }

        if (count($realms) < 1) {
            throw new Exception('At least one realm expected must be provided.');
        }

        $db = DB::factory('database');

        // Needed because we have 'IN' clauses.
        $handle = $db->handle();

        $realmNames = implode(
            ',',
            array_reduce(
                $realms,
                function ($carry, $item) use ($handle) {
                    $value = null;
                    if ($item instanceof Realm) {
                        $value = $item->getName();
                    } elseif (is_string($item)) {
                        $value = $item;
                    } else {
                        $value = (string)$item;
                    }
                    $carry [] = $handle->quote($value);
                    return $carry;
                },
                array()
            )
        );

        $sql = <<<SQL
SELECT DISTINCT
  a.name,
  CASE WHEN agb.enabled = TRUE THEN NULL ELSE CONCAT('group_by_', r.name, '_', gb.name) END AS id,
  CASE WHEN agb.enabled = TRUE THEN NULL ELSE gb.name END                                   AS group_by,
  CASE WHEN agb.enabled = TRUE THEN NULL ELSE r.display END                                 AS realm
FROM acl_group_bys agb
  JOIN user_acls ua ON agb.acl_id = ua.acl_id AND ua.acl_id IN (
          SELECT af.*
          FROM (
            SELECT a.acl_id
            FROM (
              SELECT
                ah.acl_hierarchy_id,
                ah.acl_id,
                ah.hierarchy_id,
                ah.level
              FROM acl_hierarchies ah
                JOIN user_acls ua ON ah.acl_id = ua.acl_id
              WHERE ua.user_id = :user_id
            ) ah1
            LEFT JOIN (
                     SELECT
                       ah.acl_hierarchy_id,
                       ah.hierarchy_id,
                       ah.level
                     FROM acl_hierarchies ah
                       JOIN user_acls ua ON ah.acl_id = ua.acl_id
                     WHERE ua.user_id = :user_id
                   ) ah2
              ON ah2.hierarchy_id = ah1.hierarchy_id AND ah2.level > ah1.level
            JOIN acls a ON a.acl_id = ah1.acl_id
            WHERE ah2.acl_hierarchy_id IS NULL
          ) af
          UNION
          SELECT a.acl_id
          FROM acls a
            JOIN acl_types at ON at.acl_type_id = a.acl_type_id
            JOIN user_acls ua ON a.acl_id = ua.acl_id
            LEFT JOIN acl_hierarchies ah ON ah.acl_id = a.acl_id
          WHERE
            ah.acl_hierarchy_id IS NULL AND
            ua.user_id = :user_id AND
            at.name = 'data'
  )
  JOIN acls a ON a.acl_id = ua.acl_id
  JOIN group_bys gb ON gb.group_by_id = agb.group_by_id
  JOIN realms r ON agb.realm_id = r.realm_id
WHERE
  ua.user_id = :user_id AND
  r.name IN ($realmNames);
SQL;
        $results = array();

        /* By retrieving all of the query_descripters ( acl_group_bys ) for all
         * of a users acls / the provided realms in one go we do not need the
         * 'foreach role ... role->getDisabledMenus()' we then take care of
         * formatting the results as the XDUser->getDisabledMenus function
         * expects. The code in XDUser->getDisabledMenus is still responsible
         * for detecting whether or not any given disabled menu is present for
         * all other acls.
         */
        $rows = $db->query(
            $sql,
            array(
                ':user_id' => $user->getUserID()
            )
        );

        foreach ($rows as $row) {
            if ($row['id'] != null) {
                $results[] = array(
                    'id' => $row['id'],
                    'group_by' => $row['group_by'],
                    'realm' => $row['realm']
                );
            }
        }

        return $results;
    }


    /**
     * Attempt to retrieve an Acl by providing it's 'name' attribute.
     * Note: if there are Acls that share the same name, only the first one
     * returned ( as determined by natural table ordering ) will be returned
     * from this function.
     *
     * @param string $name the name of the Acl to retrieve
     * @return Acl|null
     * @throws Exception if the name provided is null
     */
    public static function getAclByName($name)
    {
        if (null === $name) {
            throw new Exception('A valid acl name is required');
        }

        $db = DB::factory('database');

        $sql = "SELECT * FROM acls a WHERE a.name = :name";

        $rows = $db->query($sql, array(':name' => $name));

        if (count($rows) > 0) {
            return new Acl($rows[0]);
        }

        return null;
    }

    /**
     * Attempt to retrieve an Acl via it's `display` value. This corresponds to `moddb.acls.display`
     * If an acl is not found then null will be returned.
     *
     * @param string $display
     * @return Acl|null
     * @throws Exception
     */
    public static function getAclByDisplay($display)
    {
        $db = DB::factory('database');
        $rows = $db->query('SELECT * FROM acls a WHERE a.display = :display', array(':display' => $display));
        if (count($rows) > 0) {
            return new Acl($rows[0]);
        }
        return null;
    }

    /**
     * Attempt to retrieve all descriptors for the provided user.
     *
     * @param XDUser $user the user to use when retrieving the descriptors.
     * @return array
     * @throws Exception if the user's userId is null
     */
    public static function getDescriptorsForUser(XDUser $user)
    {
        if ($user->getUserID() == null) {
            throw new Exception('A valid user must be provided.');
        }
        $db = DB::factory('database');
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
        if (count($rows) > 0) {
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
     * Attempt to retrieve the statistics that a user is permitted for a given
     * realm and groupBy
     *
     * @param XDUser $user        the user on whose behalf the permitted
     * statistics are being requested.
     * @param string $realmName   the realm in which these statistics reside
     * @param string $groupByName the group by to which these statistics are
     * related
     *
     * @return array|Statistic[]
     *
     * @throws Exception if the provided user's userId is null
     * @throws Exception if the provided $realmName is null
     * @throws Exception if the provided $groupByName is null
     */
    public static function getPermittedStatistics(XDUser $user, $realmName, $groupByName)
    {
        if (null === $user->getUserID()) {
            throw new Exception('The user must have a userId');
        }
        if (null === $realmName) {
            throw new Exception('A valid realm is required.');
        }

        if (null === $groupByName) {
            throw new Exception('A valid group by is required');
        }

        $db = DB::factory('database');
        $query = <<<SQL
SELECT DISTINCT s.*
FROM statistics s
  JOIN acl_group_bys agb
    ON s.statistic_id = agb.statistic_id
  JOIN user_acls ua
    ON agb.acl_id = ua.acl_id
  JOIN group_bys gb
    ON agb.group_by_id = gb.group_by_id
  JOIN realms r
    ON gb.realm_id = r.realm_id
WHERE
  r.name = :realm_name
  AND gb.name = :group_by_name
  AND ua.user_id = :user_id;
SQL;

        $rows = $db->query($query, array(
            ':realm_name' => $realmName,
            ':group_by_name' => $groupByName,
            ':user_id' => $user->getUserID()
        ));

        if ($rows !== false && count($rows) > 0) {
            return array_reduce($rows, function ($carry, $item) {
                $carry [] = $item['name'];
                return $carry;
            }, array());
        }
        return array();
    }

    /**
     * Attempt to retrieve the group bys that are valid for the realm identified
     * by the provided $realmName.
     *
     * @param string $realmName the string identifier to use when retrieving the group_by instances.
     *
     * @return array|GroupBy[]
     *
     * @throws Exception if the $realmName provided is null
     */
    public static function getGroupBysForRealm($realmName)
    {
        if (null === $realmName) {
            throw new Exception('A valid realm name must be provided. (null)');
        }

        $db = DB::factory('database');
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
        if (count($rows) > 0) {
            return array_reduce($rows, function ($carry, $item) {
                $carry [] = new GroupBy($item);
                return $carry;
            }, array());
        }
        return array();
    }

    /**
     * Attempt to retrieve the descriptor param value specific to the provided
     * user, acl and group_by. Note: if there is more than one param value that
     * matches the parameters provided then only the first one ( as
     * determined by natural table ordering ) will be returned by this function.
     *
     * @param XDUser $user        the user to use when determining the param
     * value
     * @param string $aclName     the name of the acl this descriptor param
     * value is associated with
     * @param string $groupByName the name of the group by this descriptor param
     * value is associated with
     * @return null|string null if the value is not found, else it's returned as
     * a string
     * @throws Exception if the user's userId is null
     * @throws Exception if the provided acl name is null
     * @throws Exception if the provided group by name is null
     */
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
        $db = DB::factory('database');
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
        if (count($rows) > 0) {
            return $rows[0]['value'];
        }
        return null;
    }

    /**
     * Attempt to retrieve all descriptor param values for the provided user,
     * acl and group by.
     *
     * @param XDUser $user        the user to use when determining the param
     * value
     * @param string $aclName     the name of the acl this descriptor param
     * value is associated with
     * @param string $groupByName the name of the group by this descriptor param
     * value is associated with
     * @return array|string[]
     * @throws Exception if the user's userId is null
     * @throws Exception if the aclName is null
     * @throws Exception if the gropuByName is null
     */
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

        $query = <<<SQL
SELECT DISTINCT uagbp.value
FROM user_acl_group_by_parameters uagbp
  JOIN group_bys gb ON gb.group_by_id = uagbp.group_by_id
  JOIN acls a ON a.acl_id = uagbp.acl_id
WHERE uagbp.user_id = :user_id
  AND a.name = :acl_name
  AND gb.name = :group_by_name;
SQL;
        $db = DB::factory('database');
        $rows = $db->query($query, array(
            ':user_id' => $user->getUserID(),
            ':acl_name' => $aclName,
            ':group_by_name' => $groupByName
        ));
        if (count($rows) > 0) {
            return array_reduce($rows, function ($carry, $item) {
                $carry [] = $item['value'];
                return $carry;
            }, array());
        }
        return array();
    }

    /**
     * Attempt to retrieve the "most privileged" acl for the provided user.
     * "most privileged" in this context is the acl that fulfills the following
     * requirements:
     *   - The user provided has a relation to said acl.
     *   - The acl was added by the module identified by the parameter
     *     '$moduleName'.
     *   - The acl has takes part in a relationship with the acl hierarchy
     *     identified by the parameter '$aclHierarchyName'.
     *   - Of all acls that fit the previous requirements, it must also be the
     *     one that has the highest 'level' value. This corresponds to the
     *     'value' column of acl_hierarchies table and the
     *     roles.json:<acl>:hierarchies:level property.
     *
     * @param XDUser $user             the user for whom the most privileged acl
     *                                 is to be returned.
     * @param string $moduleName       the module that is used to constrain the
     *                                 most privileged acl search. ( optional )
     * @param string $aclHierarchyName the name of the acl hierarchy to
     *                                 constrain the most privileged acl search.
     *                                 (optional)
     * @return Acl|null If the user does not have an acl that satisfies the
     *                  constraints then null will be returned. Else, the acl
     *                  is returned as an instantiated Acl object.
     * @throws Exception if the user does not have a user id.
     */
    public static function getMostPrivilegedAcl(XDUser $user, $moduleName = DEFAULT_MODULE_NAME, $aclHierarchyName = 'acl_hierarchy')
    {
        if (null === $user->getUserID()) {
            throw new Exception('A valid user id must be supplied.');
        }

        $query = <<<SQL
SELECT DISTINCT
  a.*,
  aclp.abbrev organization,
  aclp.id     organization_id,
  aclh.level
FROM acls a
  JOIN user_acls ua
    ON a.acl_id = ua.acl_id
  JOIN acl_types at
    ON a.acl_type_id = at.acl_type_id
  LEFT JOIN (
    SELECT
      ah.acl_id,
      ah.level
    FROM acl_hierarchies ah
      JOIN hierarchies h
        ON ah.hierarchy_id = h.hierarchy_id
    WHERE h.name = :acl_hierarchy_name
  ) aclh
    ON aclh.acl_id = ua.acl_id
  LEFT JOIN (
    SELECT
      uagbp.acl_id,
      uagbp.user_id,
      o.abbrev,
      o.id
    FROM modw.organization o
      JOIN user_acl_group_by_parameters uagbp
        ON o.id = uagbp.value
      JOIN group_bys gb
        ON uagbp.group_by_id = gb.group_by_id
      WHERE gb.name = 'provider'
  ) aclp
    ON aclp.acl_id = ua.acl_id AND
    aclp.user_id = ua.user_id
-- left join to allows us to prefer user_acl_group_by_parameter records
-- with values found in modw.serviceprovider
  LEFT JOIN (
    SELECT DISTINCT
      gt.organization_id AS id,
      gt.short_name AS short_name,
      gt.long_name AS long_name
    FROM
      modw.serviceprovider gt
    WHERE 1
  ) spv ON spv.id = aclp.id
WHERE ua.user_id = :user_id AND
  at.name != 'feature'
ORDER BY COALESCE(aclh.level, 0) DESC, COALESCE(aclp.id, 0) DESC
LIMIT 1
SQL;
        $db = DB::factory('database');
        $rows = $db->query($query, array(
            ':acl_hierarchy_name' => $aclHierarchyName,
            ':user_id' => $user->getUserID()
        ));

        if (count($rows) > 0) {
            return new Acl($rows[0]);
        }

        return null;
    }

    /**
     * Attempts to retrieve the set of acls who have an acl_type with the name
     * aclTypeName. If no records are found then an empty array will be
     * returned.
     *
     * @param string $aclTypeName
     * @return array
     */
    public static function getAclsByTypeName($aclTypeName)
    {
        $db = DB::factory('database');

        $query = <<<SQL
SELECT a.* 
FROM acls a 
  JOIN acl_types at ON a.acl_type_id = at.acl_type_id
WHERE at.name = :acl_type_name
SQL;
        $rows = $db->query(
            $query,
            array(
                ':acl_type_name' => $aclTypeName
            )
        );

        if (count($rows) > 0) {
            return array_reduce($rows, function ($carry, $item) {
                $carry[] = new Acl($item);
                return $carry;
            }, array());
        }
        return array();
    }

    /**
     * Function meant to replace User\aRole::getQueryDescripters. Retrieves the set of group_bys
     * that a user is authorized to see based on their assigned acls. The base data is stored in the
     * `acl_group_bys` table with additional information merged in from roles.json ( anything that
     * isn't 'realm' or 'group_by' ).
     *
     * @param XDUser $user
     * @param string $realmName
     * @param string $groupByName
     * @param string $statisticName
     * @return array
     * @throws Exception if there is a problem executing sql
     */
    public static function getQueryDescripters(XDUser $user, $realmName = null, $groupByName = null, $statisticName = null)
    {
        $selectClauses = array(
            'r.display as realm',
            'gb.name as group_by',
            '!agb.enabled as not_enabled'
        );

        if (isset($statisticName)) {
            $selectClauses[] = 'agb.visible';
        }

        // Note: this type of dynamic sql is safe as we're not including any user defined input
        // in the sql itself.
        $selectClause = implode(
            ",\n",
            $selectClauses
        );

        $query = <<<SQL
        SELECT DISTINCT
  $selectClause
FROM group_bys gb
  JOIN realms r ON gb.realm_id = r.realm_id
  JOIN acl_group_bys agb
    ON agb.group_by_id = gb.group_by_id AND
       agb.realm_id = gb.realm_id
  JOIN statistics s ON s.statistic_id = agb.statistic_id AND s.realm_id = r.realm_id
  JOIN user_acls ua ON agb.acl_id = ua.acl_id AND ua.acl_id IN (
    SELECT af.*
    FROM (
           SELECT a.acl_id
           FROM (
                  SELECT
                    ah3.acl_hierarchy_id,
                    ah3.acl_id,
                    ah3.hierarchy_id,
                    ah3.level
                  FROM acl_hierarchies ah3
                    JOIN user_acls ua1 ON ah3.acl_id = ua1.acl_id
                  WHERE ua1.user_id = :user_id
                ) ah1
             LEFT JOIN (
                         SELECT
                           ah4.acl_hierarchy_id,
                           ah4.hierarchy_id,
                           ah4.level
                         FROM acl_hierarchies ah4
                           JOIN user_acls ua2 ON ah4.acl_id = ua2.acl_id
                         WHERE ua2.user_id = :user_id
                       ) ah2
               ON ah2.hierarchy_id = ah1.hierarchy_id AND ah2.level > ah1.level
             JOIN acls a ON a.acl_id = ah1.acl_id
           WHERE ah2.acl_hierarchy_id IS NULL
         ) af
    UNION
    SELECT a.acl_id
    FROM acls a
      JOIN acl_types at ON at.acl_type_id = a.acl_type_id
      JOIN user_acls ua3 ON a.acl_id = ua3.acl_id
      LEFT JOIN acl_hierarchies ah ON ah.acl_id = a.acl_id
    WHERE
      ah.acl_hierarchy_id IS NULL AND
      ua3.user_id = :user_id AND
      at.name = 'data'
  )
WHERE ua.user_id = :user_id
SQL;

        $params = array(
            ':user_id' => $user->getUserID()
        );

        if (isset($realmName)) {
            $query .= " AND r.name = :realm_name\n";
            $params[':realm_name'] = $realmName;

        }

        if (isset($groupByName)) {
            $query .= " AND gb.name = :group_by_name\n";
            $params[':group_by_name'] = $groupByName;
        }

        if (isset($statisticName)) {
            $query .= " AND s.name = :statistic_name\n";
            $params[':statistic_name'] = $statisticName;
        }

        $results = array();
        $sorted = array();

        $db = DB::factory('database');
        $rows = $db->query($query, $params);

        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $descripter = new QueryDescripter(
                    'tg_usage',
                    $row['realm'],
                    $row['group_by']
                );

                $descripter->setDisableMenu((bool)$row['not_enabled']);

                if (isset($statisticName)) {
                    $descripter->setDefaultStatisticName($statisticName);
                    $descripter->setShowMenu((bool)$row['visible']);
                }

                // NOTE: this is done so that the GroupByNone query descripter does not have it's
                // groupByInstance populated. Again, just matching aRole::getQueryDescripters.
                $order = $row['group_by'] === 'none'
                    ? $descripter->getOrderId()
                    : $descripter->getMenuLabel();

                // We need to save the group_by / realm / order for later
                $results[] = array($descripter, $row['group_by'], $row['realm'], $order);
            }

            // Now we sort the created query descripters
            usort(
                $results,
                function ($a, $b) {
                    list($aQueryDescripter, $aGroupBy, $aRealm, $aOrder) = $a;
                    list($bQueryDescripter, $bGroupBy, $bRealm, $bOrder) = $b;

                    return strcmp(
                        $aOrder,
                        $bOrder
                    );
                }
            );

            // Now we setup the final structure of the results based on what parameters we were given.
            foreach ($results as $queryDescripterInfo) {
                list($queryDescripter, $groupBy, $realm) = $queryDescripterInfo;
                if (isset($realmName) && isset($groupByName) && isset($statisticName)) {
                    $sorted = $queryDescripter;
                } elseif (isset($realmName) && isset($groupByName)) {
                    $sorted = $queryDescripter;
                } elseif (isset($realmName)) {
                    $sorted[$groupBy]['all'] = $queryDescripter;
                } else {
                    $sorted[$realm][$groupBy]['all'] = $queryDescripter;
                }
            }
        }
        return $sorted;
    }

    /**
     * This function is meant as a replacement for aRole::hasDataAccess. It executes a db query that
     * will tell us whether or not the realm / group_by combination has a record in acl_group_bys
     * (present) and whether or not it is present but disabled ( disabled ). Given these values, the
     * return value is determined as follows:
     *   - if (!present && disabled) => false
     *   - else                      => !(present && disabled)
     *
     * NOTE: We take in a $statisticName parameter but it is not used. This is due to
     * aRole::hasDataAccess doing the same thing ( don't let the code fool you, the
     * $defaultStatisticName is, as of writing, always 'all'. This means that regardless of what
     * $statistic the user provides, the default query descripter for the specified realm / group_by
     * is retrieved. It's defaultStatisticName is set to the statistic the user passed in and then
     * returned. ).
     *
     * One might ask themselves, "Where does the statistic filtering occur then?". Well,
     * it occurs in the following locations ( again, as of writing ):
     * Statistic filtering ( datawarehouse.json::<realm>::statistics::<statistic>::visible: false )
     * occurs in:
     *   - html/controllers/metric_explorer/get_dw_descripter.php
     *   - html/controllers/user_interface/get_menus.php
     *   - classes/DataWarehouse/Access/Usage.php::getCharts ( if it's not a single metric query )
     *
     * @param XDUser $user the user for whom the authorization is performed
     * @param string $realmName the realm to be used in determining access.
     * @param string $groupByName the group_by to be used in determining access.
     * @param string $statisticName the statistic to be used in determining access. ( Eventually )
     * @param string $aclName the acl to be used in determining access.
     * @return bool
     * @throws Exception if there is a problem executing a sql statement.
     */
    public static function hasDataAccess(
        XDUser $user,
        $realmName,
        $groupByName,
        $statisticName = null,
        $aclName = null
    ) {
        // Query to tell whether or not this user has a 'query descriptor'
        $presentQuery = <<<SQL
SELECT DISTINCT agb.realm_id
    FROM acl_group_bys agb
      JOIN user_acls ua ON agb.acl_id = ua.acl_id
      JOIN group_bys gb ON agb.group_by_id = gb.group_by_id
      JOIN statistics s ON agb.statistic_id = s.statistic_id
      JOIN acls a ON ua.acl_id = a.acl_id
      JOIN realms r ON gb.realm_id = r.realm_id AND
                       agb.realm_id = r.realm_id AND
                       s.realm_id = r.realm_id

    WHERE
      r.name = :realm_name AND
      gb.name = :group_by_name 
SQL;
        // Query to tell whether or not they have a 'query descriptor' and it's disabled but still
        // visible.
        $disabledQuery = <<<SQL
SELECT DISTINCT agb.realm_id
    FROM acl_group_bys agb
      JOIN user_acls ua ON agb.acl_id = ua.acl_id
      JOIN group_bys gb ON agb.group_by_id = gb.group_by_id
      JOIN statistics s ON agb.statistic_id = s.statistic_id
      JOIN acls a ON ua.acl_id = a.acl_id
      JOIN realms r ON gb.realm_id = r.realm_id AND
                       agb.realm_id = r.realm_id AND
                       s.realm_id = r.realm_id

    WHERE agb.enabled = false AND
          r.name = :realm_name AND
          gb.name = :group_by_name 

SQL;

        // NOTE: we explicitly strtolower the $realmName because it is often passed in ucfirst which
        // will not match realms.name values.
        $params = array(
            ':realm_name' => strtolower($realmName),
            ':group_by_name' => $groupByName,
            ':user_id' => $user->getUserID()
        );

        // NOTE: The current aRole::checkDataAccess does not support filtering based on
        // statistic. This means that a user has 'access' to a query descripter so long as the
        // appropriate roles.json::roles::<role>::query_descripter::<query_descripter> does not
        // contain the `"enabled": false` property.


        // if the user specified an acl then make sure to modify both sub queries.
        if (isset($aclName)) {
            $presentQuery .= "\nAND a.name = :acl_name\n";
            $disabledQuery .= "\nAND a.name = :acl_name\n";
            $params[':acl_name'] = $aclName;
        }

        // the parent query that brings both of the sub-queries together.
        $query = <<<SQL
SELECT
  (
    $presentQuery
  ) as present,
  (
    $disabledQuery
  ) as disabled;

SQL;

        $db = DB::factory('database');
        $rows = $db->query($query, $params);

        // we're always going to have 1 row, even when neither of the sub-queries returns a record
        // due to the way we structured everything.
        $row = $rows[0];

        // Check if we have 'null' values in either column.
        $present = isset($row['present']);
        $disabledButVisible = isset($row['disabled']);

        // Present | Disabled | Return
        //    T    |     T    |    F
        //    T    |     F    |    T
        //    F    |     F    |    T
        //    F    |     T    |    F
        if (!$present && $disabledButVisible) {
            return false;
        } else {
            return !($present && $disabledButVisible);
        }
    }
}
