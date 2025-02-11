The Normalized Avg Processor statistic is available if you add the following JSON and SQL Files to the XDMoD project.

1. Add the follwing to: 
- configuration/datawarehouse.d/ref/Job-Statistics.json
```json
    "normalized_avg_processors": {
        "aggregate_formula": {
            "$include": "datawarehouse.d/include/Jobs-norm-avg-proc-agg.sql"
        },
        "description_html": "The percentage average size ${ORGANIZATION_NAME} job divided by the total number of cores in the resource where the job ran. The job normalization calculation assumes that the resource size is constant. This statistic should not be used with a time range where the resource size changes, because the statistic will be incorrect.<br><i>Normalized Job Size: </i>The ratio of the total number of processor cores used by a (parallel) job over the total number of cores on the resource.",
        "name": "Job Size: Normalized",
        "precision": 1,
        "timeseries_formula": {
            "$include": "datawarehouse.d/include/Jobs-norm-avg-proc-time.sql"
        },
        "unit": "% of Total Cores"
    },

```


2. Add the following to: 
- configuration/datawarehouse.d/ref/Gateways-Statistics.json
```json
 "normalized_avg_processors": {
        "$overwrite": {
            "description_html": "The percentage average size ${ORGANIZATION_NAME} ${REALM_NAME} job over total machine cores.<br><i>Normalized Job Size: </i>The percentage total number of processor cores used by a (parallel) job over the total number of cores on the machine.",
            "name": "Job Size: Normalized via ${REALM_NAME}"
        },
        "$ref-with-overwrite": "datawarehouse.d/ref/Jobs-statistics.json#/normalized_avg_processors"
    },
```

3. Create a **jobs-norm-avg-proc-time.sql** in the following: 
- configuration/datawarehouse.d/include/jobs-norm-avg-proc-time.sql
```sql
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

```

4. create a **jobs-norm-avg-proc-agg.sql** at the follwing: 
- configuration/datawarehouse.d/include/jobs-norm-avg-proc-agg.sql
```sql
100.0 *
COALESCE(
    SUM(agg.processor_count * CASE ${DATE_TABLE_ID_FIELD} WHEN ${MIN_DATE_ID} THEN agg.running_job_count ELSE agg.started_job_count END)
    /
    SUM(CASE ${DATE_TABLE_ID_FIELD} WHEN ${MIN_DATE_ID} THEN agg.running_job_count ELSE agg.started_job_count END)
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

```
