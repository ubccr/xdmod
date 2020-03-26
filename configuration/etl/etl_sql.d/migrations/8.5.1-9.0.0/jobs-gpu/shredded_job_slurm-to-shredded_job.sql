LOCK TABLES `${SOURCE_SCHEMA}`.`shredded_job_slurm` READ,
    `${DESTINATION_SCHEMA}`.`shredded_job` WRITE//
UPDATE `${SOURCE_SCHEMA}`.`shredded_job_slurm`
INNER JOIN `${DESTINATION_SCHEMA}`.`shredded_job`
    ON `${SOURCE_SCHEMA}`.`shredded_job_slurm`.`cluster_name` = `${DESTINATION_SCHEMA}`.`shredded_job`.`resource_name`
        AND `${SOURCE_SCHEMA}`.`shredded_job_slurm`.`job_id` = `${DESTINATION_SCHEMA}`.`shredded_job`.`job_id`
        AND `${SOURCE_SCHEMA}`.`shredded_job_slurm`.`job_id_raw` = `${DESTINATION_SCHEMA}`.`shredded_job`.`job_id_raw`
        AND `${SOURCE_SCHEMA}`.`shredded_job_slurm`.`submit_time` = `${DESTINATION_SCHEMA}`.`shredded_job`.`submission_time`
        AND `${SOURCE_SCHEMA}`.`shredded_job_slurm`.`end_time` = `${DESTINATION_SCHEMA}`.`shredded_job`.`end_time`
SET `${DESTINATION_SCHEMA}`.`shredded_job`.`gpu_count` = `${SOURCE_SCHEMA}`.`shredded_job_slurm`.`ngpus`
WHERE `${DESTINATION_SCHEMA}`.`shredded_job`.`source_format` = 'slurm'
    AND (`${DESTINATION_SCHEMA}`.`shredded_job`.`gpu_count` != 0 OR `${SOURCE_SCHEMA}`.`shredded_job_slurm`.`ngpus` != 0)//
UNLOCK TABLES//
