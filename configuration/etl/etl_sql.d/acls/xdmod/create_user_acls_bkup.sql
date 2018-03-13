-- Name: create_user_acls_bkup.sql
-- Purpose: to serve as the select query that will be used to create the backup
--          table 'user_acls_bkup'.
-- Reason: We do not currently store which users are associated with which acl
--         in a configuration file. As such we need to preserve these associations
--         while the tables they rely upon are re-built.
SELECT
  ua.user_id,
  a.name AS acl_name
FROM user_acls ua
  JOIN acls a ON a.acl_id = ua.acl_id