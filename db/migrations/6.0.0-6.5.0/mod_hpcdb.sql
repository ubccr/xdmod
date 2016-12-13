ALTER TABLE `hpcdb_jobs` MODIFY COLUMN `node_list` mediumtext COLLATE utf8_unicode_ci;

INSERT INTO `schema_version_history` VALUES ('mod_hpcdb', '6.5.0', NOW(), 'upgraded', 'N/A');
