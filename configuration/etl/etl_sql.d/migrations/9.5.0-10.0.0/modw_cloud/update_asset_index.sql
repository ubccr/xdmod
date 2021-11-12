LOCK TABLES
  modw_cloud.asset WRITE,
  modw_cloud.asset as a WRITE,
  modw_cloud.event_asset WRITE,
  modw_cloud.event_asset as ea WRITE;

ALTER TABLE modw_cloud.asset MODIFY asset_id INT NOT NULL;
ALTER TABLE modw_cloud.asset DROP INDEX autoincrement_key;
ALTER TABLE modw_cloud.asset ADD COLUMN new_asset_id INT(11) UNSIGNED NOT NULL auto_increment unique;
ALTER TABLE modw_cloud.event_asset DROP PRIMARY KEY;
CREATE INDEX asset_resource_idx ON modw_cloud.event_asset (asset_id, resource_id);
CREATE INDEX asset_resource_idx ON modw_cloud.asset (asset_id, resource_id);

UPDATE
  modw_cloud.event_asset as ea
JOIN
  modw_cloud.asset as a
ON
  ea.resource_id = a.resource_id AND ea.asset_id = a.asset_id
SET
  ea.asset_id = a.new_asset_id;

ALTER TABLE modw_cloud.asset DROP COLUMN asset_id;
ALTER TABLE modw_cloud.asset CHANGE new_asset_id asset_id INT(11) UNSIGNED;
ALTER TABLE modw_cloud.event_asset ADD PRIMARY KEY(resource_id, event_id, asset_id);
DROP INDEX asset_resource_idx ON modw_cloud.asset;
DROP INDEX asset_resource_idx ON modw_cloud.event_asset;

UNLOCK TABLES;
