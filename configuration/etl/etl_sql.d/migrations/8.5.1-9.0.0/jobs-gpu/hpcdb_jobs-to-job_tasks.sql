LOCK TABLES `${SOURCE_SCHEMA}`.`hpcdb_jobs` READ,
    `${DESTINATION_SCHEMA}`.`job_tasks` WRITE//
UPDATE `${SOURCE_SCHEMA}`.`hpcdb_jobs`
INNER JOIN `${DESTINATION_SCHEMA}`.`job_tasks`
    ON `${SOURCE_SCHEMA}`.`hpcdb_jobs`.`job_id` = `${DESTINATION_SCHEMA}`.`job_tasks`.`job_id`
SET `${DESTINATION_SCHEMA}`.`job_tasks`.`gpu_count` = `${SOURCE_SCHEMA}`.`hpcdb_jobs`.`gpucount`,
    `${DESTINATION_SCHEMA}`.`job_tasks`.`gpu_time` = `${SOURCE_SCHEMA}`.`hpcdb_jobs`.`gpucount` * `${DESTINATION_SCHEMA}`.`job_tasks`.`wallduration`
WHERE `${SOURCE_SCHEMA}`.`hpcdb_jobs`.`gpucount` != 0 OR `${DESTINATION_SCHEMA}`.`job_tasks`.`gpu_count` != 0//
UNLOCK TABLES//
