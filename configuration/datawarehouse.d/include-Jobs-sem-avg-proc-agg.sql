SQRT(
    COALESCE(
        (
            (
                SUM(
                    POW(agg.processor_count, 2)
                    *
                    agg.ended_job_count
                )
                /
                SUM(agg.ended_job_count)
            )
            -
            POW(
                SUM(agg.processor_count * agg.ended_job_count)
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
