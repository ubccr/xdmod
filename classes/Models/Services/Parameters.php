<?php

namespace Models\Services;

use CCR\DB;

/**
 * Class Parameters
 *
 * This class is meant to provide the parameters used by other parts of XDMoD to filter requested data.
 *
 * @package Models\Services
 */
class Parameters
{

    /**
     * Retrieve the parameters ( ultimately where clauses ) for the specified $user & $aclName. An
     * empty array means that there are no where clauses & hence unrestricted.
     *
     * @param \XDUser $user the user for whom the parameters are to be retrieved
     * @param string $aclName the acl to use when retrieving the parameters
     * @return array in the form: array($dimensionName => $dimensionValue)
     * @throws \Exception if the 'database' db config cannot be found.
     */
    public static function getParameters(\XDUser $user, $aclName)
    {
        $parameters = array();

        $db = DB::factory('database');

        $sql = <<<SQL
/* Prefer selecting the explicit filters provided by a users acls */
SELECT DISTINCT
    gb.name,
    uagbp.value
FROM moddb.user_acl_group_by_parameters uagbp
    JOIN user_acls ua ON uagbp.acl_id = ua.acl_id AND uagbp.user_id = ua.user_id
    JOIN acls a ON a.acl_id = ua.acl_id
    JOIN group_bys gb ON gb.group_by_id = uagbp.group_by_id
    JOIN Users u ON ua.user_id = u.id
WHERE u.id   = :user_id  AND
        a.name = :acl_name
UNION
/* If a user does not have a record in user_acl_group_by_parameters then, based on the acl being requested and the
   group by that corresponds to that acls dimension, return the correct user property for filtering.
   NOTE: This is to maintain the behavior of this code that was originally located in `Roles::getParameters`
   */
SELECT DISTINCT gb.name AS group_by,
                CASE
                    WHEN gb.name IN ('provider', 'organization', 'institution') THEN u.organization_id
                    WHEN gb.name IN ('person', 'pi') THEN u.person_id
                    END AS value
FROM acl_dimensions ad
    JOIN group_bys  gb ON ad.group_by_id = gb.group_by_id
    JOIN acls       a ON ad.acl_id = a.acl_id
    JOIN Users u
WHERE u.id = :user_id AND
      a.name = :acl_name;
SQL;
        $results = $db->query(
            $sql,
            array(
                ':user_id' => $user->getUserID(),
                ':acl_name' => $aclName
            )
        );

        foreach($results as $row) {
            $parameters[$row['name']] = $row['value'];
        }

        return $parameters;
    }
}
