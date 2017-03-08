-- ------------------------------------------------------------------------------------------
--  If start_time > end_time or start_time <= EPOCH then calculate it based on end_time -
--  wallduration. Note that wallduration is only trusted if start > end otherwise it is
--  recalculated as the delta between end_time and start_time.
--  ------------------------------------------------------------------------------------------

CASE
  WHEN (start_time > end_time) OR (EXTRACT(epoch FROM start_time) <= 0)
    THEN TO_TIMESTAMP(EXTRACT(epoch FROM end_time) -
      CASE
        WHEN start_time > end_time AND wallduration > 0
          THEN wallduration
        ELSE ABS(EXTRACT(epoch FROM end_time) - EXTRACT(epoch FROM start_time))
      END
    )
  ELSE start_time
END
