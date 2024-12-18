ALTER TABLE `test_db_model`
CHARSET = utf8,
COLLATE = utf8_unicode_ci,
DROP INDEX `fk_instance`,
ADD INDEX `fk_instance` USING BTREE (`instance_id`, `inferred`);
ALTER TABLE `test_db_model`
CHANGE COLUMN `col1` `col1` varchar(32) CHARSET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'mydefault' ,
CHANGE COLUMN `instance_id` `instance_id` int(11) NULL DEFAULT -1 ;
ALTER TABLE `test_db_model`
DROP FOREIGN KEY `con_col1`;
ALTER TABLE `test_db_model`
ADD CONSTRAINT `con_col1` FOREIGN KEY (`col1`) REFERENCES `db_test_model2` (`col4`);
