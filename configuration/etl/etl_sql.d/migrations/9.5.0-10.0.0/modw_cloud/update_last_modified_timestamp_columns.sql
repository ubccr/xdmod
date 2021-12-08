UPDATE modw_cloud.event SET last_modified_ts = UNIX_TIMESTAMP(last_modified);
UPDATE modw_cloud.session_records SET last_modified_ts = UNIX_TIMESTAMP(last_modified);

ALTER TABLE modw_cloud.cloudfact_by_day ADD COLUMN last_modified_ts INT(11) unsigned;
UPDATE modw_cloud.cloudfact_by_day SET last_modified_ts = UNIX_TIMESTAMP(last_modified);
