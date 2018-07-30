-- =============================================================================
-- NAME:      normalize_user_organizations.sql
-- EXECUTION: once on installation / update
-- PURPOSE:   Ensure that the moddb.Users.organization_id value is valid.
-- =============================================================================
UPDATE moddb.Users u
  LEFT JOIN modw.organization o ON o.id = u.organization_id
SET u.organization_id = -1
WHERE u.organization_id IS NULL OR o.id IS NULL;