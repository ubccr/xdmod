CREATE TABLE IF NOT EXISTS `schema_version_history` (
    `database_name` char(64) NOT NULL,
    `schema_version` char(64) NOT NULL,
    `action_datetime` datetime NOT NULL,
    `action_type` enum('created','upgraded') NOT NULL,
    `script_name` varchar(255) NOT NULL,
    PRIMARY KEY (`database_name`,`schema_version`,`action_datetime`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `schema_version_history` VALUES ('moddb', '4.5.0', NOW(), 'upgraded', 'N/A');

