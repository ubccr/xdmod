<?php namespace Models\Services;

use CCR\DB;

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
}
