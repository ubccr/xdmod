-- Name: create_uagbp_bkup.sql
-- Purpose: to serve as the select query that will be used to create the backup
--          table 'user_acl_group_by_parameters_bkup'.
-- Reason: We do not currently store this information in a configuration file and
--         as such we need to preserve these associations while the tables they
--         rely upon are re-built.
SELECT
  uagbp.user_id,
  a.name  AS acl_name,
  m.name  AS group_by_module_name,
  r.name  AS group_by_realm_name,
  gb.name AS group_by_name,
  uagbp.value
FROM user_acl_group_by_parameters uagbp
  JOIN acls a ON a.acl_id = uagbp.acl_id
  JOIN group_bys gb ON gb.group_by_id = uagbp.group_by_id
  JOIN realms r ON r.realm_id = gb.realm_id
  JOIN modules m ON m.module_id = gb.module_id;