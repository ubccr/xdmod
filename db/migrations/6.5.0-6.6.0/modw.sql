use modw;

LOCK TABLES `peopleonaccount` WRITE;
ALTER TABLE `peopleonaccount` DISABLE KEYS;

ALTER TABLE `peopleonaccount` DROP COLUMN `min_activity_time`;
ALTER TABLE `peopleonaccount` DROP COLUMN `min_activity_time_ts`;
ALTER TABLE `peopleonaccount` DROP KEY `aggregation_index`;
ALTER TABLE `peopleonaccount` ADD KEY `aggregation_index` (`resource_id`,`account_id`,`person_id`,`start_time_ts`,`end_time_ts`,`allocationstate_id`);

ALTER TABLE `peopleonaccount` ENABLE KEYS;
UNLOCK TABLES;

INSERT INTO schema_version_history VALUES ('modw', '6.6.0', NOW(), 'upgraded', 'N/A');
