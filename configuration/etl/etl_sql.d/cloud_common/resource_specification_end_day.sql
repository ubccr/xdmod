-- This sql statement inserts -1 values for the memory_mb and vcpus for a day that a compute node has been
-- removed from the most recently ingested resource specifications file. The -1 helps when setting start and
-- end times of a cpu and memory configuration for a compute node.
INSERT INTO modw_cloud.staging_resource_specifications (host_id, resource_id, memory_mb, vcpus, fact_date) SELECT
	rs.host_id,
	rs.resource_id,
	rs.memory_mb,
	rs.vcpus,
	rs.fact_date
FROM
	(SELECT
		rss1.host_id as host_id,
		rss1.resource_id AS resource_id,
		-1 AS memory_mb,
		-1 AS vcpus,
		rss1.fact_date AS fact_date
	FROM
		(SELECT
			rs.fact_date,
			rs.resource_id,
			rs2.host_id
		FROM
			modw_cloud.staging_resource_specifications AS rs
		LEFT JOIN
			(SELECT r.resource_id, r.host_id, r.fact_date FROM modw_cloud.staging_resource_specifications AS r GROUP BY r.resource_id, r.host_id) AS rs2 ON rs.resource_id = rs2.resource_id
		GROUP BY
			rs.resource_id,
			rs.fact_date,
			rs2.host_id
		HAVING
			MIN(rs2.fact_date) <= rs.fact_date) AS rss1
	LEFT JOIN
		 `modw_cloud`.`staging_resource_specifications` AS rss2
	ON
		rss1.resource_id = rss2.resource_id AND rss1.fact_date = rss2.fact_date AND rss1.host_id = rss2.host_id
	WHERE
		rss2.memory_mb IS NULL AND rss2.vcpus IS NULL
	GROUP BY
		rss1.resource_id, rss1.host_id, rss1.fact_date) as rs
LEFT JOIN
	(SELECT MAX(r.fact_date) AS fact_date, r.host_id, r.resource_id FROM modw_cloud.staging_resource_specifications AS r GROUP BY r.resource_id, r.host_id) AS r1
ON
	rs.resource_id = r1.resource_id and rs.host_id = r1.host_id
LEFT JOIN
	`modw_cloud`.`staging_resource_specifications` AS rs2
ON
	r1.resource_id = rs2.resource_id AND r1.host_id = rs2.host_id AND r1.fact_date = rs2.fact_date AND rs2.memory_mb != -1 AND rs2.vcpus != -1
WHERE
	rs2.host_id IS NOT NULL AND rs2.resource_id IS NOT NULL AND rs2.memory_mb IS NOT NULL AND rs2.vcpus IS NOT NULL AND rs2.fact_date IS NOT NULL
GROUP BY
	rs.resource_id, rs.host_id, rs.fact_date
//
