--
INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 (SELECT AT.acl_type_id
                  FROM ${DESTINATION_SCHEMA}.acl_types AT
                  WHERE AT.name = 'flag') AS acl_type_id,
                 'mgr'                    AS NAME,
                 'Manager'                AS display,
                 TRUE                     AS enabled
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acls cur
            ON inc.module_id = cur.module_id
               AND inc.acl_type_id = cur.acl_type_id
               AND inc.name = cur.name
               AND inc.display = cur.display
               AND inc.enabled = cur.enabled
    WHERE cur.acl_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 (SELECT AT.acl_type_id
                  FROM ${DESTINATION_SCHEMA}.acl_types AT
                  WHERE AT.name = 'flag') AS acl_type_id,
                 'dev'                    AS NAME,
                 'Developer'              AS display,
                 TRUE                     AS enabled
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acls cur
            ON inc.module_id = cur.module_id
               AND inc.acl_type_id = cur.acl_type_id
               AND inc.name = cur.name
               AND inc.display = cur.display
               AND inc.enabled = cur.enabled
    WHERE cur.acl_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 (SELECT AT.acl_type_id
                  FROM ${DESTINATION_SCHEMA}.acl_types AT
                  WHERE AT.name = 'data') AS acl_type_id,
                 'usr'                    AS NAME,
                 'User'                   AS display,
                 TRUE                     AS enabled
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acls cur
            ON inc.module_id = cur.module_id
               AND inc.acl_type_id = cur.acl_type_id
               AND inc.name = cur.name
               AND inc.display = cur.display
               AND inc.enabled = cur.enabled
    WHERE cur.acl_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 (SELECT AT.acl_type_id
                  FROM ${DESTINATION_SCHEMA}.acl_types AT
                  WHERE AT.name = 'data') AS acl_type_id,
                 'po'                     AS NAME,
                 'Program Officer'        AS display,
                 TRUE                     AS enabled
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acls cur
            ON inc.module_id = cur.module_id
               AND inc.acl_type_id = cur.acl_type_id
               AND inc.name = cur.name
               AND inc.display = cur.display
               AND inc.enabled = cur.enabled
    WHERE cur.acl_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 (SELECT AT.acl_type_id
                  FROM ${DESTINATION_SCHEMA}.acl_types AT
                  WHERE AT.name = 'data') AS acl_type_id,
                 'cs'                     AS NAME,
                 'Center Staff'           AS display,
                 TRUE                     AS enabled
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acls cur
            ON inc.module_id = cur.module_id
               AND inc.acl_type_id = cur.acl_type_id
               AND inc.name = cur.name
               AND inc.display = cur.display
               AND inc.enabled = cur.enabled
    WHERE cur.acl_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 (SELECT AT.acl_type_id
                  FROM ${DESTINATION_SCHEMA}.acl_types AT
                  WHERE AT.name = 'feature') AS acl_type_id,
                 'cd'                        AS NAME,
                 'Center Director'           AS display,
                 TRUE                        AS enabled
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acls cur
            ON inc.module_id = cur.module_id
               AND inc.acl_type_id = cur.acl_type_id
               AND inc.name = cur.name
               AND inc.display = cur.display
               AND inc.enabled = cur.enabled
    WHERE cur.acl_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 (SELECT AT.acl_type_id
                  FROM ${DESTINATION_SCHEMA}.acl_types AT
                  WHERE AT.name = 'flag') AS acl_type_id,
                 'pi'                     AS NAME,
                 'Principal Investigator' AS display,
                 TRUE                     AS enabled
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acls cur
            ON inc.module_id = cur.module_id
               AND inc.acl_type_id = cur.acl_type_id
               AND inc.name = cur.name
               AND inc.display = cur.display
               AND inc.enabled = cur.enabled
    WHERE cur.acl_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 (SELECT AT.acl_type_id
                  FROM ${DESTINATION_SCHEMA}.acl_types AT
                  WHERE AT.name = 'flag') AS acl_type_id,
                 'cc'                     AS NAME,
                 'Campus Champion'        AS display,
                 TRUE                     AS enabled
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acls cur
            ON inc.module_id = cur.module_id
               AND inc.acl_type_id = cur.acl_type_id
               AND inc.name = cur.name
               AND inc.display = cur.display
               AND inc.enabled = cur.enabled
    WHERE cur.acl_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT inc.*
    FROM (
             SELECT
                 m.module_id,
                 (SELECT AT.acl_type_id
                  FROM ${DESTINATION_SCHEMA}.acl_types AT
                  WHERE AT.name = 'flag') AS acl_type_id,
                 'pub'                    AS NAME,
                 'Public'                 AS display,
                 TRUE                     AS enabled
             FROM ${DESTINATION_SCHEMA}.modules m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.acls cur
            ON inc.module_id = cur.module_id
               AND inc.acl_type_id = cur.acl_type_id
               AND inc.name = cur.name
               AND inc.display = cur.display
               AND inc.enabled = cur.enabled
    WHERE cur.acl_id IS NULL;
