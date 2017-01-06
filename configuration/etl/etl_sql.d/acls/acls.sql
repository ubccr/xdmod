--
INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT
        m.module_id,
        (SELECT at.acl_type_id
         FROM ${DESTINATION_SCHEMA}.acl_types at
         WHERE at.name = 'flag') AS acl_type_id,
        'mgr'                    AS name,
        'Manager'                AS display,
        TRUE                     AS enabled
    FROM ${DESTINATION_SCHEMA}.modules m
    WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT
        m.module_id,
        (SELECT at.acl_type_id
         FROM ${DESTINATION_SCHEMA}.acl_types at
         WHERE at.name = 'flag') AS acl_type_id,
        'dev'                    AS name,
        'Developer'              AS display,
        TRUE                     AS enabled
    FROM ${DESTINATION_SCHEMA}.modules m
    WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT
        m.module_id,
        (SELECT at.acl_type_id
         FROM ${DESTINATION_SCHEMA}.acl_types at
         WHERE at.name = 'data') AS acl_type_id,
        'usr'                    AS name,
        'User'                   AS display,
        TRUE                     AS enabled
    FROM ${DESTINATION_SCHEMA}.modules m
    WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT
        m.module_id,
        (SELECT at.acl_type_id
         FROM ${DESTINATION_SCHEMA}.acl_types at
         WHERE at.name = 'data') AS acl_type_id,
        'po'                     AS name,
        'Program Officer'        AS display,
        TRUE                     AS enabled
    FROM ${DESTINATION_SCHEMA}.modules m
    WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT
        m.module_id,
        (SELECT at.acl_type_id
         FROM ${DESTINATION_SCHEMA}.acl_types at
         WHERE at.name = 'data') AS acl_type_id,
        'cs'                     AS name,
        'Center Staff'           AS display,
        TRUE                     AS enabled
    FROM ${DESTINATION_SCHEMA}.modules m
    WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT
        m.module_id,
        (SELECT at.acl_type_id
         FROM ${DESTINATION_SCHEMA}.acl_types at
         WHERE at.name = 'feature') AS acl_type_id,
        'cd'                        AS name,
        'Center Director'           AS display,
        TRUE                        AS enabled
    FROM ${DESTINATION_SCHEMA}.modules m
    WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT
        m.module_id,
        (SELECT at.acl_type_id
         FROM ${DESTINATION_SCHEMA}.acl_types at
         WHERE at.name = 'flag') AS acl_type_id,
        'pi'                     AS name,
        'Principal Investigator' AS display,
        TRUE                     AS enabled
    FROM ${DESTINATION_SCHEMA}.modules m
    WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT
        m.module_id,
        (SELECT at.acl_type_id
         FROM ${DESTINATION_SCHEMA}.acl_types at
         WHERE at.name = 'flag') AS acl_type_id,
        'cc'                     AS name,
        'Campus Champion'        AS display,
        TRUE                     AS enabled
    FROM ${DESTINATION_SCHEMA}.modules m
    WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acls (module_id, acl_type_id, name, display, enabled)
    SELECT
        m.module_id,
        (SELECT at.acl_type_id
         FROM ${DESTINATION_SCHEMA}.acl_types at
         WHERE at.name = 'flag') AS acl_type_id,
        'pub'                    AS name,
        'Public'                 AS display,
        TRUE                     AS enabled
    FROM ${DESTINATION_SCHEMA}.modules m
    WHERE m.name = 'xdmod';
