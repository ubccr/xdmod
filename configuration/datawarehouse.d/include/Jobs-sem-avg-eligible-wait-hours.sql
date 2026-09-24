SQRT(
    COALESCE(
        (
            (
                SUM(COALESCE(agg.sum_eligible_waitduration_squared, 0.0))
                /
                SUM(agg.eligible_started_job_count)
            )
            -
            POW(
                SUM(COALESCE(agg.eligible_waitduration, 0))
                /
                SUM(agg.eligible_started_job_count)
                , 2
            )
        )
        /
        SUM(agg.eligible_started_job_count)
        , 0
    )
)
/
3600.0