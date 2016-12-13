INSERT INTO `schema_version_history` VALUES ('mod_shredder', '5.6.0', NOW(), 'upgraded', 'N/A');

ALTER TABLE `shredded_job_pbs`
    MODIFY `resource_list_ncpus` int(10) unsigned DEFAULT NULL,
    ADD COLUMN `node_list` text NOT NULL AFTER `resource_list_pmem`;
