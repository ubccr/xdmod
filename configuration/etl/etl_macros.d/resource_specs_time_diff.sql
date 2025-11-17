GREATEST(
    0,
    1 + COALESCE(
        LEAST(
            ra.end_date_ts,
            r.end_date_ts,
            ${:PERIOD_END_TS}
        ),
        ${:PERIOD_END_TS}
    ) - GREATEST(
        ra.start_date_ts,
        r.start_date_ts,
        ${:PERIOD_START_TS}
    )
)
