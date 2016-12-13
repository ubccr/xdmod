-- --------------------------------------------------------------------------------
-- Calculate the portion of a metric collected during a source time period (srcStart - srcEnd)
-- that overlaps with the destination (e.g., aggregation) time period (destStart - destEnd). This
-- is used to determine the portion of a job that is included in a given aggregation period.
-- Cast it to the specified data type.
--
-- 1) Source time period contained within destination time. Return unmodified stat.
--
--     Ss -- Se
-- Ds ---------- De
--
-- 2) Source time period overlaps a portion of destination time period. Return (stat * fraction of overlap)
--
-- Ss ----- Se             OR            Ss ----- Se  
--      Ds ---------- De        Ds ---------- De
--
-- 3) Source includes destination. Return (stat * max) where max may not be De - Ds.
--
-- Ss ---------- Se
--     Ds -- De
--
-- NOTE: All start and end times are assumed to be unix timestamps!
--
-- @param $data_type
-- @param $statistic The statistic that we are working with
-- @param $max The maximum value of the statistic (may not be end minus start)
-- @param $src_start_ts Start time of the source period
-- @param $src_end_ts End time of the source period
-- @param $dest_start_ts Start time of the destination period
-- @param $dest_end_ts End time of the destination period
-- --------------------------------------------------------------------------------
CAST(
CASE
  WHEN (${src_start_ts} BETWEEN ${dest_start_ts} AND ${dest_end_ts}) AND (${src_end_ts} BETWEEN ${dest_start_ts} AND ${dest_end_ts} )
    THEN ${statistic}
  WHEN ( ${src_start_ts} < ${dest_start_ts} AND ${src_end_ts} BETWEEN ${dest_start_ts} AND ${dest_end_ts} )
    THEN ${statistic} * (${src_end_ts} - ${dest_start_ts}) / (${src_end_ts} - ${src_start_ts})
  WHEN ( ${src_start_ts} BETWEEN ${dest_start_ts} AND ${dest_end_ts} AND ${src_end_ts} > ${dest_end_ts} )
    THEN ${statistic} * ((${dest_end_ts} + 1) - ${src_start_ts}) / (${src_end_ts} - ${src_start_ts})
  WHEN ( ${src_start_ts} < ${dest_start_ts} AND ${src_end_ts} > ${dest_end_ts} )
    THEN ${statistic} * ${max} / (${src_end_ts} - ${src_start_ts})
  ELSE ${statistic}
END
AS ${data_type})
