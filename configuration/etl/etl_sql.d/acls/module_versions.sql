INSERT INTO ${DESTINATION_SCHEMA}.module_versions (module_id, version_major, version_minor, version_micro, version_patch, created_on, last_modified_on)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 6     version_major,
                 5     version_minor,
                 0     version_micro,
                 ''    version_patch,
                 now() created_on,
                 now() last_modified_on
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.module_versions cur
            ON cur.module_id = inc.module_id
               AND cur.version_major = inc.version_major
               AND cur.version_minor = inc.version_minor
               AND cur.version_patch = inc.version_patch
    WHERE cur.module_version_id IS NULL;
;

