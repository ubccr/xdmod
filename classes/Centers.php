<?php

use CCR\DB;
use CCR\DB\iDatabase;
use User\Acl;
use User\Acls;

/**
 * Class Centers
 *
 * This class is meant to provide functionality having to do with a User's
 * 'Center'. This mainly grew out of the desire to lift the business logic type
 * functions out of classes like CenterDirectorRole and CenterStaffRole and
 * consolidate them into a one stop shop for all of your 'Center' related needs.
 *
 * In so far as database tables that Centers interacts with, the primary table
 * is 'user_acl_group_by_parameters'. This table holds a relation between a :
 *    - user
 *    - acl
 *    - group_by
 *    - organization_id ( which is stored in the value column )
 *
 * In particular this table / class attempts to fulfil the need to relate a User
 * ( that may or may not be associated with a Person ) with an organization,
 * in the context of an acl and group_by.
 */
class Centers
{
    /**
     * Default acl name to use when listing a user's center(s).
     *
     * @var string
     */
    const DEFAULT_ACL_NAME = 'cs';

    /**
     * Attempt to list all users that currently have the center staff acl with a
     * relation to the same organization as the provided user is a center
     * director for.
     *
     * @param XDUser $user
     * @return array
     */
    public static function listStaffForUser(XDUser $user)
    {
        return self::_listStaffForUser(
            DB::factory('database'),
            $user
        );
    }

    /**
     * Attempt to list the organization associated with this user, and optionally
     * within the context of the '$aclName' provided. By default the acl name
     * that is used is the Center Staff acl.
     *
     * @param XDUser $user
     * @param null $aclName
     * @return array[]
     * @throws Exception
     */
    public static function listCenterForUser(XDUser $user, $aclName = null)
    {
        // Check to see if they did not include an $aclName or passed null.
        if (null == $aclName) {
            $aclName = self::DEFAULT_ACL_NAME;
        }

        // Catch if they passed an empty string.
        if (strlen($aclName) < 1) {
            throw new Exception('A valid acl name must be provided.');
        }

        return self::_listCenterForUser(
            DB::factory('database'),
            $user,
            $aclName
        );
    }

    /**
     * Attempt to retrieve a list of all the Centers ( organizations ) that the
     * provided user has a relation to via the provided '$aclName'.
     *
     * @param XDUser $user
     * @param null $aclName
     * @return array[]
     * @throws Exception
     */
    public static function listCentersForUser(XDUser $user, $aclName = null)
    {
        // Check to see if they did not include an $aclName or passed null.
        if (null == $aclName) {
            $aclName = self::DEFAULT_ACL_NAME;
        }

        // Catch if they passed an empty string.
        if (strlen($aclName) < 1) {
            throw new Exception('A valid acl name must be provided.');
        }

        return self::_listCentersForUser(
            DB::factory('database'),
            $user,
            $aclName
        );
    }

    /**
     * Attempt to downgrade ( remove the relation ) a user who is a center
     * director for the Center identified by the provided '$centerId'. A small
     * side effect of this function is that if the user being downgraded is no
     * longer a center director of a center then the center director acl will
     * be removed from the user.
     *
     * @param XDUser $user
     * @param $centerId
     * @throws Exception
     */
    public static function downgradeStaffMember(XDUser $user, $centerId)
    {
        if (!isset($centerId)) {
            throw new Exception('A valid center id must be provided.');
        }

        $db = DB::factory('database');
        $acl = Acls::getAclByName(ROLE_ID_CENTER_DIRECTOR);
        if (null === $acl) {
            throw new Exception('Unable to upgrade staff member. Unable to find center director acl');
        }


        self::_downgradeStaffMember(
            $db,
            $user,
            $acl,
            $centerId
        );

        $hasCenterDirectorAffiliations = self::hasCenterDirectorAffiliations($db, $user);
        if (false === $hasCenterDirectorAffiliations) {
            $user->removeAcl($acl);
            $user->saveUser();
        }
    }

    /**
     * Attempt to 'upgrade' a user to be a center director of the same center
     * as the upgrading user. A side effect of this function is that if the user
     * does not have the center director acl it will ensure that the user receives
     * it.
     *
     * @param XDUser $user
     * @throws Exception
     */
    public static function upgradeStaffMember(XDUser $user)
    {
        $acl = Acls::getAclByName(ROLE_ID_CENTER_DIRECTOR);
        if (null === $acl) {
            throw new Exception('Unable to upgrade staff member. Unable to find center director acl');
        }
        $userHasAcl = $user->hasAcl($acl);

        if (false === $userHasAcl) {
            $user->addAcl($acl);
        }

        self::_upgradeStaffMember(
            DB::factory('database'),
            $user,
            $acl
        );

        if (false === $userHasAcl) {
            $user->saveUser();
        }
    }

    /**
     * Attempt to ascertain whether or not the provided user is a center director
     * of the Center identified by the provided '$centerId'.
     *
     * @param XDUser $user
     * @param $centerId
     * @return bool|null
     * @throws Exception
     */
    public static function isCenterDirector(XDUser $user, $centerId)
    {
        if (null === $centerId) {
            throw new Exception('A valid center id must be provided.');
        }

        return self::_isCenterDirector(
            DB::factory('database'),
            $user,
            $centerId
        );
    }

    /**
     * Attempt to set ( first completely remove and then add ) the user's center
     * relations for the provided $aclName.
     *
     * @param XDUser $user
     * @param $aclName
     * @param array $centerIds
     * @throws Exception
     */
    public static function setUserCentersByAcl(XDUser $user, $aclName, array $centerIds = array())
    {

        if (null === $aclName) {
            throw new Exception('A valid acl name must be provided.');
        }

        if (strlen($aclName) < 1) {
            throw new Exception('A valid acl name must be provided.');
        }

        self::_setUserCenters(
            DB::factory('database'),
            $user,
            $aclName,
            $centerIds
        );
    }

    /**
     * Attempt to update the 'organization_id' column of the Users table
     * for the provided user.
     *
     * @param XDUser $user
     * @param $organizationId
     * @throws Exception
     */
    public static function setUserOrganization(XDUser $user, $organizationId)
    {
        if (null === $organizationId) {
            throw new Exception('You must provide a valid organization id.');
        }

        self::_setUserOrganization(
            DB::factory('database'),
            $user,
            $organizationId
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
            ':provider_name' => $providerName,
            ':center_director_name' => $centerDirectorName,
            ':user_id' => $user->getUserID()
        ));

        if ($rows !== false && count($rows) > 0) {
            return $rows;
        }

        return array();
    }

    /**
     * @param iDatabase $db
     * @param XDUser $user
     * @param Acl $acl
     * @param mixed $centerId
     * @return bool
     */
    private static function _downgradeStaffMember(iDatabase $db, XDUser $user, Acl $acl, $centerId)
    {
        $query = <<<SQL
DELETE FROM user_acl_group_by_parameters
WHERE user_id = :user_id
      AND acl_id = :acl_id
      AND value = :value
      AND group_by_id IN (
  SELECT gb.group_by_id
  FROM group_bys gb
  WHERE gb.name = 'provider');
SQL;
        $rows = $db->execute($query, array(
            ':acl_id' => $acl->getAclId(),
            ':user_id' => $user->getUserID(),
            ':value' => $centerId
        ));

        return $rows !== false && $rows > 0;
    }

    /**
     * @param iDatabase $db
     * @param XDUser $user
     * @param Acl $acl
     * @return int the number of rows inserted. Should be >= 1.
     */
    private static function _upgradeStaffMember(iDatabase $db, XDUser $user, Acl $acl)
    {
        $query = <<<SQL
        INSERT INTO user_acl_group_by_parameters(user_id, acl_id, group_by_id, value)
        SELECT inc.*
        FROM (
          SELECT DISTINCT
            :user_id       AS user_id,
            :acl_id        AS acl_id,
            gb.group_by_id AS group_by_id,
            uagbp.value    AS value
        FROM group_bys gb, user_acl_group_by_parameters uagbp
        WHERE gb.name = 'provider'
             AND uagbp.user_id = :user_id
             AND uagbp.acl_id = :acl_id
        ) inc
        LEFT JOIN user_acl_group_by_parameters cur
          ON cur.user_id = inc.user_id
            AND cur.acl_id = inc.acl_id
            AND cur.group_by_id = inc.group_by_id
            AND cur.value = inc.value
        WHERE cur.user_acl_parameter_id IS NULL;
SQL;
        return $db->execute($query, array(
            ':user_id' => $user->getUserID(),
            ':acl_id' => $acl->getAclId()
        ));
    }

    /**
     * @param iDatabase $db
     * @param XDUser $user
     * @return null|boolean
     */
    private static function hasCenterDirectorAffiliations(iDatabase $db, XDUser $user)
    {
        $query = <<<SQL
SELECT COUNT(data.value) total
FROM (
       SELECT DISTINCT uagbp.value
       FROM user_acl_group_by_parameters uagbp
         JOIN acls a ON uagbp.acl_id = a.acl_id
         JOIN group_bys gb ON uagbp.group_by_id = gb.group_by_id
       WHERE a.name = 'cd'
             AND uagbp.user_id = :user_id
             AND gb.name = 'provider'
     ) data;
SQL;
        $rows = $db->query($query, array(
            'user_id' => $user->getUserID()
        ));
        if ($rows != false && count($rows) > 0) {
            return $rows[0]['total'] > 0;
        }
        return null;
    }

    /**
     * @param iDatabase $db
     * @param XDUser $user
     * @param string $aclName
     *
     * @return array[]
     */
    private static function _listCenterForUser(iDatabase $db, XDUser $user, $aclName)
    {
        $query = <<<SQL
SELECT DISTINCT uagbp.value
FROM user_acl_group_by_parameters uagbp
  JOIN acls a ON uagbp.acl_id = a.acl_id
  JOIN group_bys gb ON uagbp.group_by_id = gb.group_by_id
WHERE
  a.name = :acl_name
  AND uagbp.user_id = :user_id
  AND gb.name = 'provider'
ORDER BY uagbp.value DESC LIMIT 1;
SQL;

        $rows = $db->query($query, array(
            ':acl_name' => $aclName,
            ':user_id' => $user->getUserID()
        ));
        if ($rows !== false) {
            return $rows[0]['value'];
        }

        return array();
    }

    /**
     * @param iDatabase $db
     * @param XDUser $user
     * @param string $aclName
     *
     * @return array[]
     */
    private static function _listCentersForUser(iDatabase $db, XDUser $user, $aclName)
    {
        $query = <<<SQL
SELECT DISTINCT uagbp.value
FROM user_acl_group_by_parameters uagbp
  JOIN acls a ON uagbp.acl_id = a.acl_id
  JOIN group_bys gb ON uagbp.group_by_id = gb.group_by_id
WHERE
  a.name = :acl_name
  AND uagbp.user_id = :user_id
  AND gb.name = 'provider';
SQL;

        $rows = $db->query($query, array(
            ':acl_name' => $aclName,
            ':user_id' => $user->getUserID()
        ));
        if ($rows !== false && count($rows) > 0) {
            return array_reduce($rows, function ($carry, $item) {
                $carry [] = $item['value'];
                return $carry;
            }, array());
        }

        return array();
    }

    /**
     * @param iDatabase $db
     * @param XDUser $user
     * @param integer $centerId
     *
     * @return bool|null
     */
    private static function _isCenterDirector(iDatabase $db, XDUser $user, $centerId)
    {
        $query = <<<SQL
SELECT COUNT(*) AS num_matches
FROM (
       SELECT DISTINCT value
       FROM user_acl_group_by_parameters uagbp
         JOIN group_bys gb
           ON uagbp.group_by_id = gb.group_by_id
              AND gb.name = 'provider'
         JOIN acls a
           ON uagbp.acl_id = a.acl_id
              AND a.name = :acl_name
       WHERE uagbp.user_id = :user_id
             AND uagbp.value = :value) data
SQL;
        $rows = $db->query($query, array(
            ':acl_name' => ROLE_ID_CENTER_DIRECTOR,
            ':user_id' => $user->getUserID(),
            ':value' => $centerId
        ));
        if ($rows !== false && count($rows) > 0) {
            return $rows[0]['num_matches'] > 0;
        }

        return null;
    }

    private static function _setUserCenters(iDatabase $db, XDUser $user, $aclName, array $centerIds)
    {
        $delete = <<<SQL
DELETE FROM user_acl_group_by_parameters
WHERE   user_id = :user_id
AND acl_id IN ( SELECT a.acl_id FROM acls a WHERE a.name = :acl_name)
AND group_by_id IN (SELECT gb.group_by_id FROM group_bys gb WHERE gb.name = 'provider')
AND value IN (:center_ids);
SQL;
        $insert = <<<SQL
INSERT INTO user_acl_group_by_parameters (user_id, acl_id, group_by_id, value)
  SELECT inc.*
  FROM (
         SELECT DISTINCT
           :user_id       AS user_id,
           a.acl_id       AS acl_id,
           gb.group_by_id AS group_by_id,
           o.id         AS value
         FROM group_bys gb, modw.organization o, acls a 
         WHERE gb.name = 'provider'
               AND a.name = :acl_name
               AND o.id IN (:center_ids)
       ) inc
    LEFT JOIN user_acl_group_by_parameters cur
      ON cur.user_id = inc.user_id
         AND cur.acl_id = inc.acl_id
         AND cur.group_by_id = inc.group_by_id
         AND cur.value = inc.value
  WHERE cur.user_acl_parameter_id IS NULL;
SQL;
        $handle = $db->handle();

        $quotedCenterIds = implode(',', array_reduce($centerIds, function ($carry, $item) use ($handle) {
            $carry [] = $handle->quote($item, PDO::PARAM_STR);
            return $carry;
        }, array()));

        $params = array(
            ':user_id' => $user->getUserID(),
            ':acl_name' => $aclName,
            ':center_ids' => $quotedCenterIds
        );

        // First remove previous centers
        $db->execute($delete, $params);

        // Then set the ones provided.
        $db->execute($insert, $params);
    }

    private static function _setUserOrganization(iDatabase $db, XDUser $user, $organizationId)
    {
        $query = <<<SQL
UPDATE Users SET organization_id = :organization_id WHERE id = :user_id;
SQL;

        $db->execute($query, array(
            ':organization_id' => $organizationId,
            ':user_id' => $user->getUserID()
        ));
    }

}