INSERT INTO ${DESTINATION_SCHEMA}.realms(module_id, name, display, table_name, schema_name)
SELECT
m.module_id,
'accounts' as name,
'Accounts' as display,
'accountfact' as table_name,
'modw_aggregates' as schema_name
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.realms(module_id, name, display, table_name, schema_name)
SELECT
m.module_id,
'allocations' as name,
'Allocations' as display,
'allocationfact' as table_name,
'modw_aggregates' as schema_name
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.realms(module_id, name, display, table_name, schema_name)
SELECT
m.module_id,
'jobs' as name,
'Jobs' as display,
'jobfact' as table_name,
'modw_aggregates' as schema_name
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.realms(module_id, name, display, table_name, schema_name)
SELECT
m.module_id,
'requests' as name,
'Requests' as display,
'xrasrequestsfact' as table_name,
'modw_aggregates' as schema_name
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.realms(module_id, name, display, table_name, schema_name)
SELECT
m.module_id,
'resource_allocations' as name,
'Resource Allocations' as display,
'resourceallocationfact' as table_name,
'modw_aggregates' as schema_name
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.realms(module_id, name, display, table_name, schema_name)
SELECT
m.module_id,
'supremm' as name,
'SUPREMM' as display,
'' as table_name,
'' as schema_name
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';
