# Name: update_user_acl_group_by_parameters.sql
# Description: This script is meant to populate `user_acl_group_by_parameters` with any records that may be missing
# based on the contents of the new `acl_dimensions` table.
# Intended Execution: On Upgrade, Once.
INSERT INTO moddb.user_acl_group_by_parameters(user_id, acl_id, group_by_id, value)
SELECT inc.*
FROM (
    SELECT u.id           AS user_id,
           a.acl_id       AS acl_id,
           gb.group_by_id AS group_by_id,
           CASE
               WHEN gb.name IN ('provider', 'organization', 'institution') THEN u.organization_id
               WHEN gb.name IN ('person', 'pi') THEN u.person_id
               END        AS value
    FROM moddb.acl_dimensions ad
        JOIN moddb.group_bys  gb ON ad.group_by_id = gb.group_by_id
        JOIN moddb.user_acls  ua ON ad.acl_id = ua.acl_id
        JOIN moddb.Users      u ON ua.user_id = u.id
        JOIN moddb.acls       a ON ad.acl_id = a.acl_id
)                                                inc
    LEFT JOIN moddb.user_acl_group_by_parameters cur
                  ON cur.user_id = inc.user_id AND
                     cur.acl_id = inc.acl_id AND
                     cur.group_by_id = inc.group_by_id AND
                     cur.value = inc.value
WHERE cur.user_acl_parameter_id IS NULL;
