<?php

namespace TestHarness;

use CCR\DB;

/**
 * Class OrganizationHelper
 *
 * A Test Helper class that provides convenience methods related to Organizations
 * ( from the table `modw.organization` ).
 *
 * @package TestHarness
 */
class OrganizationHelper
{

    /**
     * @var \CCR\DB\iDatabase
     */
    private $db;

    /**
     * OrganizationHelper constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->db = DB::factory('database');
    }

    /**
     * Retrieve the `id` for the organization identified by `$longName`.
     *
     * @param string $longName the `long_name`
     * @return int|null `id` of the associated organization if found, else null is
     *                  returned.
     */
    public function getIdByLongName($longName)
    {
        $query = "SELECT id FROM modw.organization WHERE long_name = :long_name";
        $params = array(
            ':long_name'=> $longName
        );

        $rows = $this->db->query($query, $params);

        return count($rows) > 0 ? $rows[0]['id'] : -1;
    }
}
