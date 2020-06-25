LOCK TABLES `${SOURCE_SCHEMA}`.`staging_job` READ,
    `${DESTINATION_SCHEMA}`.`hpcdb_jobs` WRITE//
UPDATE `${SOURCE_SCHEMA}`.`staging_job`
INNER JOIN `${DESTINATION_SCHEMA}`.`hpcdb_jobs`
    ON `${SOURCE_SCHEMA}`.`staging_job`.`id` = `${DESTINATION_SCHEMA}`.`hpcdb_jobs`.`job_id`
SET `${DESTINATION_SCHEMA}`.`hpcdb_jobs`.`gpucount` = `${SOURCE_SCHEMA}`.`staging_job`.`gpu_count`
WHERE `${SOURCE_SCHEMA}`.`staging_job`.`gpu_count` != 0 OR `${DESTINATION_SCHEMA}`.`hpcdb_jobs`.`gpucount` != 0//
UNLOCK TABLES//
