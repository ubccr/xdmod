-- =============================================================================
-- NAME:      update_module_version.sql
-- EXECUTION: once on installation
-- PURPOSE:   Update the module_version record with the newly created
--            'module_id' for the record w/ a 'name' == 'xdmod';
-- =============================================================================

UPDATE ${DESTINATION_SCHEMA}.module_versions mv
  JOIN (
    SELECT MAX(mmv.module_version_id) AS max_version_id
    FROM ${DESTINATION_SCHEMA}.module_versions mmv
  ) mmvi ON mv.module_version_id = mmvi.max_version_id
SET module_id = (
  SELECT m.module_id
  FROM ${DESTINATION_SCHEMA}.modules m
  WHERE m.name = 'xdmod'
),  created_on = NOW()
 ,  last_modified_on = NOW();

