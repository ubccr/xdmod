-- Name: repopulate_uagbp.sql
-- Purpose: Serves as the insertion query to utilize when reconstituting the
--          user_acl_group_by_parameters table. A number of steps have been
--          taken to ensure that orphan records are not introduced into the
--          system post sync. In particular we prevent orphan records from the
--          following tables:
--            - acls
--            - modules
--            - realms
--            - group_bys
-- Reason: The goal of this feature is to ensure that the acl related tables are
--         consistent with the information expressed in the configuration files as
--         well as internally consistent ( i.e. not re-inserting orphan records
--         for acls, modules, realms or group_bys that no longer exist. ).
INSERT INTO user_acl_group_by_parameters (user_id, acl_id, group_by_id, value)
  SELECT
    uagbp.user_id,
    a.acl_id,
    gb.group_by_id,
    uagbp.value
  FROM user_acl_group_by_parameters_bkup uagbp
    LEFT JOIN acls a ON a.name = uagbp.acl_name
    LEFT JOIN modules m ON m.name = uagbp.group_by_module_name
    LEFT JOIN realms r ON r.name = uagbp.group_by_realm_name
    LEFT JOIN group_bys gb
      ON gb.name = uagbp.group_by_name AND
         gb.module_id = m.module_id AND
         gb.realm_id = r.realm_id
  WHERE a.acl_id IS NOT NULL AND
        m.module_id IS NOT NULL AND
        r.realm_id IS NOT NULL AND
        gb.group_by_id IS NOT NULL;