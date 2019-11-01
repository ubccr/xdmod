SQRT(
    COALESCE(
        (
            (
                SUM(COALESCE(agg.sum_waitduration_squared, 0.0))
                /
                SUM(agg.started_job_count)
            )
            -
            POW(
                SUM(COALESCE(agg.waitduration, 0))
                /
                SUM(agg.started_job_count)
                , 2
            )
        )
        /
        SUM(agg.started_job_count)
        , 0
    )
)
/
3600.0
