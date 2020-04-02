100.0 *
COALESCE(
    SUM(agg.processor_count * CASE ${DATE_TABLE_ID_FIELD} WHEN ${MIN_DATE_ID} THEN agg.running_job_count ELSE agg.started_job_count END)
    /
    SUM(CASE ${DATE_TABLE_ID_FIELD} WHEN ${MIN_DATE_ID} THEN agg.running_job_count ELSE agg.started_job_count END)
    /
    (
        SELECT
            SUM(rrf.processors)
        FROM
            modw.resourcespecs rrf
        WHERE
            FIND_IN_SET(
                rrf.resource_id,
                GROUP_CONCAT(distinct agg.task_resource_id)
            ) <> 0
            AND ${AGGREGATION_UNIT}_end_ts >= rrf.start_date_ts
            AND (
                rrf.end_date_ts IS NULL
                OR ${AGGREGATION_UNIT}_end_ts <= rrf.end_date_ts
            )
    ),
0)
