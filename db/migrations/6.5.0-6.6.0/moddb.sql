ALTER TABLE `Users` DROP COLUMN `parent_user_id`;

INSERT INTO `schema_version_history` VALUES ('moddb', '6.6.0', NOW(), 'upgraded', 'N/A');
