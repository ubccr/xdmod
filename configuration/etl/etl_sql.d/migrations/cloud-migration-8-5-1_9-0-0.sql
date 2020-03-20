-- In order to chnage from a multi column to single column unique auto increment id field
-- we change in id field on the table to not be auto increment, then drop the auto increment
-- key and finally drop the id field. Once ingestion is run again at the end of the migration
-- pipeline the id field is remade as a unique field with auto incremented id's. The aggregation
-- tables use the account_id and instance_type_id fields and are updated when aggregation is run
-- at the end of the migration script
//
LOCK TABLES
  modw_cloud.account WRITE,
  modw_cloud.instance WRITE,
  modw_cloud.instance_type WRITE,
  modw_cloud.instance_data WRITE,
  modw_cloud.session_records WRITE,
  modw_cloud.event WRITE;
//
ALTER TABLE modw_cloud.account MODIFY account_id INT NOT NULL;
ALTER TABLE modw_cloud.account DROP INDEX autoincrement_key;
ALTER TABLE modw_cloud.account ADD COLUMN new_account_id INT(11) UNSIGNED NOT NULL auto_increment unique;
UPDATE
  modw_cloud.instance as i
JOIN
  modw_cloud.account as acc
ON
  i.resource_id = acc.resource_id AND i.account_id = acc.account_id
SET
  i.account_id = acc.new_account_id;
//
ALTER TABLE modw_cloud.instance_type MODIFY instance_type_id INT NOT NULL;
ALTER TABLE modw_cloud.instance_type DROP INDEX increment_key;
ALTER TABLE modw_cloud.instance_type ADD COLUMN new_instance_type_id INT(11) UNSIGNED NOT NULL auto_increment unique;
UPDATE
  modw_cloud.instance_data as id
JOIN
  modw_cloud.instance_type as it
ON
  id.resource_id = it.resource_id AND id.instance_type_id = it.instance_type_id
SET
  id.instance_type_id = it.new_instance_type_id;
UPDATE
  modw_cloud.session_records as sr
JOIN
  modw_cloud.instance_type as it
ON
  sr.resource_id = it.resource_id AND sr.instance_type_id = it.instance_type_id
SET
  sr.instance_type_id = it.new_instance_type_id;
//
-- The instance_id in used on the event table so we need to update the event table
-- with the new instance_id value. The existing multi column auto increment key is
-- dropped and a new field is created. Once the event table is updated the old instance_id
-- is dropped and the new instance_id field is renamed
ALTER TABLE modw_cloud.instance MODIFY instance_id INT(11) NOT NULL;
ALTER TABLE modw_cloud.instance DROP INDEX increment_key;
ALTER TABLE modw_cloud.instance ADD COLUMN new_instance_id INT(11) UNSIGNED NOT NULL auto_increment unique;
UPDATE
  modw_cloud.event AS ev
JOIN
  modw_cloud.instance AS i
ON
  ev.resource_id = i.resource_id AND ev.instance_id = i.instance_id
SET
  ev.instance_id = i.new_instance_id;
-- On the session_records table the instance_id column is part of the primary key and
-- in order to update it the primary key needs to be removed then the instance_id can
-- be updated and the primary key reapplied
ALTER TABLE modw_cloud.session_records DROP PRIMARY KEY;
UPDATE
  modw_cloud.session_records as sr
JOIN
  modw_cloud.instance as i
ON
  sr.resource_id = i.resource_id AND sr.instance_id = i.instance_id
SET
  sr.instance_id = i.new_instance_id;
ALTER TABLE modw_cloud.session_records ADD PRIMARY KEY (resource_id, instance_id, start_time_ts);
//
ALTER TABLE modw_cloud.account DROP COLUMN account_id;
ALTER TABLE modw_cloud.account CHANGE new_account_id account_id INT(11) UNSIGNED;
ALTER TABLE modw_cloud.instance_type DROP COLUMN instance_type_id;
ALTER TABLE modw_cloud.instance_type CHANGE new_instance_type_id instance_type_id  INT(11) UNSIGNED;
ALTER TABLE modw_cloud.instance DROP COLUMN instance_id;
ALTER TABLE modw_cloud.instance CHANGE new_instance_id instance_id INT(11) UNSIGNED NOT NULL auto_increment unique;
//
UNLOCK TABLES;
