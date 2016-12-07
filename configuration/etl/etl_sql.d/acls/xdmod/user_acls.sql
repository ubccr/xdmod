-- =============================================================================
-- NAME:      user_acls.sql
-- EXECUTION: once on installation
-- PURPOSE:   Provides initial population of the 'user_acls' table from the
--            previous UserRoles table. This file was generated manually.
-- =============================================================================

INSERT INTO ${DESTINATION_SCHEMA}.user_acls (user_id, acl_id)
    SELECT DISTINCT
        u.id,
        a.acl_id
    FROM ${DESTINATION_SCHEMA}.UserRoles ur
        JOIN  ${DESTINATION_SCHEMA}.Roles r
            ON r.role_id = ur.role_id
        JOIN ${DESTINATION_SCHEMA}.Users u
            ON u.id = ur.user_id
        JOIN ${DESTINATION_SCHEMA}.acls a
            ON a.name = r.abbrev
        LEFT JOIN ${DESTINATION_SCHEMA}.user_acls ua
            ON ua.user_id = ur.user_id
               AND ua.acl_id = a.acl_id
    WHERE
        ua.user_acl_id IS NULL
    ORDER BY u.id;
