GREATEST(0, 1 + LEAST(r.end_date_ts, ${:PERIOD_END_TS}) - GREATEST(r.start_date_ts, ${:PERIOD_START_TS}))
