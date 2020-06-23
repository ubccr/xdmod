LOCK TABLES `${SOURCE_SCHEMA}`.`shredded_job` READ,
    `${DESTINATION_SCHEMA}`.`staging_job` WRITE//
UPDATE `${SOURCE_SCHEMA}`.`shredded_job`
INNER JOIN `${DESTINATION_SCHEMA}`.`staging_job`
    ON `${SOURCE_SCHEMA}`.`shredded_job`.`shredded_job_id` = `${DESTINATION_SCHEMA}`.`staging_job`.`id`
SET `${DESTINATION_SCHEMA}`.`staging_job`.`gpu_count` = `${SOURCE_SCHEMA}`.`shredded_job`.`gpu_count`
WHERE `${SOURCE_SCHEMA}`.`shredded_job`.`gpu_count` != 0 OR `${DESTINATION_SCHEMA}`.`staging_job`.`gpu_count` != 0//
UNLOCK TABLES//
