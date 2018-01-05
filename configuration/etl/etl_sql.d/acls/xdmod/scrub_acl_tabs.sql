-- =============================================================================
-- NAME:      scrub_acl_tabs.sql
-- EXECUTION: once on installation
-- PURPOSE:   Ensure that the records in the acl_tabs table is accurate. We do
--            not want 'feature' acls
-- =============================================================================

DELETE FROM acl_tabs WHERE acl_id IN (
  SELECT a.acl_id
  FROM acls a
    JOIN acl_types at ON a.acl_type_id = at.acl_type_id
  WHERE at.name = 'feature'
);