use modw;
INSERT INTO `schema_version_history` VALUES ('modw', '8.0.0', NOW(), 'upgraded', 'N/A');

ALTER TABLE `modw`.`account`
ADD COLUMN `origin_id` int(10) unsigned NOT NULL DEFAULT 0,
ADD COLUMN `federation_blade_id` int(11) unsigned NOT NULL DEFAULT 0;
UPDATE `modw`.`account` set origin_id = id;

ALTER TABLE `modw`.`allocation`
ADD COLUMN `origin_id` int(10) unsigned NOT NULL;
UPDATE `modw`.`allocation` set origin_id = id;

ALTER TABLE `modw`.`person`
ADD COLUMN `origin_id` int(11) NOT NULL DEFAULT 0,
CHANGE COLUMN `id` `id` int(11) NOT NULL auto_increment;
UPDATE `modw`.`person` SET origin_id = id;
