ALTER TABLE `shredded_job_lsf`
    ADD COLUMN `exit_status` int(10) NOT NULL AFTER `num_ex_hosts`,
    ADD COLUMN `exit_info` int(10) NOT NULL AFTER `exit_status`,
    ADD COLUMN `node_list` text NOT NULL AFTER `exit_info`;

ALTER TABLE `shredded_job_slurm` MODIFY COLUMN `eligible_time` int(10) unsigned DEFAULT NULL;

INSERT INTO `schema_version_history` VALUES ('mod_shredder', '6.0.0', NOW(), 'upgraded', 'N/A');
