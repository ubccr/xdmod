-- Update destroy times of volume assets. This updates both volumes that are destoyed
-- in volume.delete.end events and also root volumes which are inferred from compute.instance.create
-- events.

UPDATE
	${DESTINATION_SCHEMA}.asset AS a
LEFT JOIN
	${DESTINATION_SCHEMA}.openstack_raw_event AS raw
ON
  raw.resource_id = a.resource_id AND raw.openstack_resource_id = a.provider_identifier
SET
	a.destroy_time_utc = raw.event_time_utc
WHERE
	raw.event_type = "volume.delete.end";
//

UPDATE
	${DESTINATION_SCHEMA}.asset AS a
LEFT JOIN
	${DESTINATION_SCHEMA}.instance AS i
ON
	i.resource_id = a.resource_id AND CONCAT('root-vol-', i.provider_identifier) = a.provider_identifier
LEFT JOIN
	${DESTINATION_SCHEMA}.openstack_staging_event AS staging
ON
	staging.resource_id = i.resource_id AND staging.instance_id = i.instance_id
SET
	a.destroy_time_utc = staging.event_time_utc
WHERE
	staging.event_type_id = 4;
//

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
//

-- Truncate raw and staging tables once the data is no longer needed

TRUNCATE ${DESTINATION_SCHEMA}.openstack_raw_event;

TRUNCATE ${DESTINATION_SCHEMA}.openstack_raw_instance_type;

TRUNCATE ${DESTINATION_SCHEMA}.openstack_staging_event;
