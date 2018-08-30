-- HPCDB email address ingestion was broken in releases before 8.0.0.  This has
-- been fixed, but any existing data must be removed before the new data is
-- ingested.
TRUNCATE ${DESTINATION_SCHEMA}.hpcdb_email_addresses//
