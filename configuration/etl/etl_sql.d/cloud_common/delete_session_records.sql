DELETE sr FROM
  ${SOURCE_SCHEMA}.session_records AS sr
JOIN
  (SELECT DISTINCT instance_id FROM ${SOURCE_SCHEMA}.event_reconstructed) AS ev ON ev.instance_id = sr.instance_id;
//
