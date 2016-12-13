INSERT INTO `schema_version_history` VALUES ('mod_hpcdb', '5.0.0', NOW(), 'upgraded', 'N/A');

ALTER TABLE `hpcdb_resources`
    ADD COLUMN `resource_shared_jobs` int(1) NOT NULL DEFAULT '0' AFTER `resource_description`,
    ADD COLUMN `resource_timezone` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UTC' AFTER `resource_shared_jobs`;

CREATE TABLE IF NOT EXISTS `hpcdb_resource_allocated` (
  `resource_id` int(11) NOT NULL,
  `start_date_ts` int(11) NOT NULL,
  `end_date_ts` int(11) DEFAULT NULL,
  `percent` int(11) NOT NULL DEFAULT '100'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

