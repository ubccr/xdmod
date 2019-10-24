<?php namespace Models\Services;

use Exception;

use CCR\DB;

/**
 * Class Centers
 * @package User
 *
 * It is the intended purpose of this class to provide a host of functions to
 * ease working with and within the Acl framework. It provides basic CRUD
 * functionality in addition to a number of other functions related to Acls and
 * their associated pieces of data.
 *
 */
class Centers
{

    /**
     * Retrieves a listing of the current centers in XDMoD.
     *
     * @return array
     * @throws Exception if there is a problem retrieving a db connection
     * @throws Exception if there is a problem executing the sql statement.
     */
    public static function getCenters()
    {
        $db = DB::factory('database');
        return $db->query("
    SELECT DISTINCT o.*
    FROM modw.organization o
      JOIN modw.resourcefact rf
        ON o.id = rf.organization_id;
");
    }

    /**
     * Retrieves whether or not the User identified by $userId has a "valid"
     * relation with the Center identified by $centerId.
     * In this context "valid" means:
     *   - The user has a user_acl record for $centerAclName
     *   - The user has a corresponding user_acl_group_by_parameter record for
     *     $centerAclName / $centerId
     * @param $userId
     * @param $centerId
     * @param $centerAclName
     * @return bool true if the user has a relationship w/ the acl identified by $centerAclName and a
     * corresponding record in user_acl_group_by_parameters else false.
     * @throws Exception if there is a problem obtaining a database connection
     * @throws Exception if there is a problem executing a sql statement
     */
    public static function hasCenterRelation($userId, $centerId, $centerAclName)
    {
        $query = <<<SQL
SELECT DISTINCT
  u.id,
  u.username,
  ua.*,
  uagbp.value
FROM Users u
JOIN user_acls ua ON u.id = ua.user_id
JOIN acls a ON ua.acl_id = a.acl_id
JOIN user_acl_group_by_parameters uagbp
ON u.id = uagbp.user_id
   AND uagbp.acl_id = a.acl_id
WHERE a.name = :acl_name AND
  uagbp.value = :center_id AND
      u.id = :user_id;
SQL;
        $params = array(
            ':user_id' => $userId,
            ':center_id' => $centerId,
            ':acl_name' => $centerAclName
        );
        $db = DB::factory('database');
        return count($db->query($query, $params)) > 0;
    }

    /**
     * Remove a center relation for the center identified by $centerId / $aclName
     * from the user identified by $userId.
     *
     * @param integer $userId
     * @param integer $centerId
     * @param string $aclName
     * @throws Exception if there is a problem obtaining a database connection
     * @throws Exception if there is a problem executing a sql statement
     */
    public static function removeCenterRelation($userId, $centerId, $aclName)
    {
        $query = <<<SQL
DELETE FROM moddb.user_acl_group_by_parameters 
WHERE 
  user_id = :user_id                                         AND 
  acl_id  = (SELECT a.acl_id FROM moddb.acls a WHERE a.name = :acl_name) AND
  value = :center_id;
SQL;

        $params = array(
            ':user_id' => $userId,
            ':center_id' => $centerId,
            ':acl_name' => $aclName
        );

        $db = DB::factory('database');

        // Ensure that the center relation is removed from the current table.
        $db->execute($query, $params);
    }
}
