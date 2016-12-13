INSERT INTO `schema_version_history` VALUES ('mod_logger', '5.6.0', NOW(), 'upgraded', 'N/A');

ALTER TABLE `log_table`
    MODIFY COLUMN `ident` char(32),
    MODIFY COLUMN `priority` int,
    ADD KEY `ident_idx` (`ident`),
    ADD KEY `priority_idx` (`priority`);
