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

-- Truncate raw and staging tables once the data is no longer needed

TRUNCATE ${DESTINATION_SCHEMA}.openstack_raw_event;

TRUNCATE ${DESTINATION_SCHEMA}.openstack_raw_instance_type;

TRUNCATE ${DESTINATION_SCHEMA}.openstack_staging_event;
