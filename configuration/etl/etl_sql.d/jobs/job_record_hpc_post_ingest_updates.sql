-- ------------------------------------------------------------------------------------------
-- Post-job ingestion updates
--
-- We assume that the wallduration and end_time_ts haven both been calculated:
--
-- end_time_ts = EXTRACT(epoch FROM end_time)
--
-- wallduration =
-- CASE
--    WHEN (EXTRACT(epoch FROM end_time) - EXTRACT(epoch FROM start_time) < 0) AND wallduration > 0
--      THEN wallduration
--    ELSE ABS(EXTRACT(epoch FROM end_time) - EXTRACT(epoch FROM start_time))
-- END
-- ------------------------------------------------------------------------------------------


-- Update the processor count for jobs recently ingested. If a resource is not present in the
-- resource specs table records for that job will not be modified
--
-- IF (resource is keenland) THEN
--   Use the special case processor_count = processor_count * processors_per_node
-- ELSE IF (processor_count is not null AND processor_count != 0) THEN
--   processor_count = processor_count
-- ELSE
--   IF (node_count > total nodes available) OR (node_count * processors_per_node > total_nodes_available) THEN
--     processors = node_count
--   ELSE
--     processor_count = node_count * processors_per_node
--   END IF
-- END IF
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
        WHEN 2800 = task.resource_id
        THEN task.processor_count * rs.q_ppn
        ELSE
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

-- ------------------------------------------------------------------------------------------
-- Update historical data
-- ------------------------------------------------------------------------------------------

-- Update any people that have changed in both the record and task tables as these may reference
-- different people

UPDATE
    ${DESTINATION_SCHEMA}.job_records record,
    ${UTILITY_SCHEMA}.person_mapper pm
SET
    record.person_id = pm.new_person_id
WHERE
    record.person_id = pm.old_person_id
//

UPDATE
    ${DESTINATION_SCHEMA}.job_tasks task,
    ${UTILITY_SCHEMA}.person_mapper pm
SET
    task.person_id = pm.new_person_id
WHERE
    task.person_id = pm.old_person_id
//

-- If a person changed, also update their organization id and nsf status code
--
-- Note that we use the max mysql datetime value of '9999-12-31 23:59:59' in several places to
-- indicate an infinate date in the future. Be aware that calling UNIX_TIMESTAMP('9999-12-31
-- 23:59:59') hits the 2038 32-bit integer bug and returns 0 in mysql (x86_64 v5.5.10) so making a
-- comparison such as `end_time_ts <= UNIX_TIMESTAMP(valid_until)` may very well not work.

UPDATE 
    ${DESTINATION_SCHEMA}.job_records record,
    ${UTILITY_SCHEMA}.personhistory ph
SET
    record.person_organization_id = ph.organization_id,
    record.person_nsfstatuscode_id = ph.nsfstatuscode_id
WHERE
    record.person_id = ph.person_id
    AND ( record.person_organization_id != ph.organization_id OR record.person_nsfstatuscode_id != ph.nsfstatuscode_id)
    AND FROM_UNIXTIME(record.submit_time_ts) BETWEEN ph.valid_from AND ph.valid_to
//

UPDATE 
    ${DESTINATION_SCHEMA}.job_tasks task,
    ${UTILITY_SCHEMA}.personhistory ph
SET
    task.person_organization_id = ph.organization_id,
    task.person_nsfstatuscode_id = ph.nsfstatuscode_id
WHERE
    task.person_id = ph.person_id
    AND ( task.person_organization_id != ph.organization_id OR task.person_nsfstatuscode_id != ph.nsfstatuscode_id)
    AND FROM_UNIXTIME(task.submit_time_ts) BETWEEN ph.valid_from AND ph.valid_to
//

-- If the PI changed, update the person id and the PI organization

UPDATE
    ${DESTINATION_SCHEMA}.job_records record,
    ${UTILITY_SCHEMA}.principalinvestigatorhistory h
SET
    record.principalinvestigator_person_id = h.person_id
WHERE
    record.request_id = h.request_id
    AND record.principalinvestigator_person_id != h.person_id
    AND FROM_UNIXTIME(record.submit_time_ts) BETWEEN h.valid_from AND h.valid_to
//


UPDATE
    ${DESTINATION_SCHEMA}.job_records record,
    ${UTILITY_SCHEMA}.personhistory h
SET
    record.piperson_organization_id = h.organization_id
WHERE
    record.principalinvestigator_person_id = h.person_id
    AND record.piperson_organization_id != h.organization_id
    AND FROM_UNIXTIME(record.submit_time_ts) BETWEEN h.valid_from AND h.valid_to
//
