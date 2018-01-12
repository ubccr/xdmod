<?php

use CCR\DB;

/**
 * XDMoD User administration functions.
 */
class XDAdmin
{

    /**
     * moddb database.
     *
     * @var \CCR\DB\iDatabase
     */
    private $moddb = null;

    /**
     * modw database.
     *
     * @var \CCR\DB\iDatabase
     */
    private $modw = null;

    /**
     * Default constructor.
     */
    function __construct()
    {
        $this->moddb = DB::factory('database');
        $this->modw  = DB::factory('datawarehouse');
    }

    /**
     * Get a list of users.
     *
     * @param int $groupFilter Optional user type.
     *
     * @return array
     */
    public function getUserListing($groupFilter = 0)
    {
        $filterSQL = '';

        if ($groupFilter != 0) {
            $filterSQL = "WHERE u.user_type = " . (int)$groupFilter;
        }

        $query = "
            SELECT
                u.id,
                u.username,
                u.first_name,
                u.last_name,
                u.account_is_active,
                CASE
                    WHEN (
                        SELECT init_time
                        FROM SessionManager
                        WHERE user_id = u.id
                        ORDER BY init_time DESC
                        LIMIT 1
                    ) IS NULL
                        THEN '0'
                    ELSE (
                        SELECT init_time
                        FROM SessionManager
                        WHERE user_id = u.id
                        ORDER BY init_time DESC
                        LIMIT 1
                    )
                END AS last_logged_in
            FROM Users u
            $filterSQL
            ORDER BY last_logged_in DESC
        ";

        return $this->moddb->query($query);
    }

    /**
     * Update account request status.
     *
     * @param int $id
     * @param string $creator
     */
    public function updateAccountRequestStatus($id = -1, $creator = '')
    {
        $create_message = 'Created on ' . date('Y-m-d \a\t h:i A');

        if (!empty($creator)) {
            $create_message .= " by $creator";
        }

        $this->moddb->execute(
            "UPDATE AccountRequests SET status = :status WHERE id = :id",
            array(
                'id'     => $id,
                'status' => $create_message,
            )
        );
    }

    /**
     * Get a list of all acls.
     *
     * @return array
     */
    public function enumerateAcls()
    {
        $sql = <<<SQL
SELECT
a.*,
  CASE WHEN a.name IN ('cd', 'cs') THEN 1 ELSE 0 END requires_center
FROM acls a
ORDER BY a.display;
SQL;

        return $this->moddb->query($sql);
    }

    /**
     * Get a list of exception email addresses.
     *
     * @return array
     */
    public function enumerateExceptionEmailAddresses()
    {

        $emailAddressResults = $this->moddb->query("
            SELECT email_address
            FROM ExceptionEmailAddresses
            ORDER BY email_address
        ");

        $results = array();

        foreach($emailAddressResults as $address) {
            $results[] = $address['email_address'];
        }

        return $results;
    }

    /**
     * Get a listing of resource providers that ever ran jobs, along
     * with their id.
     *
     * @return array
     */
    public function enumerateResourceProviders()
    {
        return $this->modw->query("
            SELECT DISTINCT
                organization_id AS id,
                o.abbrev        AS organization,
                o.name          AS name
            FROM
                modw_aggregates.jobfact_by_quarter,
                organization o
            WHERE o.id = organization_id
            ORDER BY o.abbrev ASC
        ");
    }

    /**
     * Get a listing of institutions along with their id.
     *
     * @param int $start
     * @param int $limit
     * @param int $nameFilter
     *
     * @return array
     */
    public function enumerateInstitutions($start, $limit, $nameFilter = NULL)
    {
        $start = (int)$start;
        $limit = (int)$limit;

        $filter
            = !empty($nameFilter)
            ? "WHERE name LIKE " . $this->modw->quote("%$nameFilter%")
            : '';

        $institutionCountQuery = $this->modw->query(
            "SELECT COUNT(*) AS total_records FROM organization $filter"
        );

        $institutionResults = $this->modw->query("
            SELECT id, name
            FROM organization
            $filter
            ORDER BY name ASC
            LIMIT $limit OFFSET $start
        ");

        return array(
            $institutionCountQuery[0]['total_records'],
            $institutionResults
        );
    }

    /**
     * Get a listing of user types.
     *
     * @return array
     */
    public function enumerateUserTypes()
    {
        return $this->moddb->query(
            "SELECT id, type FROM UserTypes ORDER BY type ASC"
        );
    }
}
