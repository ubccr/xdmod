LOCK TABLES
  modw_cloud.event WRITE,
  modw_cloud.event as e WRITE,
  modw_cloud.event_asset WRITE,
  modw_cloud.event_asset as ea WRITE,
  modw_cloud.instance_data WRITE,
  modw_cloud.instance_data as id WRITE;

ALTER TABLE modw_cloud.event MODIFY event_id INT NOT NULL;
ALTER TABLE modw_cloud.event DROP INDEX increment_key;
ALTER TABLE modw_cloud.event ADD COLUMN new_event_id INT(11) UNSIGNED NOT NULL auto_increment unique;
ALTER TABLE modw_cloud.event_asset DROP PRIMARY KEY;
ALTER TABLE modw_cloud.instance_data DROP PRIMARY KEY;
CREATE INDEX event_resource_idx ON modw_cloud.event_asset (event_id, resource_id);
CREATE INDEX event_resource_idx ON modw_cloud.event (event_id, resource_id);

UPDATE
  modw_cloud.event_asset as ea
JOIN
  modw_cloud.event as e
ON
  ea.resource_id = e.resource_id AND ea.event_id = e.event_id
SET
  ea.event_id = e.new_event_id;

UPDATE
  modw_cloud.instance_data as id
JOIN
  modw_cloud.event as e
ON
  id.resource_id = e.resource_id AND id.event_id = e.event_id
SET
  id.event_id = e.new_event_id;

ALTER TABLE modw_cloud.event DROP COLUMN event_id;
ALTER TABLE modw_cloud.event CHANGE new_event_id event_id INT(11) UNSIGNED;
ALTER TABLE modw_cloud.event_asset ADD PRIMARY KEY(resource_id, event_id, asset_id);
ALTER TABLE modw_cloud.instance_data ADD PRIMARY KEY(resource_id, event_id);
DROP INDEX event_resource_idx ON modw_cloud.event;
DROP INDEX event_resource_idx ON modw_cloud.event_asset;

UNLOCK TABLES;
