ALTER TABLE modw_cloud.account MODIFY account_id INT NOT NULL;
ALTER TABLE modw_cloud.account DROP INDEX autoincrement_key;
ALTER TABLE modw_cloud.account DROP COLUMN account_id;
//
ALTER TABLE modw_cloud.instance_type MODIFY instance_type_id INT NOT NULL;
ALTER TABLE modw_cloud.instance_type DROP INDEX increment_key;
ALTER TABLE modw_cloud.instance_type DROP COLUMN instance_type_id;
//
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
ALTER TABLE modw_cloud.instance DROP COLUMN instance_id;
ALTER TABLE modw_cloud.instance CHANGE new_instance_id instance_id int(11) unsigned;
