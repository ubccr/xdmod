DELETE FROM
  ${SOURCE_SCHEMA}.cloudfact_by_day_sessionlist
WHERE
  session_id IN (SELECT DISTINCT session_id FROM ${SOURCE_SCHEMA}.session_records WHERE instance_id IN (SELECT DISTINCT instance_id FROM ${SOURCE_SCHEMA}.event_reconstructed))
//
DELETE FROM
  ${SOURCE_SCHEMA}.session_records
WHERE
  instance_id IN (SELECT DISTINCT instance_id FROM ${SOURCE_SCHEMA}.event_reconstructed)
//
