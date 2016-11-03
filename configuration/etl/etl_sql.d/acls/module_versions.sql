INSERT INTO ${DESTINATION_SCHEMA}.module_versions(module_id, version_major, version_minor, version_micro, version_patch, created_on, last_modified_on)
SELECT
m.module_id,
6,
5,
0,
'',
now(),
now()
FROM modules m
WHERE m.name = 'xdmod';
