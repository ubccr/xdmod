UPDATE
	modw_cloud.raw_resource_specs
SET
	fact_date = CONCAT(DATE(fact_date), 'T', TIME_FORMAT(fact_date, "%T"), 'Z');
