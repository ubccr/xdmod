CREATE TABLE IF NOT EXISTS modw_cloud.deleted_instance_types AS SELECT
	it.instance_type_id,
	it.instance_type
FROM
	modw_cloud.instance_type AS it
LEFT JOIN
	modw_cloud.instance_type_staging AS its ON it.instance_type_id = its.instance_type_id
WHERE
	its.instance_type IS NULL
AND
	it.instance_type != 'Unknown'//
