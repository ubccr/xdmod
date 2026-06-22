UPDATE
	modw_cloud.instance_data AS itd
JOIN 
	modw_cloud.deleted_instance_types AS d ON itd.instance_type_id = d.instance_type_id
JOIN
	modw_cloud.event AS ev ON ev.event_id = itd.event_id
JOIN 
	modw_cloud.instance_type AS it ON it.instance_type = d.instance_type AND ev.event_time_ts BETWEEN it.start_time AND it.end_time AND ev.resource_id = it.resource_id
SET
	itd.instance_type_id = it.instance_type_id//

DROP TABLE modw_cloud.deleted_instance_types//
