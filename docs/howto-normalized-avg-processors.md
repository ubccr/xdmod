The "Job Size: Normalized" statistic in the Jobs realm was removed from the default list of statistics in version 11.5.0 of Open XDMoD. The statistic was removed because the values displayed in XDMoD were incorrect if the resource size changed during the plotted time period. The statistic computation also did not produce a physically meaningful numerical values except when grouping by or filtering by resource.

The recommended way to report on this type of statistic in Open XDMoD 11.5+ is to use the Data Analytics Framework to compute the normalization factor. However, the "Job Size: Normalized" statistic can be restored to the  Open XDMoD portal by making the following changes to  configuration files in your installation of Open XDMoD. For an RPM-based install, these files are located in `/etc/xdmod`. For a source code install they are located in the`/etc` directory under the installation location.

1. Add the following to `datawarehouse.d/ref/Jobs-statistics.json`:
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


2. Add the following to `datawarehouse.d/ref/Gateways-statistics.json`:
```json
 "normalized_avg_processors": {
        "$overwrite": {
            "description_html": "The percentage average size ${ORGANIZATION_NAME} ${REALM_NAME} job over total machine cores.<br><i>Normalized Job Size: </i>The percentage total number of processor cores used by a (parallel) job over the total number of cores on the machine.",
            "name": "Job Size: Normalized via ${REALM_NAME}"
        },
        "$ref-with-overwrite": "datawarehouse.d/ref/Jobs-statistics.json#/normalized_avg_processors"
    },
```

3. Create the file `datawarehouse.d/include/Jobs-norm-avg-proc-time.sql`
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

4. Create the file `datawarehouse.d/include/Jobs-norm-avg-proc-agg.sql`:
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
