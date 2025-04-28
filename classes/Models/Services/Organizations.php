<?php

namespace Models\Services;

use CCR\DB;

class Organizations
{

    /**
     * Retrieve the name for the organization identified by the provided id.
     *
     * @param int $organizationId the id of the organization whose name is to be retrieved.
     *
     * @return string Returns 'Unknown' if the organization could not be found, else the `name`
     *                value is returned.
     *
     * @throws \Exception if there is a problem retrieving a db connection
     * @throws \Exception if there is a problem executing sql
     */
    public static function getNameById($organizationId)
    {
        $query = <<<SQL
SELECT o.name, o.id
FROM modw.organization o
WHERE o.id = :organization_id
UNION
SELECT unk.name, unk.id
FROM modw.organization unk
WHERE unk.id = -1
ORDER BY id DESC
SQL;
        $params = array(
            ':organization_id' => $organizationId
        );

        $db = DB::factory('database');

        $rows = $db->query($query, $params);

        return $rows[0]['name'];
    }

    /**
     * Attempt to retrieve the organization id associated with the provided
     * $organizationName.
     *
     * @param string $organizationName the name of the organization to retrieve.
     * @return string '-1' if no record is found else the organization_id as a string.
     * @throws \Exception if there is a problem retrieving a db connection.
     * @throws \Exception if there is a problem executing the sql statement.
     */
    public static function getIdByName($organizationName)
    {
        $db = DB::factory('database');
        $rows = $db->query(
            "SELECT o.id FROM modw.organization o WHERE o.name = :organization_name;",
            array(':organization_name' => $organizationName)
        );
        return !empty($rows) ? $rows[0]['id'] : '-1';
    }

    /**
     * Retrieve an organizations `abbrev` value based on the provided `$organizationId`.
     *
     * @param integer $organizationId
     * @return string
     * @throws \Exception
     */
    public static function getAbbrevById($organizationId)
    {
        $db = DB::factory('database');
        $rows = $db->query(
            "SELECT o.abbrev FROM modw.organization o WHERE o.id = :organization_id",
            array(':organization_id' => $organizationId)
        );

        return $rows[0]['abbrev'];
    }

    /**
     * Attempt to retrieve the organization_id for the specified person_id.
     *
     * @param int $personId
     * @return string id of the organization or '-1' if not found
     * @throws \Exception
     */
    public static function getOrganizationIdForPerson($personId)
    {
        $db = DB::factory('database');
        $rows = $db->query(
            "SELECT p.organization_id FROM modw.person p WHERE p.id = :person_id",
            array(
                ':person_id' => $personId
            )
        );

        return count($rows) > 0 ? $rows[0]['organization_id'] : '-1';
    }
}
