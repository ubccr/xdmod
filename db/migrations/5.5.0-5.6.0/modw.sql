INSERT INTO `schema_version_history` VALUES ('modw', '5.6.0', NOW(), 'upgraded', 'N/A');

DROP TABLE IF EXISTS `weeks`;

ALTER TABLE `days`
CHANGE COLUMN `hours` `hours` SMALLINT(5) UNSIGNED NOT NULL COMMENT 'The number of hours in this day. Could be less than 24 in case the last job record fell in the middle of this day.',
CHANGE COLUMN `seconds` `seconds` INT(10) UNSIGNED NOT NULL COMMENT 'number of seconds n the day';

ALTER TABLE `months`
CHANGE COLUMN `month` `month` SMALLINT(5) UNSIGNED NOT NULL COMMENT 'The month of the year. Starts at 1.';

ALTER TABLE `quarters`
CHANGE COLUMN `quarter` `quarter` SMALLINT(5) UNSIGNED NOT NULL COMMENT 'The quarter of the year [1-4]',
CHANGE COLUMN `quarter_start_ts` `quarter_start_ts` INT(10) UNSIGNED NOT NULL COMMENT 'The start timestamp of the quarter in epochs.';

ALTER TABLE `years`
CHANGE COLUMN `year` `year` SMALLINT(5) UNSIGNED NOT NULL COMMENT 'The year of the record.';
