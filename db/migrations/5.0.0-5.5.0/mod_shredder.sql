INSERT INTO `schema_version_history` VALUES ('mod_shredder', '5.5.0', NOW(), 'upgraded', 'N/A');

ALTER TABLE `shredded_job_slurm`
    MODIFY COLUMN `exit_code` varchar(32) NOT NULL,
    ADD COLUMN `job_id_raw` int unsigned DEFAULT NULL AFTER `job_array_index`,
    ADD COLUMN `uid_number` int unsigned DEFAULT NULL AFTER `user_name`,
    ADD COLUMN `gid_number` int unsigned DEFAULT NULL AFTER `group_name`,
    ADD COLUMN `state` varchar(32) DEFAULT NULL AFTER `exit_code`,
    ADD COLUMN `req_cpus` int unsigned DEFAULT NULL AFTER `ncpus`,
    ADD COLUMN `req_mem` varchar(32) DEFAULT NULL AFTER `req_cpus`,
    ADD COLUMN `timelimit` int unsigned DEFAULT NULL AFTER `req_mem`;

ALTER TABLE `shredded_job`
    ADD COLUMN `job_id_raw` int unsigned DEFAULT NULL AFTER `job_array_index`,
    ADD COLUMN `uid_number` int unsigned DEFAULT NULL AFTER `user_name`,
    ADD COLUMN `gid_number` int unsigned DEFAULT NULL AFTER `group_name`,
    ADD COLUMN `eligible_time` int unsigned DEFAULT NULL AFTER `submission_time`,
    ADD COLUMN `exit_code` varchar(32) DEFAULT NULL AFTER `wait_time`,
    ADD COLUMN `exit_state` varchar(32) DEFAULT NULL AFTER `exit_code`,
    ADD COLUMN `cpu_req` int unsigned DEFAULT NULL AFTER `cpu_count`,
    ADD COLUMN `mem_req` varchar(32) DEFAULT NULL AFTER `cpu_req`,
    ADD COLUMN `timelimit` int unsigned DEFAULT NULL AFTER `mem_req`,
    ADD COLUMN `node_list` text DEFAULT NULL AFTER `timelimit`;

ALTER TABLE `staging_job`
    ADD COLUMN `job_id_raw` int unsigned DEFAULT NULL AFTER `job_array_index`,
    ADD COLUMN `uid_number` int unsigned DEFAULT NULL AFTER `user_name`,
    ADD COLUMN `gid_number` int unsigned DEFAULT NULL AFTER `group_name`,
    ADD COLUMN `eligible_time` int unsigned DEFAULT NULL AFTER `submission_time`,
    ADD COLUMN `exit_code` varchar(32) DEFAULT NULL AFTER `wait_time`,
    ADD COLUMN `exit_state` varchar(32) DEFAULT NULL AFTER `exit_code`,
    ADD COLUMN `cpu_req` int unsigned DEFAULT NULL AFTER `cpu_count`,
    ADD COLUMN `mem_req` varchar(32) DEFAULT NULL AFTER `cpu_req`,
    ADD COLUMN `timelimit` int unsigned DEFAULT NULL AFTER `mem_req`,
    ADD COLUMN `node_list` text DEFAULT NULL AFTER `timelimit`;

