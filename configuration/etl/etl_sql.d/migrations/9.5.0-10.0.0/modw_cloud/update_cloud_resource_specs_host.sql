ALTER TABLE modw_cloud.cloud_resource_specs MODIFY host_id INT(11) NOT NULL;
ALTER TABLE modw_cloud.cloud_resource_specs DROP INDEX autoincrement_key;
ALTER TABLE modw_cloud.staging_resource_specifications ADD COLUMN host_id INT(11) NOT NULL;

-- Any hostnames in the cloud_resource_specs table that do not exist in
-- `modw_cloud`.`host` should be added to that table
INSERT INTO `modw_cloud`.`host` (resource_id, hostname) SELECT
  rs.resource_id,
  rs.hostname
FROM
  `modw_cloud`.`cloud_resource_specs` as rs
GROUP BY
  rs.resource_id,
  rs.hostname
ON DUPLICATE KEY UPDATE
  resource_id = VALUES(resource_id), hostname = VALUES(hostname);

-- Update the new host_id field to a value that is in `modw_cloud`.`host`
UPDATE
  `modw_cloud`.`staging_resource_specifications` AS srs
JOIN
  `modw_cloud`.`host` as h
ON
  h.hostname = srs.hostname and h.resource_id = srs.resource_id
SET
  srs.host_id = h.host_id;

UPDATE
  `modw_cloud`.`cloud_resource_specs` AS rs
JOIN
  `modw_cloud`.`host` as h
ON
  h.hostname = rs.hostname and h.resource_id = rs.resource_id
SET
  rs.host_id = h.host_id;
