ALTER TABLE `shredded_job` MODIFY COLUMN `node_list` mediumtext;
ALTER TABLE `shredded_job_lsf` MODIFY COLUMN `node_list` mediumtext NOT NULL;
ALTER TABLE `shredded_job_pbs` MODIFY COLUMN `node_list` mediumtext NOT NULL;
ALTER TABLE `shredded_job_slurm` MODIFY COLUMN `node_list` mediumtext NOT NULL;
ALTER TABLE `staging_job` MODIFY COLUMN `node_list` mediumtext;

ALTER TABLE `shredded_job_slurm`
    ADD COLUMN `req_gres` text NOT NULL DEFAULT '' AFTER `req_mem`,
    ADD COLUMN `req_tres` text NOT NULL DEFAULT '' AFTER `req_gres`;

INSERT INTO `schema_version_history` VALUES ('mod_shredder', '6.5.0', NOW(), 'upgraded', 'N/A');
