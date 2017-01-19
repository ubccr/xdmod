INSERT INTO ${DESTINATION_SCHEMA}.realms(module_id, name, display, table_name, schema_name)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'accounts'        AS name,
                 'Accounts'        AS display,
                 'accountfact'     AS table_name,
                 'modw_aggregates' AS schema_name
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.realms cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.table_name = inc.table_name
               AND cur.schema_name = inc.schema_name
    WHERE
        cur.realm_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.realms(module_id, name, display, table_name, schema_name)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'allocations' as name,
                 'Allocations' as display,
                 'allocationfact' as table_name,
                 'modw_aggregates' as schema_name
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.realms cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.table_name = inc.table_name
               AND cur.schema_name = inc.schema_name
    WHERE
        cur.realm_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.realms(module_id, name, display, table_name, schema_name)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'jobs' as name,
                 'Jobs' as display,
                 'jobfact' as table_name,
                 'modw_aggregates' as schema_name
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.realms cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.table_name = inc.table_name
               AND cur.schema_name = inc.schema_name
    WHERE
        cur.realm_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.realms(module_id, name, display, table_name, schema_name)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'requests' as name,
                 'Requests' as display,
                 'xrasrequestsfact' as table_name,
                 'modw_aggregates' as schema_name
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.realms cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.table_name = inc.table_name
               AND cur.schema_name = inc.schema_name
    WHERE
        cur.realm_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.realms(module_id, name, display, table_name, schema_name)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'resource_allocations' as name,
                 'Resource Allocations' as display,
                 'resourceallocationfact' as table_name,
                 'modw_aggregates' as schema_name
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.realms cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.table_name = inc.table_name
               AND cur.schema_name = inc.schema_name
    WHERE
        cur.realm_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.realms(module_id, name, display, table_name, schema_name)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'supremm' as name,
                 'SUPREMM' as display,
                 '' as table_name,
                 '' as schema_name
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.realms cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name
               AND cur.display = inc.display
               AND cur.table_name = inc.table_name
               AND cur.schema_name = inc.schema_name
    WHERE
        cur.realm_id IS NULL;

