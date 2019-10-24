SELECT
res.display_resource_name AS "resource",
xdcdb_res.resource_id AS "id",
min(orn.number_value) AS "available",
coalesce(sum(ar_req.resource_amount), 0) AS "requested",
coalesce(sum(ar_rec.resource_amount), NULL) AS "recommended",
coalesce(sum(ar_awd.resource_amount), 0) AS "awarded",
date_trunc('quarter', o.submission_end_date)::timestamp + interval '3 months' AS "start_alloc_date",
date_trunc('quarter', o.submission_end_date)::timestamp + interval '6 months' - interval '1 second' AS "end_alloc_date",
extract(epoch from (date_trunc('quarter', o.submission_end_date)::timestamp + interval '3 months') at time zone 'America/New_York') AS "start_time_ts",
extract(epoch from (date_trunc('quarter', o.submission_end_date)::timestamp + interval '6 months' - interval '1 second') at time zone 'America/New_York') AS "end_time_ts"
FROM "xras"."actions" AS ac
JOIN "xras"."requests" AS r ON r.request_id = ac.request_id
JOIN "xras"."opportunities" AS o ON o.opportunity_id = r.opportunity_id
JOIN "xras"."allocation_types" AS at ON at.allocation_type_id = o.allocation_type_id
JOIN "xras"."opportunity_resource_numbers" AS orn ON orn.opportunity_id = o.opportunity_id
JOIN "xras"."resources" AS res ON res.resource_id = orn.resource_id
left outer JOIN "xras"."action_resources" AS ar_req ON ar_req.action_id = ac.action_id and ar_req.resource_id = orn.resource_id and ar_req.resource_amount_type_id = 1
left outer JOIN "xras"."action_resources" AS ar_rec ON ar_rec.action_id = ac.action_id and ar_rec.resource_id = orn.resource_id and ar_rec.resource_amount_type_id = 3
left outer JOIN "xras"."action_resources" AS ar_awd ON ar_awd.action_id = ac.action_id and ar_awd.resource_id = orn.resource_id and ar_awd.resource_amount_type_id = 2
left outer JOIN "acct"."resources" AS xdcdb_res ON xdcdb_res.rdr_resource_id = res.resource_repository_key
WHERE o.allocations_process_id = 1
AND at.allocation_type_id in (500006, 500016, 500018)
AND ac.action_status_type_id != 500002
AND ac.action_type_id in (2,3)
AND res.resource_type_id = 500003
AND ac.is_deleted = false
AND orn.resource_number_type_id = 1
GROUP BY res.display_resource_name, xdcdb_res.resource_id, o.opportunity_id
