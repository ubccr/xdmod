SELECT SQL_NO_CACHE
:period_id AS `quarter_id`,
:year_value AS `year`,
:period_value AS `quarter`,
ra.id AS `resource_id`,
organization_id AS `organization_id`,
ra.end_alloc_date AS `alloc_date`,
aladj.conversion_factor AS `xd_su_conversion_factor`,
sum(ra.available) AS `available`,
sum(ra.requested) AS `requested`,
coalesce(sum(ra.recommended), NULL) AS `recommended`,
sum(ra.awarded) AS `awarded`
FROM `modw_ra`.`resource_allocations` AS ra
JOIN `modw`.`resourcefact` AS rf ON rf.id = ra.id
JOIN `modw`.`allocationadjustment` AS aladj ON aladj.site_resource_id = ra.id AND aladj.allocation_resource_id = 1546 AND ra.end_alloc_date BETWEEN aladj.start_date AND IFNULL(aladj.end_date, DATE('9999-01-01'))
WHERE ( ra.end_time_ts BETWEEN :period_start_ts AND :period_end_ts OR :period_end_ts BETWEEN ra.start_time_ts AND ra.end_time_ts )
GROUP BY quarter_id, year, quarter, organization_id, resource_id, alloc_date
