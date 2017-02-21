-- ------------------------------------------------------------------------------------------
--  If start_time > end_time or start_time <= 0 then calculate it based on end_time -
--  wallduration. Note that wallduration is only trusted if start > end otherwise it is
--  recalculated as the delta between end_time and start_time.
-- ------------------------------------------------------------------------------------------

CASE
  WHEN (start_time > end_time) OR ((start_time) <= 0)
    THEN (end_time) -
      CASE
        WHEN ((end_time) - (start_time) < 0) AND wallduration > 0
          THEN wallduration
        ELSE ABS((end_time) - (start_time))
      END
  ELSE (start_time)
END
