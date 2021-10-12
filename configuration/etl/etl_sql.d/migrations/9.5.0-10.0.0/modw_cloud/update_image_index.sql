LOCK TABLES
  modw_cloud.image WRITE,
  modw_cloud.image as img WRITE,
  modw_cloud.instance_data WRITE,
  modw_cloud.instance_data as id WRITE;

ALTER TABLE modw_cloud.image MODIFY image_id INT NOT NULL;
ALTER TABLE modw_cloud.image DROP INDEX autoincrement_key;
ALTER TABLE modw_cloud.image ADD COLUMN new_image_id INT(11) UNSIGNED NOT NULL auto_increment unique;
ALTER TABLE modw_cloud.instance_data DROP PRIMARY KEY;
CREATE INDEX host_resource_idx ON modw_cloud.image (image_id, resource_id);
CREATE INDEX host_resource_idx ON modw_cloud.instance_data (image_id, resource_id);

UPDATE
  modw_cloud.instance_data as id
JOIN
  modw_cloud.image as img
ON
  id.resource_id = img.resource_id AND id.image_id = img.image_id
SET
  id.image_id = img.new_image_id;

ALTER TABLE modw_cloud.image DROP COLUMN image_id;
ALTER TABLE modw_cloud.image CHANGE new_image_id image_id INT(11) UNSIGNED;
ALTER TABLE modw_cloud.instance_data ADD PRIMARY KEY(resource_id, event_id);
DROP INDEX host_resource_idx ON modw_cloud.image;
DROP INDEX host_resource_idx ON modw_cloud.instance_data;

UNLOCK TABLES;
