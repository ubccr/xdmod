INSERT INTO `schema_version_history` VALUES ('mod_shredder', '5.0.0', NOW(), 'upgraded', 'N/A');

ALTER TABLE `shredded_job_pbs`
    DROP KEY `job`,
    ADD UNIQUE KEY `job` (`host`,`job_id`,`job_array_index`,`ctime`,`end`);

ALTER TABLE `shredded_job_lsf`
    DROP KEY `job`,
    ADD UNIQUE KEY `job` (`resource_name`(20),`job_id`,`idx`,`submit_time`,`event_time`);

