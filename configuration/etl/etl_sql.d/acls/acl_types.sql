DELETE FROM ${DESTINATION_SCHEMA}.acl_types
WHERE name = 'data' and display = 'Data' AND module_id IN (
    SELECT
        m.module_id
    FROM ${DESTINATION_SCHEMA}.modules m
    WHERE m.name = 'xdmod'
);

INSERT INTO ${DESTINATION_SCHEMA}.acl_types(module_id, name, display)
SELECT
m.module_id,
'data' as name,
'Data' as display
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';
-- //
DELETE FROM ${DESTINATION_SCHEMA}.acl_types
WHERE name = 'feature' and display = 'Feature' AND module_id IN (
    SELECT
        m.module_id
    FROM ${DESTINATION_SCHEMA}.modules m
    WHERE m.name = 'xdmod'
);

INSERT INTO ${DESTINATION_SCHEMA}.acl_types(module_id, name, display)
SELECT
m.module_id,
'feature' as name,
'Feature' as display
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';
-- //
DELETE FROM ${DESTINATION_SCHEMA}.acl_types
WHERE name = 'flag' and display = 'Flag' AND module_id IN (
    SELECT
        m.module_id
    FROM ${DESTINATION_SCHEMA}.modules m
    WHERE m.name = 'xdmod'
);

INSERT INTO ${DESTINATION_SCHEMA}.acl_types(module_id, name, display)
SELECT
m.module_id,
'flag' as name,
'Flag' as display
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';
