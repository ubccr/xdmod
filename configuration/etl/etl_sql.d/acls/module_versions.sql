INSERT INTO ${DESTINATION_SCHEMA}.module_versions(module_id, version_major, version_minor, version_micro, version_patch)
SELECT
m.module_id,
6,
5,
0,
''
FROM modules m
WHERE m.name = 'xdmod';
