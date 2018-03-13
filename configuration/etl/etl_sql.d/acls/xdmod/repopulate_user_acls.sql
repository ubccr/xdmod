-- Name: repopulate_user_acls.sql
-- Purpose: Serves as the insertion query to utilize when reconstituting the
--          user_acls table. Notice the 'LEFT JOIN' on 'acls' and accompanying
--          where clause 'a.acl_id IS NOT NULL'. These are to ensure that if an
--          acl was removed as part of the sync those orphan records do not make
--          their way back into the system.
-- Reason: The goal of this feature is to ensure that the acl related tables are
--         consistent with the information expressed in the configuration files as
--         well as internally consistent ( i.e. not re-inserting orphan records
--         for acls that no longer exist. ).
INSERT INTO user_acls (user_id, acl_id)
  SELECT
    ua.user_id,
    a.acl_id
  FROM user_acls_bkup ua
    LEFT JOIN acls a ON a.name = ua.acl_name
  WHERE a.acl_id IS NOT NULL;