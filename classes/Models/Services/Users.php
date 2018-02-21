<?php namespace Models\Services;

use Exception;
use CCR\DB;
use XDUser;

class Users
{

    const DB_SECTION_NAME = 'database';

    /**
     * Function to retrieve a list of users that can optionally be filtered by
     * user type, acl, having a valid email address and or a value that will be
     * used as a wildcard filter on the username, first_name and last_name columns.
     *
     * @param int $userTypeId the user type on which to filter the
     *                                    returned list of users.
     * @param int $aclId the acl that the users returned must
     *                                    have.
     * @param string $searchFragment a value that will be used to conduct
     *                                    wildcard searches on the username,
     *                                    first_name, and last_name columns.
     * @param bool $requireEmailAddress if true is specified then users must
     *                                    have a valid email address to be
     *                                    included
     * @return array of users matching the specified parameters.
     * @throws Exception if there is a problem obtaining a database connection
     * @throws Exception if there is a problem executing a sql statement
     */
    public static function getUsers($userTypeId, $aclId, $searchFragment, $requireEmailAddress = false)
    {
        $db = DB::factory(self::DB_SECTION_NAME);

        if ($userTypeId === 'all') {
            $userTypeId = null;
        }

        if ($aclId === 'any') {
            $aclId = null;
        }

        if (empty($searchFragment)) {
            $searchFragment = null;
        }

        $additionalJoins = '';
        $whereClauses = array();
        $params = array();

        $sql = <<<SQL
SELECT DISTINCT
  CONCAT(u.first_name, ' ', u.last_name) AS formal_name,
  u.id,
  u.username,
  u.first_name,
  u.last_name,
  u.email_address,
  u.user_type,
  u.account_is_active,
  ut.acl_type                            AS role_type,
  CASE WHEN last_session.init_time IS NULL
    THEN '0'
  ELSE last_session.init_time END           last_logged_in
FROM Users u
  LEFT JOIN (
              SELECT
                x.user_id,
                MAX(x.init_time) AS init_time
              FROM SessionManager x
                JOIN (SELECT
                        sm.user_id,
                        MAX(sm.init_time) AS max_init_time
                      FROM SessionManager sm
                      GROUP BY sm.user_id
                     ) y ON y.user_id = x.user_id AND
                            y.max_init_time = x.init_time
              GROUP BY x.user_id
            ) last_session ON last_session.user_id = u.id
  LEFT JOIN (
              SELECT
                ua.user_id,
                GROUP_CONCAT(a.display ORDER BY a.name DESC SEPARATOR ', ') acl_type
              FROM moddb.user_acls ua
                JOIN  moddb.acls a
                  ON a.acl_id = ua.acl_id
              GROUP BY ua.user_id
            ) ut ON ut.user_id = u.id
SQL;
        // If filtering by aclId then add the appropriate joins, where clauses,
        // and params
        if ($aclId !== null) {
            $additionalJoins = <<<SQLF
LEFT JOIN user_acls ua
    ON ua.user_id = u.id
  LEFT JOIN acls a
    ON a.acl_id = ua.acl_id
SQLF;
            $whereClauses [] = 'a.acl_id = :acl_id';
            $params[':acl_id'] = $aclId;
        }

        // If the user has provided a userTypeId to filter by add the appropriate
        // where clauses and params.
        if ($userTypeId !== null) {
            $whereClauses [] = 'u.user_type = :user_type_id';
            $params[':user_type_id'] = $userTypeId;
        }

        // If the user has indicated to only include users with valid emails then
        // add the appropriate where clauses.
        if ($requireEmailAddress === true) {
            $whereClauses [] = "u.email_address != '" . NO_EMAIL_ADDRESS_SET . "'";
        }

        // If the user has provided a search fragment make sure to add the
        // appropriate where clauses and parameters.
        if ($searchFragment !== null) {
            $whereClauses [] = <<<SQLF
(
  u.username  LIKE CONCAT('%', :filter ,'%') OR 
  u.first_name LIKE CONCAT('%', :filter, '%') OR
  u.last_name LIKE CONCAT('%', :filter, '%')
)
SQLF;
            $params[':filter'] = $searchFragment;
        }

        $query = count($whereClauses) > 0
            ? implode("\n", array($sql, $additionalJoins, 'WHERE ', join(" AND \n", $whereClauses)))
            : implode("\n", array($sql, $additionalJoins));

        return $db->query($query, $params);
    }

    /**
     * Attempt to retrieve the list of users who are associated with the $promoters
     * center that are eligible for "promotion". Promotion is defined in this
     * context as granting the "center staff" acl w/ associated parameter record
     * for the promoters center.
     *
     * @param integer $organizationId the id of the organization for whom
     *                                    the list of eligible users should be
     *                                    retrieved.
     * @return array populated if users were found, else an empty array.
     * @throws \Exception if unable to retrieve a database connection.
     * @throws \Exception if unable to query the database.
     */
    public static function getUsersForPromotion($organizationId)
    {
        $db = DB::factory(self::DB_SECTION_NAME);
        $query = <<<SQL
SELECT
  u.id,
  CONCAT(u.last_name, ', ', u.first_name, ' [', COALESCE(o.abbrev, po.abbrev),
         ']') AS name
FROM Users u
  -- we only want organization records w/ a resourcefact entry
  -- i.e. "centers"
  LEFT JOIN modw.organization o
    ON o.id = u.organization_id AND
       o.id IN
       (SELECT DISTINCT rf.organization_id
        FROM modw.resourcefact rf)
  LEFT JOIN modw.person p ON p.id = u.person_id
  LEFT JOIN modw.organization po ON po.id = p.organization_id
  -- exclude users that are 'cd' for the given organization.
  LEFT JOIN (
              SELECT DISTINCT uagbp.user_id
              FROM user_acl_group_by_parameters uagbp
                JOIN acls a ON uagbp.acl_id = a.acl_id
              WHERE a.name = 'cd' AND
                    uagbp.value = :organization_id
            ) has_cd ON has_cd.user_id = u.id
WHERE
  -- only include users that have an organization / person_id
  (u.organization_id IS NOT NULL OR u.person_id IS NOT NULL) AND
  -- exclude users that are a 'cd' for the given organization.
  has_cd.user_id IS NULL AND
  -- include users that have a relation to the specified organization
  (o.id = :organization_id OR po.id = :organization_id);
SQL;
        $params = array(
            ':organization_id' => $organizationId
        );

        return $db->query($query, $params);
    }

    /**
     * Retrieves whether or not a user is "associated" with a given center.
     * In this context "associated" means that $centerId equals one of the
     * following:
     *   - If the users User record has an 'organization_id' record that also
     *     corresponds to a record in resourcefact ( i.e. a center ) prefer
     *     this value.
     *   - If the User instead has a 'person_id' then use this persons
     *     'organization_id' if it is also a center.
     * @param $userId
     * @param $centerId
     * @return mixed
     * @throws Exception if there is a problem retrieving a db connection
     * @throws Exception if there is a problem executing the sql statement.
     */
    public static function userIsAssociatedWithCenter($userId, $centerId)
    {
        $query = <<<SQL
SELECT
  u.id,
  CONCAT(u.last_name, ', ', u.first_name, ' [', COALESCE(o.abbrev, po.abbrev),
         ']') AS name
FROM Users u
  -- we only want organization records w/ a resourcefact entry
  -- i.e. "centers"
  LEFT JOIN modw.organization o
    ON o.id = u.organization_id AND
       o.id IN
       (SELECT DISTINCT rf.organization_id
        FROM modw.resourcefact rf)
  LEFT JOIN modw.person p ON p.id = u.person_id
  LEFT JOIN modw.organization po 
    ON po.id = p.organization_id AND
       po.id IN
       (SELECT DISTINCT rf.organization_id
        FROM modw.resourcefact rf)
  -- exclude users that are 'cd' for the given organization.
  LEFT JOIN (
              SELECT DISTINCT uagbp.user_id
              FROM user_acl_group_by_parameters uagbp
                JOIN acls a ON uagbp.acl_id = a.acl_id
              WHERE a.name = 'cd' AND
                    uagbp.value = :organization_id
            ) has_cd ON has_cd.user_id = u.id
WHERE
  u.id = :user_id AND
  -- exclude users that are a 'cd' for the given organization.*/
  has_cd.user_id IS NULL AND
  (o.id = :organization_id OR po.id = :organization_id);
SQL;
        $params = array(
            ':user_id' => $userId,
            ':organization_id' => $centerId
        );
        $db = DB::factory(self::DB_SECTION_NAME);
        return count($db->query($query, $params)) > 0;
    }

    /**
     * Retrieves the set of centers that a user has a relation to.
     *
     * @param XDUser $user
     * @return mixed
     * @throws Exception if there is a problem retrieving a db connection
     * @throws Exception if there is a problem executing the sql statement.
     */
    public static function getCentersFor(XDUser $user)
    {
        $query = <<<SQL
SELECT DISTINCT 
  uagbp.value 
FROM moddb.user_acl_group_by_parameters uagbp
WHERE uagbp.user_id = :user_id
SQL;
        $params = array(
            ':user_id' => $user->getUserID()
        );

        $db = DB::factory('database');
        return $db->query($query, $params);
    }

    /**
     * Promote the provided $user to 'Center Staff' of the center identified by
     * $centerId.
     *
     * @param XDUser $user
     * @param $centerId
     * @throws Exception if there is a problem retrieving a db connection
     * @throws Exception if there is a problem executing the sql statement.
     */
    public static function promoteUserToCenterStaff(XDUser $user, $centerId)
    {
        if (!$user->hasAcl(ROLE_ID_CENTER_STAFF)) {
            // Add the Center Staff acl to the user.
            $user->setRoles(array_merge($user->getAcls(true), array(ROLE_ID_CENTER_STAFF)));

            // Save changes
            $user->saveUser();
        }

        $centerConfig = array();
        $centerConfig[$centerId] = array('active' => true, 'primary' => true);
        // Add which center the users new center staff acl is related it.
        $user->setOrganizations($centerConfig, ROLE_ID_CENTER_STAFF);
    }

    /**
     * Demote the provided $user from having a relation ( via Center Staff ) to
     * the center identified by $centerId.
     *
     * @param XDUser $user
     * @param $centerId
     * @throws Exception if there is a problem retrieving a db connection
     * @throws Exception if there is a problem executing the sql statement.
     */
    public static function demoteUserFromCenterStaff(XDUser $user, $centerId)
    {
        $currentCenters = array_pop(array_values(Users::getCentersFor($user)));

        // If this user has no more center staff centers then remove the center
        // staff acl.
        if (count(array_diff(array_values($currentCenters), array((string)$centerId))) === 0) {
            // Remove the center staff acl from the user.
            $user->setRoles(array_diff($user->getAcls(true), array(ROLE_ID_CENTER_STAFF)));

            // Save the acl changes.
            $user->saveUser();
        }

        // Remove the center relation from the user.
        Centers::removeCenterRelation($user->getUserID(), $centerId, ROLE_ID_CENTER_STAFF);
    }
}
