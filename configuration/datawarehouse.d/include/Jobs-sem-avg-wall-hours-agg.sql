SQRT(
    COALESCE(
        (
            (
                SUM(COALESCE(agg.sum_wallduration_squared, 0.0))
                /
                SUM(agg.ended_job_count)
            )
            -
            POW(
                SUM(COALESCE(agg.wallduration, 0))
                /
                SUM(agg.ended_job_count)
                , 2
            )
        )
        /
        SUM(agg.ended_job_count)
        , 0
    )
)
/
3600.0
