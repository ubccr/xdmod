<?php

namespace TestHarness;

use Exception;
use CCR\DB;

/**
 * Helper class for people related tests.
 */
class PeopleHelper
{
    /**
     * modw database handle.
     *
     * @var CCR\DB\MySQLDB
     */
    private $dbh;

    public function __construct()
    {
        $this->dbh = DB::factory('datawarehouse');
    }

    /**
     * Find the ID for a person given their long name.
     *
     * @return int An id from the modw person table.
     * @throws Exception If none or more than one person is found.
     */
    public function getPersonIdByLongName($longName)
    {
        $sql = 'SELECT id FROM person WHERE long_name = :name';
        $people = $this->dbh->query($sql, array('name' => $longName));
        if (count($people) !== 1) {
            throw new Exception(
                sprintf('Found %d people, expected 1', count($people))
            );
        }
        return $people[0]['id'];
    }

    /**
     * Manually create a person with the attributes provided.
     *
     * @param int $organizationId
     * @param int $nsfStatusCodeId
     * @param string $firstName
     * @param string $lastName
     * @param string $longName
     * @param string $shortName
     */
    public function createPerson($organizationId, $nsfStatusCodeId, $firstName, $lastName, $longName, $shortName)
    {
        $query = <<<SQL
INSERT INTO modw.person(organization_id, nsfstatuscode_id, first_name, last_name, long_name, short_name, person_origin_id)
SELECT
:organization_id as organization_id,
:nsfstatuscode_id as nsfstatuscode_id,
:first_name as first_name,
:last_name as last_name,
:long_name as long_name,
:short_name as short_name,
MAX(person_origin_id) + 1 as person_origin_id
FROM modw.person
SQL;
        $params = array(
            ':organization_id' => $organizationId,
            ':nsfstatuscode_id' => $nsfStatusCodeId,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':long_name' => $longName,
            ':short_name' => $shortName
        );

        $this->dbh->execute($query, $params);
    }

    /**
     * Remove the person identified by the provided `$longName`.
     *
     * @param string $longName
     */
    public function removePerson($longName)
    {
        $query = <<<SQL
DELETE FROM modw.person WHERE long_name = :long_name;
SQL;
        $params = array(
            ':long_name' => $longName
        );
        $this->dbh->execute($query, $params);
    }
}
