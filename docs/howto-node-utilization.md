---
title: HOWTO Enable Node Utilization Statistics
---

In addition to the "Utilization" statistics, Open XDMoD includes a "Node
Utilization" statistic.  This statistic is disabled by default because
it is not appropriate for all resource configurations.  Specifically, if
your resource allows node sharing, this statistic will not be accurate.
All nodes are assumed to be exclusive when this statistic is calculated.

First, add the SQL queries used by the statistic.

`/etc/xdmod/datawarehouse.d/include/Jobs-node-util-agg.sql`:

```sql
100.0 * (
    COALESCE(
        SUM(agg.node_time)
        /
        (
            SELECT
                SUM(ra.percent * inner_days.hours * rs.q_nodes / 100.0)
            FROM
                modw.resourcespecs rs,
                modw.resource_allocated ra,
                modw.days inner_days
            WHERE
                inner_days.id BETWEEN YEAR(FROM_UNIXTIME(ra.start_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(ra.start_date_ts))
                    AND COALESCE(YEAR(FROM_UNIXTIME(ra.end_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(ra.end_date_ts)), 999999999)
                AND inner_days.id BETWEEN YEAR(FROM_UNIXTIME(rs.start_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(rs.start_date_ts))
                    AND COALESCE(YEAR(FROM_UNIXTIME(rs.end_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(rs.end_date_ts)), 999999999)
                AND inner_days.id BETWEEN YEAR(FROM_UNIXTIME(${START_DATE_TS})) * 100000 + DAYOFYEAR(FROM_UNIXTIME(${START_DATE_TS}))
                    AND YEAR(FROM_UNIXTIME(${END_DATE_TS})) * 100000 + DAYOFYEAR(FROM_UNIXTIME(${END_DATE_TS}))
                AND ra.resource_id = rs.resource_id
                AND FIND_IN_SET(
                    rs.resource_id,
                    GROUP_CONCAT(DISTINCT agg.task_resource_id)
                ) <> 0
        ),
        0
    ) / 3600.0
)
```

`/etc/xdmod/datawarehouse.d/include/Jobs-node-util-time.sql`:

```sql
100.0 * (
    COALESCE(
        SUM(agg.node_time)
        /
        (
            SELECT
                SUM(ra.percent * inner_days.hours * rs.q_nodes / 100.0)
            FROM
                modw.resourcespecs rs,
                modw.resource_allocated ra,
                modw.days inner_days
            WHERE
                inner_days.id BETWEEN YEAR(FROM_UNIXTIME(ra.start_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(ra.start_date_ts))
                    AND COALESCE(YEAR(FROM_UNIXTIME(ra.end_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(ra.end_date_ts)), 999999999)
                AND inner_days.id BETWEEN YEAR(FROM_UNIXTIME(rs.start_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(rs.start_date_ts))
                    AND COALESCE(YEAR(FROM_UNIXTIME(rs.end_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(rs.end_date_ts)), 999999999)
                AND inner_days.id BETWEEN YEAR(FROM_UNIXTIME(duration.${AGGREGATION_UNIT}_start_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(duration.${AGGREGATION_UNIT}_start_ts))
                    AND YEAR(FROM_UNIXTIME(duration.${AGGREGATION_UNIT}_end_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(duration.${AGGREGATION_UNIT}_end_ts))
                AND ra.resource_id = rs.resource_id
                AND FIND_IN_SET(
                    rs.resource_id,
                    GROUP_CONCAT(DISTINCT agg.task_resource_id)
                ) <> 0
        ),
        0
    ) / 3600.0
)
```

Then add the statistic to the Jobs realm statistics configuration.

`/etc/xdmod/datawarehouse.d/ref/Jobs-statistics.json`:

```json
{
    ...
    "node_utilization": {
        "name": "Node Utilization",
        "description_html": "The percentage of node time that a resource has been running jobs.<br/><i>Node Utilization:</i> The ratio of the total node hours consumed by jobs over a given time period divided by the maximum node hours that the system could deliver (based on the number of nodes present on the resources). This value does not take into account downtimes or outages. It is just calculated based on the number of nodes in the resource specifications.",
        "precision": 2,
        "unit": "%",
        "aggregate_formula": {
            "$include": "datawarehouse.d/include/Jobs-node-util-agg.sql"
        },
        "timeseries_formula": {
            "$include": "datawarehouse.d/include/Jobs-node-util-time.sql"
        }
    }
}
```

Last, run `acl-config` and restart your web server.

Reload the portal and the "Node Utilization" statistic will appear in the list
of Job statistics.
