<?php

use CCR\DB;
use CCR\DB\iDatabase;
use User\Acl;
use User\Acls;

class Centers
{

    const DEFAULT_ACL_NAME = 'cs';

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

    public static function listCenterForUser(XDUser $user, $aclName = null)
    {
        if (!isset($user)) {
            throw new Exception('A valid user must be provided.');
        }

        if (null == $aclName) {
            $aclName = self::DEFAULT_ACL_NAME;
        }

        if (strlen($aclName) < 1) {
            throw new Exception('A valid acl name must be provided.');
        }

        return self::_listCenterForUser(
            DB::factory('database'),
            $user,
            $aclName
        );
    }

    public static function listCentersForUser(XDUser $user, $aclName = null)
    {
        if (!isset($user)) {
            throw new Exception('A valid user must be provided.');
        }

        if (null == $aclName) {
            $aclName = self::DEFAULT_ACL_NAME;
        }

        if (strlen($aclName) < 1) {
            throw new Exception('A valid acl name must be provided.');
        }

        return self::_listCentersForUser(
            DB::factory('database'),
            $user,
            $aclName
        );
    }

    public static function downgradeStaffMember(XDUser $user, $centerId)
    {
        if (!isset($user)) {
            throw new Exception('A valid user must be provided.');
        }
        if (!isset($centerId)) {
            throw new Exception('A valid center id must be provided.');
        }

        $db = DB::factory('database');
        $acl = Acls::getAclByName(ROLE_ID_CENTER_DIRECTOR);
        if (!isset($acl)) {
            throw new Exception('Unable to upgrade staff member. Unable to find center director acl');
        }


        self::_downgradeStaffMember(
            $db,
            $user,
            $acl,
            $centerId
        );

        $hasCenterDierectorAffiliations = self::hasCenterDirectorAffiliations($db, $user);
        if ($hasCenterDierectorAffiliations !== null && $hasCenterDierectorAffiliations == false) {
            $user->removeAcl($acl);
            $user->saveUser();
        }
    }

    public static function upgradeStaffMember(XDUser $user)
    {
        if (!isset($user)) {
            throw new Exception('A valid user must be provided.');
        }
        $acl = Acls::getAclByName(ROLE_ID_CENTER_DIRECTOR);
        if (!isset($acl)) {
            throw new Exception('Unable to upgrade staff member. Unable to find center director acl');
        }
        $userHasAcl = $user->hasAcl($acl);

        if (!$userHasAcl) {
            $user->addAcl($acl);
        }

        self::_upgradeStaffMember(
            DB::factory('database'),
            $user,
            $acl
        );

        if (!$userHasAcl) {
            $user->saveUser();
        }
    }

    public static function isCenterDirector(XDUser $user, $centerId)
    {
        if (!isset($user)) {
            throw new Exception('A valid user must be provided.');
        }
        if (!isset($centerId)) {
            throw new Exception('A valid center id must be provide.d');
        }
        return self::_isCenterDirector(
            DB::factory('database'),
            $user,
            $centerId
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


}