-- ------------------------------------------------------------------------------------------
--If start > end and wallduration > 0 then start is suspect so trust wallduration,
-- otherwise re-calculate it as the delta between end_time and start_time.
-- ------------------------------------------------------------------------------------------

CASE
   WHEN start_time > end_time AND wallduration > 0
     THEN wallduration
   ELSE ABS(end_time - start_time)
END
