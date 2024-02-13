100.0 *
COALESCE(
    SUM(agg.processor_count * agg.running_job_count)
    /
    SUM(agg.running_job_count)
    /
    (
        SELECT
            SUM(rrf.cpu_processor_count)
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
