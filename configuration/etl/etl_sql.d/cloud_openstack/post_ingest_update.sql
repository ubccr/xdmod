-- Update destroy times of volume assets. This updates both volumes that are destoyed
-- in volume.delete.end events and also root volumes which are inferred from compute.instance.create
-- events.
CREATE TEMPORARY TABLE ${DESTINATION_SCHEMA}.tmp_volume_delete
(INDEX resource_id_openstack_resource_key (`resource_id`, `openstack_resource_id`))
AS
SELECT
    event_time_utc,
    openstack_resource_id,
    resource_id
FROM
    ${DESTINATION_SCHEMA}.openstack_raw_event
WHERE
    event_type = "volume.delete.end"//

UPDATE
    ${DESTINATION_SCHEMA}.asset AS a
LEFT JOIN
    ${DESTINATION_SCHEMA}.tmp_volume_delete AS raw
ON
    raw.resource_id = a.resource_id AND raw.openstack_resource_id = a.provider_identifier
SET
    a.destroy_time_ts = UNIX_TIMESTAMP(CONVERT_TZ(raw.event_time_utc,'+00:00', @@session.time_zone))//

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
    a.destroy_time_ts = staging.event_time_ts
WHERE
    staging.event_type_id = 4//

TRUNCATE ${DESTINATION_SCHEMA}.openstack_raw_event//

TRUNCATE ${DESTINATION_SCHEMA}.openstack_raw_instance_type//
