-- Add column to the `shredded_job_slurm` table.  This table is not managed by
-- an ETL definition because it contains a key that is currently not supported
-- by the ETL system.

-- Wrap the ALTER TABLE statement in a prepared statement that conditionally
-- add the column so that this file can be executed multiple times without
-- causing errors.
SET @statement = (
    SELECT IF(
        (
            SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = '${DESTINATION_SCHEMA}'
                AND table_name = 'shredded_job_slurm'
                AND column_name = 'qos_name'
        ) > 0,
        'SELECT 1',
        CONCAT(
            'ALTER TABLE `${DESTINATION_SCHEMA}`.`shredded_job_slurm` ',
            'ADD COLUMN `qos_name` tinytext DEFAULT NULL ',
            'AFTER `partition_name`'
        )
    )
);
PREPARE addColumnIfNotExists FROM @statement;
EXECUTE addColumnIfNotExists;
DEALLOCATE PREPARE addColumnIfNotExists;
