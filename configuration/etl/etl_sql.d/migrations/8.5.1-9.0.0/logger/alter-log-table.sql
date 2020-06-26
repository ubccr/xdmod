-- Add index to the `log_table` table.  This table is not managed by an ETL
-- definition.

-- Wrap the ALTER TABLE statement in a prepared statement that conditionally
-- add the column so that this file can be executed multiple times without
-- causing errors.
SET @statement = (
    SELECT IF(
        (
            SELECT COUNT(*) FROM information_schema.statistics
            WHERE table_schema = '${DESTINATION_SCHEMA}'
                AND table_name = 'log_table'
                AND index_name = 'get_messages_idx'
        ) > 0,
        'SELECT 1',
        CONCAT(
            'ALTER TABLE `${DESTINATION_SCHEMA}`.`log_table` ',
            'ADD INDEX `get_messages_idx` (`ident`,`logtime`,`priority`)'
        )
    )
)//
PREPARE addIndexIfNotExists FROM @statement//
EXECUTE addIndexIfNotExists//
DEALLOCATE PREPARE addIndexIfNotExists//
