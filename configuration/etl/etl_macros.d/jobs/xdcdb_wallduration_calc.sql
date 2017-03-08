-- ------------------------------------------------------------------------------------------
-- We can't always trust the wallduration stored in the XDCDB (see job_id = 26924663 where
-- wallduration is 50 and local_charge is 768 but according to the start and end dates it should be
-- 86450). If start > end and wallduration > 0 then start is suspect so trust wallduration,
-- otherwise re-calculate it as the delta between end_time and start_time.
-- ------------------------------------------------------------------------------------------

CASE
   WHEN start_time > end_time AND wallduration > 0
     THEN wallduration
   ELSE ABS(EXTRACT(epoch FROM end_time) - EXTRACT(epoch FROM start_time))
END
