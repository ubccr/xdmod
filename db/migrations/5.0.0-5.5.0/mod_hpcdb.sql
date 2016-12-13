INSERT INTO `schema_version_history` VALUES ('mod_hpcdb', '5.5.0', NOW(), 'upgraded', 'N/A');

ALTER TABLE `hpcdb_jobs`
    ADD COLUMN `local_job_id_raw` int DEFAULT NULL AFTER `local_job_array_index`,
    ADD COLUMN `uid_number` int unsigned DEFAULT NULL AFTER `username`,
    ADD COLUMN `groupname` varchar(255) DEFAULT NULL AFTER `uid_number`,
    ADD COLUMN `gid_number` int unsigned DEFAULT NULL AFTER `groupname`,
    ADD COLUMN `eligible_time` int unsigned DEFAULT NULL AFTER `submit_time`,
    ADD COLUMN `exit_code` varchar(32) DEFAULT NULL AFTER `wallduration`,
    ADD COLUMN `exit_state` varchar(32) DEFAULT NULL AFTER `exit_code`,
    ADD COLUMN `cpu_req` int unsigned DEFAULT NULL AFTER `processors`,
    ADD COLUMN `mem_req` varchar(32) DEFAULT NULL AFTER `cpu_req`,
    ADD COLUMN `timelimit` int unsigned DEFAULT NULL AFTER `mem_req`,
    ADD COLUMN `node_list` text DEFAULT NULL AFTER `timelimit`;

ALTER TABLE `hpcdb_accounts` ADD COLUMN `account_name` varchar(255) DEFAULT NULL;

