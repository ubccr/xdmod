ALTER TABLE `Users` CHANGE COLUMN `password` `password` VARCHAR(255) NULL DEFAULT NULL;

INSERT INTO `schema_version_history` VALUES ('moddb', '8.0.0', NOW(), 'upgraded', 'N/A');

-- Change wording of Federated login to Single Sign On
USE moddb;
UPDATE
    moddb.UserTypes
SET type = 'Single Sign On'
WHERE type = 'Federated'
;
