INSERT INTO ${DESTINATION_SCHEMA}.acl_types(module_id, name, display)
SELECT
m.module_id,
'data' as name,
'Data' as display
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acl_types(module_id, name, display)
SELECT
m.module_id,
'feature' as name,
'Feature' as display
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acl_types(module_id, name, display)
SELECT
m.module_id,
'flag' as name,
'Flag' as display
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';
