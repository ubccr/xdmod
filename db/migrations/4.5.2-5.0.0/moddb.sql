INSERT INTO `schema_version_history` VALUES ('moddb', '5.0.0', NOW(), 'upgraded', 'N/A');

CREATE TABLE `VersionCheck` (
  `entry_date` datetime NOT NULL,
  `ip_address` varchar(15) NOT NULL,
  `name` varchar(255) DEFAULT NULL COMMENT 'Log of xdmod version checks',
  `email` varchar(255) DEFAULT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `current_version` varchar(16) DEFAULT NULL,
  `all_params` text,
  KEY `entry_date` (`entry_date`),
  KEY `ip` (`ip_address`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

