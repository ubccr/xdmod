ALTER TABLE modw_cloud.cloud_resource_specs MODIFY host_id INT(11) NOT NULL;
ALTER TABLE modw_cloud.cloud_resource_specs DROP INDEX autoincrement_key;

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


UPDATE
  `modw_cloud`.`cloud_resource_specs` AS rs
JOIN
  `modw_cloud`.`host` as h
ON
  h.hostname = rs.hostname and h.resource_id = rs.resource_id
SET
  rs.host_id = h.host_id;
