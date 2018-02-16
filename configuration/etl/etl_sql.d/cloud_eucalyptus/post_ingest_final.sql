CREATE TABLE IF NOT EXISTS euca_events_transient
SELECT
    it.provider_identifier as instance,
    consolidate.event_id as start_event_id,
    consolidate.resource_id as resource_id,
    consolidate.instance_id as id,
    consolidate.start_event_type as start_event,
    consolidate.start_event_time as start_event_time,
    consolidate.end_event_type as end_event,
    -- Find the first stop event following a start event
    MIN(consolidate.end_event_time) as end_event_time,
    itt.instance_type as configuration,
    itt.instance_type_id,
    itt.num_cores as num_cores,
    itt.memory_mb as memory_mb,
    itt.disk_gb as disk_gb,
    itt.start_time AS config_start,
    UNIX_TIMESTAMP(consolidate.start_event_time) AS start_time_ts,
    UNIX_TIMESTAMP(consolidate.end_event_time) AS end_time_ts,
    UNIX_TIMESTAMP(consolidate.end_event_time) - UNIX_TIMESTAMP(consolidate.start_event_time) AS wallduration,
    YEAR(consolidate.start_event_time) * 100000 + DAYOFYEAR(consolidate.start_event_time) AS start_day_id,
    YEAR(consolidate.end_event_time) * 100000 + DAYOFYEAR(consolidate.end_event_time) AS end_day_id
FROM (
    -- Match up instance stop events that occur after a start event
    SELECT
		start_event.event_id,
        start_event.start_event_type,
        start_event.start_event_time,
        end_event.end_event_type,
        end_event.end_event_time,
        start_event.instance_id,
        start_event.resource_id
    FROM (
        -- Find all events where an instance is starting
        SELECT
			ev.event_id,
            ev.instance_id,
            ev.event_time_utc AS start_event_time,
            ev.resource_id,
            et.event_type AS start_event_type
        FROM event ev
		JOIN event_type et ON et.event_type_id = ev.event_type_id
        WHERE
            ev.event_type_id IN (2, 8)  -- START and RESUME
            AND ev.instance_id != 1  -- Skip unknowns
    ) AS start_event
    INNER JOIN (
        -- Find all events where an instance is stopping
        SELECT
            ev.instance_id,
            ev.event_time_utc AS end_event_time,
            ev.resource_id,
            et.event_type AS end_event_type
        FROM event ev
		JOIN event_type et ON et.event_type_id = ev.event_type_id
        WHERE
            ev.event_type_id IN (4, 6)  -- STOP and TERMINATE
            AND ev.instance_id != 1  -- Skip unknowns
    ) AS end_event ON end_event.instance_id = start_event.instance_id
        AND end_event.end_event_time > start_event.start_event_time
        AND end_event.resource_id = start_event.resource_id
) AS consolidate
JOIN instance it ON it.resource_id = consolidate.resource_id and consolidate.instance_id = it.instance_id
-- We join instance_data to ensure that we properly link events with the appropriate instance + configuration
JOIN instance_data itd on itd.event_id = consolidate.event_id and itd.resource_id = consolidate.resource_id
JOIN instance_type itt ON itt.resource_id = consolidate.resource_id and itt.instance_type_id = itd.instance_type_id
GROUP BY provider_identifier, start_event_type, consolidate.start_event_time;
