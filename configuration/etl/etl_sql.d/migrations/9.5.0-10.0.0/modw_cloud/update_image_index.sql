LOCK TABLES
  modw_cloud.image WRITE,
  modw_cloud.image as img WRITE,
  modw_cloud.instance_data WRITE,
  modw_cloud.instance_data as id WRITE;

-- The next two statement set any row in `modw_cloud`.`instance_data` with a previous resource specific unknown values to the new global
-- (1) unknown value and then deletes those resource specific unknown values from the image table. This is done before the creation of
-- the single column auto-increment value to prevent gaps in the auto-increment values by deleting rows after the auto-increment column
-- is created.

  UPDATE
  	modw_cloud.instance_data as id
  JOIN
  	modw_cloud.image as img on id.resource_id = img.resource_id AND id.image_id = img.image_id
  SET
  	id.image_id = 1
  WHERE
  	img.image = 'unknown' and img.resource_id != -1;

-- This statement removes the previous resource specific unknown values
DELETE FROM modw_cloud.image WHERE image = 'unknown' and resource_id != -1;

ALTER TABLE modw_cloud.image MODIFY image_id INT NOT NULL;
ALTER TABLE modw_cloud.image DROP INDEX autoincrement_key;
ALTER TABLE modw_cloud.image ADD COLUMN new_image_id INT(11) UNSIGNED NOT NULL auto_increment unique;
ALTER TABLE modw_cloud.instance_data DROP PRIMARY KEY;
CREATE INDEX image_resource_idx ON modw_cloud.image (image_id, resource_id);
CREATE INDEX image_resource_idx ON modw_cloud.instance_data (image_id, resource_id);

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
DROP INDEX image_resource_idx ON modw_cloud.image;
DROP INDEX image_resource_idx ON modw_cloud.instance_data;

UNLOCK TABLES;
