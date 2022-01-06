UPDATE
	mod_shredder.staging_storage_usage
SET
    dt = CONCAT(DATE(dt), 'T', TIME_FORMAT(dt, "%T"), 'Z');
