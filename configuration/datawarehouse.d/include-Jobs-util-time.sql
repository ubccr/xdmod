100.0 * (
    COALESCE(
        SUM(agg.cpu_time)
        /
        (
            SELECT SUM( ra.percent * inner_days.hours * rs.processors / 100.0 )
            FROM modw.resourcespecs rs,
                    modw.resource_allocated ra,
                    modw.days inner_days
            WHERE
                inner_days.id BETWEEN YEAR(FROM_UNIXTIME(ra.start_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(ra.start_date_ts)) AND COALESCE(YEAR(FROM_UNIXTIME(ra.end_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(ra.end_date_ts)), 999999999) AND
                inner_days.id BETWEEN YEAR(FROM_UNIXTIME(rs.start_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(rs.start_date_ts)) AND COALESCE(YEAR(FROM_UNIXTIME(rs.end_date_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(rs.end_date_ts)), 999999999) AND
                inner_days.id BETWEEN YEAR(FROM_UNIXTIME(duration.${AGGREGATION_UNIT}_start_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(duration.${AGGREGATION_UNIT}_start_ts)) AND YEAR(FROM_UNIXTIME(duration.${AGGREGATION_UNIT}_end_ts)) * 100000 + DAYOFYEAR(FROM_UNIXTIME(duration.${AGGREGATION_UNIT}_end_ts)) AND
                ra.resource_id = rs.resource_id
                AND FIND_IN_SET(
                    rs.resource_id,
                    GROUP_CONCAT(DISTINCT agg.task_resource_id)
                ) <> 0
        ),
        0
    ) /3600.0
)
