LOCK TABLES `${SOURCE_SCHEMA}`.`shredded_job_pbs` READ,
    `${DESTINATION_SCHEMA}`.`shredded_job` WRITE//
UPDATE `${SOURCE_SCHEMA}`.`shredded_job_pbs`
INNER JOIN `${DESTINATION_SCHEMA}`.`shredded_job`
    ON `${SOURCE_SCHEMA}`.`shredded_job_pbs`.`host` = `${DESTINATION_SCHEMA}`.`shredded_job`.`resource_name`
        AND `${SOURCE_SCHEMA}`.`shredded_job_pbs`.`job_id` = `${DESTINATION_SCHEMA}`.`shredded_job`.`job_id`
        AND `${SOURCE_SCHEMA}`.`shredded_job_pbs`.`job_id` = `${DESTINATION_SCHEMA}`.`shredded_job`.`job_id_raw`
        AND `${SOURCE_SCHEMA}`.`shredded_job_pbs`.`ctime` = `${DESTINATION_SCHEMA}`.`shredded_job`.`submission_time`
        AND `${SOURCE_SCHEMA}`.`shredded_job_pbs`.`end` = `${DESTINATION_SCHEMA}`.`shredded_job`.`end_time`
SET `${DESTINATION_SCHEMA}`.`shredded_job`.`gpu_count` = `${SOURCE_SCHEMA}`.`shredded_job_pbs`.`resources_used_gpus`
WHERE `${DESTINATION_SCHEMA}`.`shredded_job`.`source_format` = 'pbs'
    AND (`${DESTINATION_SCHEMA}`.`shredded_job`.`gpu_count` != 0 OR `${SOURCE_SCHEMA}`.`shredded_job_pbs`.`resources_used_gpus` != 0)//
UNLOCK TABLES//
