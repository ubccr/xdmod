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
}
