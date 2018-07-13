<?php

namespace Models\Services;

use CCR\DB;

class Persons
{

    /**
     * Retrieve the organization's id for the person associated with $personId.
     *
     * @param int $personId
     *
     * @return int organization id for the person identified by $personId
     *
     * @throws \Exception if there is a problem retrieving a db connection.
     */
    public static function getOrganizationIdForPerson($personId)
    {
        $query = "SELECT organization_id FROM modw.person WHERE id = :person_id";
        $params = array(
            ':person_id' => $personId
        );

        $db = DB::factory('database');

        $rows = $db->query($query, $params);

        return count($rows) > 0 ? $rows[0]['organization_id'] : UNKNOWN_ORGANIZATION_ID;
    }
}