-- Determine the end time for each instance in a post-processing step. To properly calculate the end
-- time we need to order the fields in order of descending start times and calculate the end time as
-- 1 second prior to the previous start time for the same instance type.  However, we cannot use
-- local variables to track the current instance type and previous start times as part of a
-- multi-table UPDATE unless we use them in a subquery and we cannot apply ORDER BY in multi-table
-- updates. We also cannot specify the target of the update in a subquery. I've opted to use a
-- temporary table to calculate the end times and then perform the update.

CREATE TEMPORARY TABLE ${DESTINATION_SCHEMA}.tmp_end_times
AS
SELECT
  instance_type_id,
  instance_type,
  resource_id,
  IF ( @current_instance_type = instance_type AND @prev_start IS NOT NULL, @prev_start - INTERVAL 1 SECOND, NULL) AS end_time,
  @current_instance_type := instance_type AS junk1,
  @prev_start := start_time AS junk2
FROM ${DESTINATION_SCHEMA}.instance_type
ORDER BY instance_type, start_time DESC
//

UPDATE
  ${DESTINATION_SCHEMA}.instance_type a
  JOIN ${DESTINATION_SCHEMA}.tmp_end_times e
SET
  a.end_time = e.end_time
WHERE
 a.instance_type_id = e.instance_type_id
AND
 a.resource_id = e.resource_id
//

-- The UpdateIngestor does not yet support queries as source data. Update the accounts table with
-- the account names found in the block device records.

UPDATE
${DESTINATION_SCHEMA}.account a
JOIN ${DESTINATION_SCHEMA}.eucalyptus_raw_volume v
  ON v.provider_account_number = a.provider_account AND v.resource_id = a.resource_id
SET a.display = v.provider_account_name
//
