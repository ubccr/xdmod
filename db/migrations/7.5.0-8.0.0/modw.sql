use modw;
INSERT INTO `schema_version_history` VALUES ('modw', '8.0.0', NOW(), 'upgraded', 'N/A');

ALTER TABLE `modw`.`account`
ADD COLUMN `account_origin_id` int(10) unsigned NOT NULL DEFAULT 0,
ADD COLUMN `federation_instance_id` int(11) unsigned NOT NULL DEFAULT 0;
UPDATE `modw`.`account` SET account_origin_id = id;

ALTER TABLE `modw`.`allocation`
ADD COLUMN `allocation_origin_id` int(10) unsigned NOT NULL;
UPDATE `modw`.`allocation` SET  allocation_origin_id = id;

ALTER TABLE `modw`.`person`
ADD COLUMN `person_origin_id` int(11) NOT NULL DEFAULT 0,
CHANGE COLUMN `id` `id` int(11) NOT NULL auto_increment;
UPDATE `modw`.`person` SET person_origin_id = id;
