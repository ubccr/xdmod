SQRT(
    COALESCE(
        (
            (
                SUM(POW(agg.gpu_count, 2) * agg.ended_job_count)
                /
                SUM(agg.running_job_count)
            )
            -
            POW(
                SUM(agg.gpu_count * agg.running_job_count)
                /
                SUM(agg.running_job_count),
                2
            )
        )
        /
        SUM(agg.running_job_count),
        0
    )
)
