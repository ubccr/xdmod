-- =============================================================================
-- NAME:      update_user_organizations.sql
-- EXECUTION: once on installation / update
-- PURPOSE:   Ensure that the moddb.Users.organization_id is up to date.
-- =============================================================================
UPDATE moddb.Users u
  JOIN modw.person p ON p.id = u.person_id
SET u.organization_id = p.organization_id
WHERE u.organization_id = -1;
