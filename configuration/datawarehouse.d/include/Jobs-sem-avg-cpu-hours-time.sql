SQRT(
    COALESCE(
        (
            (
                SUM(agg.sum_cpu_time_squared)
                /
                SUM(agg.running_job_count)
            )
            -
            POW(
                SUM(agg.cpu_time)
                /
                SUM(agg.running_job_count)
                , 2
            )
        )
        /
        SUM(agg.running_job_count)
    , 0
    )
)
/
3600.0
