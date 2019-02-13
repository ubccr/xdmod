<?php

use CCR\DB;

class XDStatistics
{

    /**
     * Retrieve the number of user visits for the provided $user_types, by default all of them,
     * formatted for the given $aggregation_type.
     *
     * @param string $aggregation_type by default 'month', also accepts 'year'.
     * @param array $user_types an array of user type id's
     * @return mixed returns an array per row found w/ the following key/values:
     *   - last_name:      Users.last_name
     *   - first_name:     Users.first_name
     *   - email_address:  Users.email_address
     *   - username:       Users.username
     *   - role_list:      comma concatenated list of Acls.display per acl assigned to this user
     *   - user_type:      UserTypes.type that corresponds to Users.user_type
     *   - timeframe:      based on $aggregation_type, 'YYYY-MM' for 'month' and 'YYYY' for 'year'
     *   - visit_frequency The number of records found in SessionManager for this user in the
     *                     timeframe defined by aggregation_type
     * @throws Exception if there is a problem retrieving data from the db.
     */
    function getUserVisitStats($aggregation_type = 'month', $user_types = array())
    {

        $db = DB::factory('database');

        $aggregationFormat = '%Y-%m';
        if ($aggregation_type === 'year') {
            $aggregationFormat = '%Y';
        }

        // Default to a no-op if no use_types are provided.
        $whereClause = '1 = 1';
        if (!empty($user_types)) {
            $inValues = array_map(
                function ($value) use ($db) {
                    return $db->quote($value);
                },
                $user_types
            );
            $whereClause = 'ud.user_type IN (' . implode(',', $inValues) . ')';
        }

        $query = <<<SQL
SELECT ud.last_name,
       ud.first_name,
       ud.email_address,
       ud.username,
       CONCAT('"', ud.role_list, '"') as role_list,
       ud.type      as                                user_type,
       DATE_FORMAT(FROM_UNIXTIME(sm.init_time), '$aggregationFormat') timeframe,
       COUNT(ud.id) as                                visit_frequency
FROM SessionManager sm
/* We split out the user-data from the session manager data
* so that we can isolate the group bys.
*/
JOIN (
    SELECT u.id,
           u.last_name,
           u.first_name,
           u.email_address,
           u.username,
           u.user_type,
           ut.type,
           GROUP_CONCAT(a.display ORDER BY a.display) as role_list
    FROM Users u
      JOIN user_acls ua ON ua.user_id = u.id
      JOIN acls a ON a.acl_id = ua.acl_id
      JOIN UserTypes ut ON ut.id = u.user_type
      GROUP BY
        u.id,
        u.last_name,
        u.first_name,
        u.email_address,
        u.username,
        u.user_type,
        ut.type
) as ud
  ON ud.id = sm.user_id
WHERE $whereClause
GROUP BY
  ud.id,
  ud.email_address,
  ud.type,
  ud.role_list,
  timeframe
ORDER BY
  timeframe DESC,
  visit_frequency DESC,
  ud.role_list DESC;
SQL;

        return $db->query($query);
    } //getUserVisitStats

} //XDStatistics
