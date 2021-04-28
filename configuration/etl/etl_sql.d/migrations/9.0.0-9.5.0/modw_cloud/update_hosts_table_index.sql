LOCK TABLES
  modw_cloud.host WRITE,
  modw_cloud.host as h WRITE,
  modw_cloud.event WRITE,
  modw_cloud.event as ev WRITE;

ALTER TABLE modw_cloud.host MODIFY host_id INT NOT NULL;
ALTER TABLE modw_cloud.host DROP INDEX autoincrement_key;
ALTER TABLE modw_cloud.host ADD COLUMN new_host_id INT(11) UNSIGNED NOT NULL auto_increment unique;
ALTER TABLE modw_cloud.event DROP PRIMARY KEY;
CREATE INDEX host_resource_idx ON modw_cloud.event (host_id, resource_id);
CREATE INDEX host_resource_idx ON modw_cloud.host (host_id, resource_id);

UPDATE
  modw_cloud.event as ev
JOIN
  modw_cloud.host as h
ON
  ev.resource_id = h.resource_id AND ev.host_id = h.host_id
SET
  ev.host_id = h.new_host_id;

ALTER TABLE modw_cloud.host DROP COLUMN host_id;
ALTER TABLE modw_cloud.host CHANGE new_host_id host_id INT(11) UNSIGNED;
ALTER TABLE modw_cloud.event ADD PRIMARY KEY(resource_id, instance_id, event_time_ts, event_type_id, host_id);
DROP INDEX host_resource_idx ON modw_cloud.host;
DROP INDEX host_resource_idx ON modw_cloud.event;

UNLOCK TABLES;
