ALTER TABLE `test_db_model`
CHARSET = utf8mb4,
COLLATE = utf8mb4_general_ci,
ADD COLUMN `new_column` boolean NOT NULL DEFAULT 0 AFTER `inferred`,
ADD COLUMN `new_column2` char(64) CHARSET utf8mb4 COLLATE utf8mb4_general_ci NULL AFTER `new_column`,
ADD INDEX `index_new_column` (`new_column`),
DROP INDEX `fk_instance`,
ADD INDEX `fk_instance` USING BTREE (`instance_id`, `inferred`),
ADD CONSTRAINT `fk_new_column` FOREIGN KEY (`new_column`) REFERENCES `other_table` (`other_column`),
DROP FOREIGN KEY `con_col1`,
ADD CONSTRAINT `con_col1` FOREIGN KEY (`col1`) REFERENCES `db_test_model2` (`col4`);
ALTER TABLE `test_db_model`
CHANGE COLUMN `col1` `col1` varchar(32) CHARSET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'mydefault' ,
CHANGE COLUMN `instance_id` `instance_id` int(11) NULL DEFAULT -1 ;
CREATE TRIGGER `before_ins` BEFORE INSERT ON `jobfact` FOR EACH ROW
 BEGIN DELETE FROM jobfactstatus WHERE job_id = NEW.job_id; END
