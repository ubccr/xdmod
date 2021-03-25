DELETE FROM
  ${SOURCE_SCHEMA}.session_records
WHERE
  instance_id IN (SELECT DISTINCT instance_id FROM ${SOURCE_SCHEMA}.event_reconstructed)
//
