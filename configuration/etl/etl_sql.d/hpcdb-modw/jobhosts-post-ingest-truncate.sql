--
-- Truncate the staging table
-- This is done after the ingest has already happened
-- to clean up after the ingest has completed.
--
TRUNCATE ${DESTINATION_SCHEMA}.staging_jobhosts
//
