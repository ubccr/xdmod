-- ------------------------------------------------------------------------------------------
-- Post-job ingestion updates
--
-- We assume that the wallduration and end_time_ts have both been calculated:
-- ------------------------------------------------------------------------------------------


-- Update the processor count for jobs recently ingested. If a resource is not present in the
-- resource specs table records for that job will not be modified
--
-- Also if the resource has no entry in the resourcespecs table use the nodecount.
--
-- NOTE: job start/end timestamps are in UTC/GMT while ETL start/end dates are in the timezone of
-- the server, but FROM_UNIXTIME() returns the value in the current timezone.

UPDATE
    ${DESTINATION_SCHEMA}.job_tasks task
    JOIN modw.resourcespecs rs ON task.resource_id = rs.resource_id
SET
    processor_count =
    CASE
        WHEN task.processor_count IS NULL OR 0 = task.processor_count
        THEN
            CASE
                WHEN (task.node_count > rs.q_nodes) OR (task.node_count * rs.q_ppn > rs.q_nodes)
                THEN task.node_count
                ELSE task.node_count * rs.q_ppn
            END
        ELSE task.processor_count
    END
WHERE
    rs.q_ppn IS NOT NULL
    AND task.last_modified >= ${LAST_MODIFIED}
//

-- Set the CPU time for each job and update the submit time and wait duration. These are done here
-- to make the ingestion query less complex as they depend on the start_time and wallduration being
-- normalized. This assumes wallduration and processor counts have been normalized.
--
-- NOTE: job start/end timestamps are in UTC/GMT while ETL start/end dates are in the timezone of
-- the server, but FROM_UNIXTIME() returns the value in the current timezone.

UPDATE
    ${DESTINATION_SCHEMA}.job_tasks task
SET
    cpu_time = wallduration * processor_count,
    submit_time_ts =
    CASE
      WHEN (submit_time_ts > start_time_ts) OR submit_time_ts <= 0
        THEN start_time_ts
      ELSE submit_time_ts
    END,
    waitduration = start_time_ts -
    CASE
      WHEN (submit_time_ts > start_time_ts) OR submit_time_ts <= 0
        THEN start_time_ts
      ELSE submit_time_ts
    END
WHERE
    task.last_modified >= ${LAST_MODIFIED}
//
