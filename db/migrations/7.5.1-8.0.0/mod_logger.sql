INSERT INTO `log_level` VALUES (8,'TRACE','Trace-level message');

INSERT INTO `schema_version_history` VALUES ('mod_logger', '8.0.0', NOW(), 'upgraded', 'N/A');

ALTER TABLE `log_table` ADD KEY `logscan` (`ident`,`priority`,`id`);
