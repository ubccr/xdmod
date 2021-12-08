LOCK TABLES `modw_cloud`.`cloud_resource_specs` WRITE;
UPDATE `modw_cloud`.`cloud_resource_specs` SET last_modified_ts = UNIX_TIMESTAMP(last_modified);
UNLOCK TABLES;
