-- =============================================================================
-- NAME:      user_acl_group_by_parameters.sql
-- EXECUTION: once on installation
-- PURPOSE:   Provides initial population of the 'user_acl_group_by_parameters'
--            table. It was generated manually by taking into consideration the
--            previous role based tables / relations and the new acl based
--            tables / relations.
-- =============================================================================

INSERT INTO ${DESTINATION_SCHEMA}.user_acl_group_by_parameters (user_id, acl_id, group_by_id, value)
  SELECT inc.*
  FROM (
         SELECT
           urp.user_id,
           a.acl_id,
           gb.group_by_id,
           urp.param_value
         FROM ${DESTINATION_SCHEMA}.UserRoleParameters urp
           JOIN ${DESTINATION_SCHEMA}.Roles r
             ON r.role_id = urp.role_id
           JOIN ${DESTINATION_SCHEMA}.acls a
             ON a.name = r.abbrev
           JOIN ${DESTINATION_SCHEMA}.group_bys gb
             ON gb.name LIKE CONCAT('%', urp.param_name, '%')
           JOIN ${DESTINATION_SCHEMA}.modules m
             ON m.module_id = gb.module_id
         ORDER BY urp.user_id, urp.role_id, a.acl_id, gb.group_by_id
       ) inc
    LEFT JOIN ${DESTINATION_SCHEMA}.user_acl_group_by_parameters cur
      ON cur.user_id = inc.user_id
         AND cur.acl_id = inc.acl_id
         AND cur.group_by_id = inc.group_by_id
         AND cur.value = inc.param_value
  WHERE cur.user_acl_parameter_id IS NULL;
