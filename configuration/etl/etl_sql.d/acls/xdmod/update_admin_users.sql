-- =============================================================================
-- NAME:      update_admin_users.sql
-- EXECUTION: once on installation
-- PURPOSE:   Ensure that any users that only have 'mgr' have 'usr' added.
-- =============================================================================

INSERT INTO user_acls (user_id, acl_id)
  SELECT inc.*
  FROM (
         SELECT
           u.id AS user_id,
           a.acl_id
         FROM Users u, acls a
         WHERE u.id IN (
           SELECT ua.user_id
           FROM user_acls ua
             JOIN acls a ON ua.acl_id = a.acl_id
           WHERE a.name = 'mgr'
         ) AND u.id NOT IN (
           SELECT DISTINCT ua.user_id
           FROM user_acls ua
             JOIN acls a ON ua.acl_id = a.acl_id
           WHERE a.name != 'mgr'
         ) AND
               a.name = 'usr'
       ) inc
    LEFT JOIN user_acls cur
      ON
        cur.user_id = inc.user_id AND
        cur.acl_id = inc.user_id
  WHERE cur.user_acl_id IS NULL;

-- =============================================================================
-- PURPOSE: Ensure that any User records that do not have a valid `person_id`
-- reference to modw.person have their `person_id` updated to -1 ( unknown ).
-- =============================================================================
UPDATE Users u LEFT JOIN modw.person p ON p.id = u.person_id
SET u.person_id = -1
WHERE
  p.id IS NULL;
