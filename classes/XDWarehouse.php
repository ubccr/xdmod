<?php

use CCR\DB;

/**
 * XDMoD wrapper for accessing data from the data warehouse.
 *
 * @author Ryan Gentner
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */
class XDWarehouse
{
    private $_pdo = null;

    public function __construct()
    {
        $this->_pdo = DB::factory('datawarehouse');
    }

    /**
     * Returns the count of persons in the database.
     *
     * @return int
     */
    public function totalGridUsers()
    {
        $recordCountQuery = $this->_pdo->query(
            "SELECT COUNT(*) AS total_records FROM person"
        );

        return $recordCountQuery[0]['total_records'];
    }

    /**
     * Search for users by formal name or username.
     *
     * @param int $searchMode Constant FORMAL_NAME_SEARCH or
     *     USERNAME_SEARCH.
     * @param int $start Query offset.
     * @param int $limit Query limit.
     * @param string $nameFilter Search string.
     * @param bool $piFilter Only search for PI persons if true.
     * @param int $university_id
     *
     * @return array Contains two elements, first is a count of the
     *     total number of users matching the search criteria, second is
     *     an array of arrays for the matching users limited by $start
     *     and $limit.
     */
    public function enumerateGridUsers(
        $searchMode,
        $start,
        $limit,
        $nameFilter = null,
        $piFilter = false,
        $university_id = null
    ) {
        if (
            $searchMode != FORMAL_NAME_SEARCH
            && $searchMode != USERNAME_SEARCH
        ) {
            throw new \Exception('Invalid search mode specified');
        }

        if (!isset($start) || !isset($limit)) {
            return array(0, array());
        }

        // Filter Logic

        $filterElements = array();

        if ($piFilter == true)   {

            // Filter to account for principal investigators only
            $filterElements[] = '
                p.id IN (
                    SELECT DISTINCT(person_id) FROM principalinvestigator
                )
            ';
        }

        if ($nameFilter != null) {
            if ($searchMode == FORMAL_NAME_SEARCH) {
                $name = $this->_pdo->handle()->quote("%$nameFilter%");

                $filterElements[]
                    = "CONCAT(p.last_name, ', ', p.first_name) LIKE $name";
            }

            if ($searchMode == USERNAME_SEARCH) {
                $name = $this->_pdo->handle()->quote("$nameFilter%");

                $filterElements[]
                    = "CONCAT(s.username, '@', r.name) LIKE $name";
            }
        }

        if ($university_id != null) {
            $id = $this->_pdo->handle()->quote($university_id);
            $filterElements[] = "p.organization_id = $id";
        }

        if ($searchMode == FORMAL_NAME_SEARCH) {
            $filterConcatClause = 'WHERE';
        }

        if ($searchMode == USERNAME_SEARCH) {
            $filterConcatClause = 'AND';
        }

        $filter
            = (count($filterElements) > 0)
            ? $filterConcatClause . ' ' . implode(' AND ', $filterElements)
            : '';

        switch ($searchMode) {
            case FORMAL_NAME_SEARCH:

                // For pagination, a total record count is needed ...
                $recordCountQuery = $this->_pdo->query(
                    "SELECT COUNT(*) AS total_records FROM person AS p $filter"
                );

                $usersQuery = $this->_pdo->query(
                    "
                        SELECT p.id, p.first_name, p.last_name
                        FROM person AS p
                        $filter
                        ORDER BY p.last_name ASC, p.first_name ASC
                        LIMIT $limit OFFSET $start
                    "
                );

                break;

            case USERNAME_SEARCH:

                // For pagination, a total record count is needed ...
                $recordCountQuery = $this->_pdo->query(
                    "
                        SELECT COUNT(*) AS total_records
                        FROM
                            systemaccount AS s,
                            resourcefact AS r,
                            person AS p
                        WHERE s.person_id = p.id
                            AND r.id = s.resource_id
                            $filter
                    "
                );

                $usersQuery = $this->_pdo->query(
                    "
                        SELECT
                            CONCAT(
                                s.username,
                                '@',
                                r.name,
                                ' (',
                                p.last_name,
                                ', ',
                                p.first_name,
                                ')'
                            ) AS absusername,
                            s.person_id AS id
                        FROM
                            systemaccount AS s,
                            resourcefact AS r,
                            person AS p
                        WHERE s.person_id = p.id
                            AND r.id = s.resource_id
                            $filter
                        ORDER BY absusername ASC, s.person_id
                        LIMIT $limit OFFSET $start
                    "
                );

                break;

            default:
                throw new \Exception('Invalid search mode specified');
                break;
        }

        return array($recordCountQuery[0]['total_records'], $usersQuery);
    }

    /**
     * Returns the name for the specified organization.
     *
     * @param int $institution_id An organiztion id.
     *
     * @return string
     */
    public function resolveInstitutionName($institution_id)
    {
        $instQuery = $this->_pdo->query(
            "SELECT name FROM organization WHERE id = :id",
            array('id' => $institution_id)

        );

        if (count($instQuery) == 0){ return NO_MAPPING; }

        return $instQuery[0]['name'];
    }

    /**
     * Find the formal name for the specified person.
     *
     * @param int $person_id
     *
     * @return string The person's formal name.
     */
    public function resolveName($person_id)
    {
        $nameQuery = $this->_pdo->query(
            "
                SELECT CONCAT(last_name, ', ', first_name) AS formal_name
                FROM person
                WHERE id = :id
            ",
            array('id' => $person_id)
        );

        if (count($nameQuery) == 0){ return NO_MAPPING; }

        return $nameQuery[0]['formal_name'];
    }

    /**
     * @param XDUser $user
     *
     * @return int A bucket id used to reference the 'page' in which the
     *     user is stored.
     */
    public function fetchMappedUserBucket($user)
    {
        $name = $this->_pdo->handle()->quote(
            $user->getLastName() . "," . $user->getFirstName()
        );

        $bucketQuery = $this->_pdo->query(
            "
                SELECT offset
                FROM person_lut
                WHERE
                    $name >= CONCAT(
                        starting_last_name,
                        ',',
                        starting_first_name
                    )
                    AND CONCAT(
                        ending_last_name,
                        ',',
                        ending_first_name
                    ) >= $name
            "
        );

        return $bucketQuery[0]['offset'];
    }

    /**
     * @return array A textual listing of all fields of science
     */
    public function enumerateFieldsOfScience()
    {
        $fos_entries = $this->_pdo->query(
            "SELECT id, description FROM fieldofscience ORDER BY description"
        );

        $fields = array();

        foreach ($fos_entries as $fos) {
            $fields[] = array(
                'field_id'    => $fos['id'],
                'field_label' => $fos['description'],
            );
        }

        return $fields;
    }

    /**
     * Search for users in the data warehouse.
     *
     * The first and last name are given priority in the search.  They
     * are required and their values must be at least a substring of the
     * values in the database.  An email address and organization may
     * also be also be provided to disambiguate users with the same
     * name.  If an exact match is found it will be the only result
     * returned as a single element in the results and marked as such,
     * e.g.:
     *     array(
     *         array(
     *             "exact_match" => true,
     *             "person_id"   => ...
     *         )
     *     )
     *
     * A match is considered exact if and only if all possible criteria
     * are used in the search and all match.
     *
     * @param array $searchCrit Array containing search criteria, valid
     *     keys are "first_name", "last_name", "email_address" and
     *     "organization".
     *
     * @return array Array of arrays containing the search results. The
     *     inner arrays have keys "person_id", "person_name",
     *     "first_name", "last_name", "email_address",
     *     "organization_id", "organization" and "exact_match".
     */
    public function searchUsers(array $searchCrit)
    {

        // key => SQL expression
        $priorityCriteria = array(
            'last_name'  => 'p.last_name',
            'first_name' => 'p.first_name',
        );

        // These are not used in the SQL query.  They are only used to
        // check if the user is an exact match.
        $secondaryCriteria = array(
            'email_address',
            'organization',
        );

        // All possible keys that may be in the search criteria.
        $criteriaKeys = array_merge(
            array_keys($priorityCriteria),
            $secondaryCriteria
        );

        $clauses  = array();
        $bindVals = array();

        foreach ($priorityCriteria as $key => $sqlExpr) {
            if (isset($searchCrit[$key])) {
                $clauses[]      = "$sqlExpr LIKE :$key";
                $bindVals[$key] = '%' . $searchCrit[$key] . '%';
            }
        }

        if (count($clauses) !== 2) {
            throw new \Exception('First and last name are required');
        }

        $sql = "
            SELECT
                p.id AS person_id,
                CONCAT(p.last_name, ', ', p.first_name) AS person_name,
                p.first_name,
                p.last_name,
                p.email_address,
                o.id AS organization_id,
                o.name AS organization
            FROM person p
            LEFT JOIN organization o ON p.organization_id = o.id
            WHERE "
            . implode(' AND ', $clauses);

        $results = $this->_pdo->query($sql, $bindVals);

        $users = array();

        foreach ($results as $user) {
            $exactMatch = true;

            foreach ($criteriaKeys as $key) {
                if (isset($searchCrit[$key])) {
                    if ($user[$key] != $searchCrit[$key]) {
                        $exactMatch = false;
                    }
                } else {
                    $exactMatch = false;
                }
            }

            $user['exact_match'] = $exactMatch;

            // If an exact match is found, short-circuit the loop and
            // return only a single user.
            if ($exactMatch) {
                return array($user);
            }

            $users[] = $user;
        }

        return $users;
    }
}

