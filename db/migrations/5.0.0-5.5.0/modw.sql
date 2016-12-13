INSERT INTO `schema_version_history` VALUES ('modw', '5.5.0', NOW(), 'upgraded', 'N/A');

CREATE TABLE IF NOT EXISTS `hosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_id` int(11) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`resource_id`,`hostname`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `jobhosts` (
  `job_id` int(11) NOT NULL,
  `host_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  PRIMARY KEY (`job_id`,`host_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `jobfact`
    ADD COLUMN `local_job_id_raw` int DEFAULT NULL COMMENT 'The local raw id of the job.' AFTER `local_job_array_index`,
    ADD COLUMN `eligible_time` datetime DEFAULT NULL COMMENT 'The time the job became eligible to run.' AFTER `submit_time`,
    ADD COLUMN `eligible_time_ts` int DEFAULT NULL COMMENT 'The eligible time in Unix time.' AFTER `submit_time_ts`,
    ADD COLUMN `group_name` varchar(255) DEFAULT NULL COMMENT 'The name of the group that ran the job.',
    ADD COLUMN `gid_number` int unsigned DEFAULT NULL COMMENT 'The GID of the group that ran the job.',
    ADD COLUMN `uid_number` int unsigned DEFAULT NULL COMMENT 'The UID of the user that ran the job.',
    ADD COLUMN `exit_code` varchar(32) DEFAULT NULL COMMENT 'The code that the job exited with.',
    ADD COLUMN `exit_state` varchar(32) DEFAULT NULL COMMENT 'The state of the job when it completed.',
    ADD COLUMN `cpu_req` int unsigned DEFAULT NULL COMMENT 'The number of CPUs required by the job.',
    ADD COLUMN `mem_req` varchar(32) DEFAULT NULL COMMENT 'The amount of memory required by the job.',
    ADD COLUMN `timelimit` int unsigned DEFAULT NULL COMMENT 'The time limit of the job in seconds.',
    DROP INDEX `index_taccstats_lookup`,
    ADD INDEX `index_supremm_lookup` (`local_job_id_raw`,`resource_id`);

