UPDATE modw.job_tasks SET last_modified_ts = UNIX_TIMESTAMP(last_modified);
UPDATE modw.storagefact SET last_modified_ts = UNIX_TIMESTAMP(last_modified);

ALTER TABLE modw_aggregates.jobfact_by_day ADD COLUMN last_modified_ts INT(11) unsigned;
UPDATE modw_aggregates.jobfact_by_day SET last_modified_ts = UNIX_TIMESTAMP(last_modified);
