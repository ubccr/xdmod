<?php

namespace xd_dashboard;

// -----------------------------------------------------------

function deriveUserEnumerationQuery($group_filter = 'all', $role_filter = 'any', $exclude_unspecified_emails = false)
{
    $query = <<<SQL
SELECT DISTINCT
  u.email_address
FROM Users u
  JOIN user_acls ua
    ON ua.user_id = u.id
  JOIN acls a
    ON ua.acl_id = a.acl_id
SQL;

    $whereClauses = array();
    $params = array();

    if ($role_filter !== 'any') {
        $whereClauses[] = 'a.acl_id = :acl_filter';
        $params[':acl_filter'] = $role_filter;
    }
    if ($group_filter !== 'all') {
        $whereClauses[] = 'u.user_type = :user_type';
        $params[':user_type'] = $group_filter;
    }
    if (true === $exclude_unspecified_emails) {
        $whereClauses[] = 'u.email_address != :unspecified_email_constant';
        $params[':unspecified_email_constant'] = 'no_email_address_set';
    }

    $whereClause = implode(" AND\n", $whereClauses);

    if (count($whereClauses) > 0) {
        $queryParts = array(
            $query,
            'WHERE',
            $whereClause,
            'ORDER BY u.last_name'
        );
    } else {
        $queryParts = array(
            $query,
            'ORDER BY u.last_name'
        );
    }
    return array(
        implode(
            "\n",
            $queryParts
        ),
        $params
    );
}// deriveUserEnumerationQuery
