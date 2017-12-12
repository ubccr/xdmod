-- =============================================================================
-- NAME:      user_acl_group_by_parameters_sync.sql
-- EXECUTION: each time a new module is installed
-- PURPOSE:   Provides 'back filling' of the user_acl_group_by_parameters table
--            by selecting a distinct cross product of all current
--            user_acl_group_by_parameter records and curent group_by records
--            named 'provider' ( note, module / realm independent ) and then
--            left joins that against the current user_acl_group_by_parameters
--            and only selecting those records that do not already exist.
-- =============================================================================

INSERT INTO ${DESTINATION_SCHEMA}.user_acl_group_by_parameters(user_id, acl_id, group_by_id, value)
  SELECT inc.*
  FROM (
         SELECT DISTINCT
           user_id,
           acl_id,
           gb.group_by_id,
           uagbp.value
         FROM ${DESTINATION_SCHEMA}.user_acl_group_by_parameters uagbp,
           ${DESTINATION_SCHEMA}.group_bys gb
         WHERE gb.name = 'provider'
       ) inc
    LEFT JOIN ${DESTINATION_SCHEMA}.user_acl_group_by_parameters cur
      ON
        cur.user_id = inc.user_id AND
        cur.acl_id = inc.acl_id AND
        cur.group_by_id = inc.group_by_id
  WHERE cur.user_acl_parameter_id IS NULL;