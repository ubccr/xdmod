-- Jobs Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'allocation'                                                 AS name,
                 'Allocation'                                                 AS display,
                 'A funded project that is allowed to run jobs on resources.' AS description,
                 'modw'                                                       AS schema_name,
                 'allocation'                                                 AS table_name,
                 'al'                                                         AS alias,
                 'account_id'                                                 AS id_column,
                 'long_name'                                                  AS name_column,
                 'short_name'                                                 AS shortname_column,
                 'order_id'                                                   AS order_id_column,
                 'allocation_id'                                              AS fk_column,
                 'GroupByAllocation'                                          AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'day'               AS name,
                 'Day'               AS display,
                 ''                  AS description,
                 'modw'              AS schema_name,
                 'days'              AS table_name,
                 'd'                 AS alias,
                 'id'                AS id_column,
                 'date(d.day_start)' AS name_column,
                 'date(d.day_start)' AS shortname_column,
                 'id'                AS order_id_column,
                 'day_id'            AS fk_column,
                 'GroupByDay'        AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'grant_type'                                    AS name,
                 'Grant Type'                                    AS display,
                 'A categorization of the projects/allocations.' AS description,
                 'modw'                                          AS schema_name,
                 'granttype'                                     AS table_name,
                 'gt'                                            AS alias,
                 'id'                                            AS id_column,
                 'name'                                          AS name_column,
                 'name'                                          AS shortname_column,
                 'name'                                          AS order_id_column,
                 'grant_type_id'                                 AS fk_column,
                 'GroupByGrantType'                              AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'institution'                                AS name,
                 'User Institution'                                AS display,
                 'Organizations that have users with allocations.' AS description,
                 'modw'                                            AS schema_name,
                 'organization'                                    AS table_name,
                 'o'                                               AS alias,
                 'id'                                              AS id_column,
                 'long_name'                                       AS name_column,
                 'name'                                            AS shortname_column,
                 'order_id'                                        AS order_id_column,
                 'organization_id'                                 AS fk_column,
                 'GroupByInstitution'                              AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'job_size'                                                                                     AS name,
                 'Job Size'                                                                                     AS display,
                 'A categorization of jobs into discrete groups based on the number of cores used by each job.' AS description,
                 'modw'                                                                                         AS schema_name,
                 'processor_buckets'                                                                            AS table_name,
                 'pb'                                                                                           AS alias,
                 'id'                                                                                           AS id_column,
                 'description'                                                                                  AS name_column,
                 'description'                                                                                  AS shortname_column,
                 'id'                                                                                           AS order_id_column,
                 'processorbucket_id'                                                                           AS fk_column,
                 'GroupByJobSize'                                                                               AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'job_wall_time'                                                                                          AS name,
                 'Job Wall Time'                                                                                          AS display,
                 'A categorization of jobs into discrete groups based on the total linear time each job took to execute.' AS description,
                 'modw'                                                                                                   AS schema_name,
                 'job_times'                                                                                              AS table_name,
                 'jt'                                                                                                     AS alias,
                 'id'                                                                                                     AS id_column,
                 'description'                                                                                            AS name_column,
                 'description'                                                                                            AS shortname_column,
                 'id'                                                                                                     AS order_id_column,
                 'jobtime_id'                                                                                             AS fk_column,
                 'GroupByJobTime'                                                                                         AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'month'                               AS name,
                 'Month'                               AS display,
                 ''                                    AS description,
                 'modw'                                AS schema_name,
                 'months'                              AS table_name,
                 'm'                                   AS alias,
                 'id'                                  AS id_column,
                 'date_format(m.month_start, "%Y-%m")' AS name_column,
                 'date_format(m.month_start, "%Y-%m")' AS shortname_column,
                 'id'                                  AS order_id_column,
                 'month_id'                            AS fk_column,
                 'GroupByMonth'                        AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'node_count'                                                         AS name,
                 'Node Count'                                                         AS display,
                 'A categorization of jobs into discrete groups based on node count.' AS description,
                 'modw'                                                               AS schema_name,
                 'nodecount'                                                          AS table_name,
                 'n'                                                                  AS alias,
                 'id'                                                                 AS id_column,
                 'nodes'                                                              AS name_column,
                 'nodes'                                                              AS shortname_column,
                 'id'                                                                 AS order_id_column,
                 'nodecount_id'                                                       AS fk_column,
                 'GroupByNodeCount'                                                   AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'none'        AS name,
                 'None'        AS display,
                 ''            AS description,
                 ''            AS schema_name,
                 ''            AS table_name,
                 ''            AS alias,
                 ''            AS id_column,
                 ''            AS name_column,
                 ''            AS shortname_column,
                 ''            AS order_id_column,
                 ''            AS fk_column,
                 'GroupByNone' AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'nsfdirectorate'          AS name,
                 'NSF Directorate'          AS display,
                 ''                         AS description,
                 'modw'                     AS schema_name,
                 'fieldofscience_hierarchy' AS table_name,
                 'fost'                     AS alias,
                 'directorate_id'           AS id_column,
                 'directorate_description'  AS name_column,
                 'directorate_abbrev'       AS shortname_column,
                 'directorate_description'  AS order_id_column,
                 'fos_id'                   AS fk_column,
                 'GroupByNSFDirectorate'    AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'nsfstatus'                           AS name,
                 'User NSF Status'                      AS display,
                 'Categorization of the users who ran ' AS description,
                 'modw'                                 AS schema_name,
                 'nsfstatuscode'                        AS table_name,
                 'ns'                                   AS alias,
                 'id'                                   AS id_column,
                 'name'                                 AS name_column,
                 'name'                                 AS shortname_column,
                 'name'                                 AS order_id_column,
                 'nsfstatuscode_id'                     AS fk_column,
                 'GroupByNSFStatus'                     AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'parent_science'                                                           AS name,
                 'Parent Science'                                                           AS display,
                 'The parent of the field of science indiciated on the allocation request.' AS description,
                 'modw'                                                                     AS schema_name,
                 'fieldofscience_hierarchy'                                                 AS table_name,
                 'fosm'                                                                     AS alias,
                 'parent_id'                                                                AS id_column,
                 'parent_description'                                                       AS name_column,
                 'parent_description'                                                       AS shortname_column,
                 'parent_description'                                                       AS order_id_column,
                 'fos_id'                                                                   AS fk_column,
                 'GroupByParentScience'                                                     AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'user'                                                                   AS name,
                 'User'                                                                   AS display,
                 'A person who is on a PIs allocation, hence able run jobs on resources.' AS description,
                 'modw'                                                                   AS schema_name,
                 'person'                                                                 AS table_name,
                 'p'                                                                      AS alias,
                 'id'                                                                     AS id_column,
                 'long_name'                                                              AS name_column,
                 'short_name'                                                             AS shortname_column,
                 'order_id'                                                               AS order_id_column,
                 'person_id'                                                              AS fk_column,
                 'GroupByPerson'                                                          AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'pi'                                                                                                                                         AS name,
                 'PI'                                                                                                                                         AS display,
                 'The principal investigator of a project has a valid allocation, which can be used by him/her or the members of the project to run jobs on.' AS description,
                 'modw'                                                                                                                                       AS schema_name,
                 'piperson'                                                                                                                                   AS table_name,
                 'pip'                                                                                                                                        AS alias,
                 'person_id'                                                                                                                                  AS id_column,
                 'long_name'                                                                                                                                  AS name_column,
                 'short_name'                                                                                                                                 AS shortname_column,
                 'order_id'                                                                                                                                   AS order_id_column,
                 'principalinvestigator_person_id'                                                                                                            AS fk_column,
                 'GroupByPI'                                                                                                                                  AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'pi_institution'                                AS name,
                 'PI Institution'                                AS display,
                 'Organizations that have PIs with allocations.' AS description,
                 'modw'                                          AS schema_name,
                 'organization'                                  AS table_name,
                 'pio'                                           AS alias,
                 'id'                                            AS id_column,
                 'long_name'                                     AS name_column,
                 'short_name'                                    AS shortname_column,
                 'order_id'                                      AS order_id_column,
                 'piperson_organization_id'                      AS fk_column,
                 'GroupByPIInstitution'                          AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'service_provider'                                           AS name,
                 'Service Provider'                                           AS display,
                 'A service provider is an institution that hosts resources.' AS description,
                 'modw'                                                       AS schema_name,
                 'serviceprovider'                                            AS table_name,
                 'sp'                                                         AS alias,
                 'organization_id'                                            AS id_column,
                 'short_name'                                                 AS name_column,
                 'short_name'                                                 AS shortname_column,
                 'order_id'                                                   AS order_id_column,
                 'organization_id'                                            AS fk_column,
                 'GroupByProvider'                                            AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'quarter'                                                              AS name,
                 'Quarter'                                                              AS display,
                 ''                                                                     AS description,
                 'modw'                                                                 AS schema_name,
                 'quarters'                                                             AS table_name,
                 'q'                                                                    AS alias,
                 'quarter_id'                                                           AS id_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS name_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS shortname_column,
                 'quarter_id'                                                           AS order_id_column,
                 'quarter_id'                                                           AS fk_column,
                 'GroupByQuarter'                                                       AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'queue'                                                        AS name,
                 'Queue'                                                        AS display,
                 'Queue pertains to the low level job queues on each resource.' AS description,
                 'modw'                                                         AS schema_name,
                 'queue'                                                        AS table_name,
                 'u'                                                            AS alias,
                 'id'                                                           AS id_column,
                 'id'                                                           AS name_column,
                 'id'                                                           AS shortname_column,
                 'id'                                                           AS order_id_column,
                 'queue_id'                                                     AS fk_column,
                 'GroupByQueue'                                                 AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'resource'                                      AS name,
                 'Resource'                                      AS display,
                 'A resource is a remote computer that can run ' AS description,
                 'modw'                                          AS schema_name,
                 'resourcefact'                                  AS table_name,
                 'rf'                                            AS alias,
                 'id'                                            AS id_column,
                 'code'                                          AS name_column,
                 'code'                                          AS shortname_column,
                 'code'                                          AS order_id_column,
                 'resource_id'                                   AS fk_column,
                 'GroupByResource'                               AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'resource_type'                                                     AS name,
                 'Resource Type'                                                     AS display,
                 'A categorization of resources into by their general capabilities.' AS description,
                 'modw'                                                              AS schema_name,
                 'resourcetype'                                                      AS table_name,
                 'rt'                                                                AS alias,
                 'id'                                                                AS id_column,
                 'description'                                                       AS name_column,
                 'abbrev'                                                            AS shortname_column,
                 'description'                                                       AS order_id_column,
                 'resourcetype_id'                                                   AS fk_column,
                 'GroupByResourceType'                                               AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'science'                                                   AS name,
                 'Field Of Science'                                          AS display,
                 'The field of science indicated on the allocation request.' AS description,
                 'modw'                                                      AS schema_name,
                 'fieldofscience_hierarchy'                                  AS table_name,
                 'fosb'                                                      AS alias,
                 'id'                                                        AS id_column,
                 'description'                                               AS name_column,
                 'description'                                               AS shortname_column,
                 'order_id'                                                  AS order_id_column,
                 'order_id'                                                  AS fk_column,
                 'GroupByScience'                                            AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'username'                                           AS name,
                 'System Username'                                    AS display,
                 'The specific system username of the users who ran ' AS description,
                 'modw'                                               AS schema_name,
                 'systemaccount'                                      AS table_name,
                 'sa'                                                 AS alias,
                 'username'                                           AS id_column,
                 'username'                                           AS name_column,
                 'username'                                           AS shortname_column,
                 'username'                                           AS order_id_column,
                 'systemaccount_id'                                   AS fk_column,
                 'GroupByUsername'                                    AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod'
         ) inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, NAME, display, description, SCHEMA_NAME, TABLE_NAME, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'year'                             AS name,
                 'Year'                             AS display,
                 ''                                 AS description,
                 'modw'                             AS schema_name,
                 'years'                            AS table_name,
                 'y'                                AS alias,
                 'year_id'                          AS id_column,
                 'date_format(y.year_start, "%Y")' AS name_column,
                 'date_format(y.year_start, "%Y")' AS shortname_column,
                 'year_id'                          AS order_id_column,
                 'year_id'                          AS fk_column,
                 'GroupByYear'                      AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'gateway'                                                                  AS name,
                 'Gateway'                                                                  AS display,
                 'A science gateway is a portal set up to aid submiting jobs to resources.' AS description,
                 'modw'                                                                     AS schema_name,
                 'gatewayperson'                                                            AS table_name,
                 'gp'                                                                       AS alias,
                 'person_id'                                                                AS id_column,
                 'long_name'                                                                AS name_column,
                 'short_name'                                                               AS shortname_column,
                 'order_id'                                                                 AS order_id_column,
                 'person_id'                                                                AS fk_column,
                 'GroupByGateway'                                                           AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
-- End Jobs Group Bys

-- Accounts Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'day'                AS name,
                 'Day'                AS display,
                 ''                   AS description,
                 'modw'               AS schema_name,
                 'days'               AS table_name,
                 'd'                  AS alias,
                 'id'                 AS id_column,
                 'date(d.day_start)' AS name_column,
                 'date(d.day_start)' AS shortname_column,
                 'id'                 AS order_id_column,
                 'day_id'             AS fk_column,
                 'GroupByDay'         AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'month'                               AS name,
                 'Month'                               AS display,
                 ''                                    AS description,
                 'modw'                                AS schema_name,
                 'months'                              AS table_name,
                 'm'                                   AS alias,
                 'id'                                  AS id_column,
                 'date_format(m.month_start, "%Y-%m")' AS name_column,
                 'date_format(m.month_start, "%Y-%m")' AS shortname_column,
                 'id'                                  AS order_id_column,
                 'month_id'                            AS fk_column,
                 'GroupByMonth'                        AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'none'        AS name,
                 'None'        AS display,
                 ''            AS description,
                 ''            AS schema_name,
                 ''            AS table_name,
                 ''            AS alias,
                 ''            AS id_column,
                 ''            AS name_column,
                 ''            AS shortname_column,
                 ''            AS order_id_column,
                 ''            AS fk_column,
                 'GroupByNone' AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'quarter'                                                              AS name,
                 'Quarter'                                                              AS display,
                 ''                                                                     AS description,
                 'modw'                                                                 AS schema_name,
                 'quarters'                                                             AS table_name,
                 'q'                                                                    AS alias,
                 'quarter_id'                                                           AS id_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS name_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS shortname_column,
                 'quarter_id'                                                           AS order_id_column,
                 'quarter_id'                                                           AS fk_column,
                 'GroupByQuarter'                                                       AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'resource'                                      AS name,
                 'Resource'                                      AS display,
                 'A resource is a remote computer that can run ' AS description,
                 'modw'                                          AS schema_name,
                 'resourcefact'                                  AS table_name,
                 'rf'                                            AS alias,
                 'id'                                            AS id_column,
                 'code'                                          AS name_column,
                 'code'                                          AS shortname_column,
                 'code'                                          AS order_id_column,
                 'resource_id'                                   AS fk_column,
                 'GroupByResource'                               AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'resource_type'                                                     AS name,
                 'Resource Type'                                                     AS display,
                 'A categorization of resources into by their general capabilities.' AS description,
                 'modw'                                                              AS schema_name,
                 'resourcetype'                                                      AS table_name,
                 'rt'                                                                AS alias,
                 'id'                                                                AS id_column,
                 'description'                                                       AS name_column,
                 'abbrev'                                                            AS shortname_column,
                 'description'                                                       AS order_id_column,
                 'resourcetype_id'                                                   AS fk_column,
                 'GroupByResourceType'                                               AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'year'                             AS name,
                 'Year'                             AS display,
                 ''                                 AS description,
                 'modw'                             AS schema_name,
                 'years'                            AS table_name,
                 'y'                                AS alias,
                 'year_id'                          AS id_column,
                 'date_format(y.year_start, "%Y")' AS name_column,
                 'date_format(y.year_start, "%Y")' AS shortname_column,
                 'year_id'                          AS order_id_column,
                 'year_id'                          AS fk_column,
                 'GroupByYear'                      AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

-- End Accounts Group Bys

-- Allocations Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'allocation'                                                 AS name,
                 'Allocation'                                                 AS display,
                 'A funded project that is allowed to run jobs on resources.' AS description,
                 'modw'                                                       AS schema_name,
                 'allocation'                                                 AS table_name,
                 'al'                                                         AS alias,
                 'account_id'                                                 AS id_column,
                 'long_name'                                                  AS name_column,
                 'short_name'                                                 AS shortname_column,
                 'order_id'                                                   AS order_id_column,
                 'allocation_id'                                              AS fk_column,
                 'GroupByAllocation'                                          AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'allocation_type'                                               AS name,
                 'Allocation Type'                                               AS display,
                 'The type of funded projects allowed to run jobs on resources.' AS description,
                 'modw'                                                          AS schema_name,
                 'transactiontype'                                               AS table_name,
                 'tt'                                                            AS alias,
                 'id'                                                            AS id_column,
                 'name'                                                          AS name_column,
                 'name'                                                          AS shortname_column,
                 'name'                                                          AS order_id_column,
                 'allocation_type_id'                                            AS fk_column,
                 'GroupByAllocationType'                                         AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'board_type'                                   AS name,
                 'Board Type'                                   AS display,
                 'A board type pertaining to the POPS meeting.' AS description,
                 'modw'                                         AS schema_name,
                 'boardtype'                                    AS table_name,
                 'bt'                                           AS alias,
                 'id'                                           AS id_column,
                 'description'                                  AS name_column,
                 'description'                                  AS shortname_column,
                 'description'                                  AS order_id_column,
                 'boardtype_id'                                 AS fk_column,
                 'GroupByBoardType'                             AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'day'               AS name,
                 'Day'               AS display,
                 ''                  AS description,
                 'modw'              AS schema_name,
                 'days'              AS table_name,
                 'd'                 AS alias,
                 'id'                AS id_column,
                 'date(d.day_start)' AS name_column,
                 'date(d.day_start)' AS shortname_column,
                 'id'                AS order_id_column,
                 'day_id'            AS fk_column,
                 'GroupByDay'        AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'month'                               AS name,
                 'Month'                               AS display,
                 ''                                    AS description,
                 'modw'                                AS schema_name,
                 'months'                              AS table_name,
                 'm'                                   AS alias,
                 'id'                                  AS id_column,
                 'date_format(m.month_start, "%Y-%m")' AS name_column,
                 'date_format(m.month_start, "%Y-%m")' AS shortname_column,
                 'id'                                  AS order_id_column,
                 'month_id'                            AS fk_column,
                 'GroupByMonth'                        AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'none'        AS name,
                 'None'        AS display,
                 ''            AS description,
                 ''            AS schema_name,
                 ''            AS table_name,
                 ''            AS alias,
                 ''            AS id_column,
                 ''            AS name_column,
                 ''            AS shortname_column,
                 ''            AS order_id_column,
                 ''            AS fk_column,
                 'GroupByNone' AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'nsfdirectorate'                                                                    AS name,
                 'NSF Directorate'                                                                   AS display,
                 'The NSF directorate of the field of science indiciated on the allocation request.' AS description,
                 'modw'                                                                              AS schema_name,
                 'fieldofscience_hierarhy'                                                           AS table_name,
                 'fos'                                                                               AS alias,
                 'directorate_id'                                                                    AS id_column,
                 'directorate_description'                                                           AS name_column,
                 'directorate_abbrev'                                                                AS shortname_column,
                 'directorate_description'                                                           AS order_id_column,
                 'directorate_description'                                                           AS fk_column,
                 'GroupByNSFDirectorate'                                                             AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'parent_science'                                                           AS name,
                 'Parent Science'                                                           AS display,
                 'The parent of the field of science indiciated on the allocation request.' AS description,
                 'modw'                                                                     AS schema_name,
                 'fieldofscience_hierarchy'                                                 AS table_name,
                 'fosm'                                                                     AS alias,
                 'parent_id'                                                                AS id_column,
                 'parent_description'                                                       AS name_column,
                 'parent_description'                                                       AS shortname_column,
                 'parent_description'                                                       AS order_id_column,
                 'fos_id'                                                                   AS fk_column,
                 'GroupByParentScience'                                                     AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'pi'                                                                                                                                         AS name,
                 'PI'                                                                                                                                         AS display,
                 'The principal investigator of a project has a valid allocation, which can be used by him/her or the members of the project to run jobs on.' AS description,
                 'modw'                                                                                                                                       AS schema_name,
                 'piperson'                                                                                                                                   AS table_name,
                 'pip'                                                                                                                                        AS alias,
                 'person_id'                                                                                                                                  AS id_column,
                 'long_name'                                                                                                                                  AS name_column,
                 'short_name'                                                                                                                                 AS shortname_column,
                 'order_id'                                                                                                                                   AS order_id_column,
                 'principalinvestigator_person_id'                                                                                                            AS fk_column,
                 'GroupByPI'                                                                                                                                  AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'quarter'                                                              AS name,
                 'Quarter'                                                              AS display,
                 ''                                                                     AS description,
                 'modw'                                                                 AS schema_name,
                 'quarters'                                                             AS table_name,
                 'q'                                                                    AS alias,
                 'quarter_id'                                                           AS id_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS name_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS shortname_column,
                 'quarter_id'                                                           AS order_id_column,
                 'quarter_id'                                                           AS fk_column,
                 'GroupByQuarter'                                                       AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'resource'                                      AS name,
                 'Resource'                                      AS display,
                 'A resource is a remote computer that can run ' AS description,
                 'modw'                                          AS schema_name,
                 'resourcefact'                                  AS table_name,
                 'rf'                                            AS alias,
                 'id'                                            AS id_column,
                 'code'                                          AS name_column,
                 'code'                                          AS shortname_column,
                 'code'                                          AS order_id_column,
                 'resource_id'                                   AS fk_column,
                 'GroupByResource'                               AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'resource_type'                                                     AS name,
                 'Resource Type'                                                     AS display,
                 'A categorization of resources into by their general capabilities.' AS description,
                 'modw'                                                              AS schema_name,
                 'resourcetype'                                                      AS table_name,
                 'rt'                                                                AS alias,
                 'id'                                                                AS id_column,
                 'description'                                                       AS name_column,
                 'abbrev'                                                            AS shortname_column,
                 'description'                                                       AS order_id_column,
                 'resourcetype_id'                                                   AS fk_column,
                 'GroupByResourceType'                                               AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'fieldofscience'                                            AS name,
                 'Field of Science'                                          AS display,
                 'The field of science indicated on the allocation request.' AS description,
                 'modw'                                                      AS schema_name,
                 'fieldofscience_hierarchy'                                  AS table_name,
                 'fos'                                                       AS alias,
                 'id'                                                        AS id_column,
                 'description'                                               AS name_column,
                 'description'                                               AS shortname_column,
                 'order_id'                                                  AS order_id_column,
                 'fos_id'                                                    AS fk_column,
                 'GroupByScience'                                            AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'year'                             AS name,
                 'Year'                             AS display,
                 ''                                 AS description,
                 'modw'                             AS schema_name,
                 'years'                            AS table_name,
                 'y'                                AS alias,
                 'year_id'                          AS id_column,
                 'date_format(y.year_start, "%Y")' AS name_column,
                 'date_format(y.year_start, "%Y")' AS shortname_column,
                 'year_id'                          AS order_id_column,
                 'year_id'                          AS fk_column,
                 'GroupByYear'                      AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
-- End Allocations Group Bys

-- Grants Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'award_num'                    AS name,
                 'Award Number'                 AS display,
                 'The number given to a grant.' AS description,
                 'modw_pops'                    AS schema_name,
                 'prop_mtg_supp_grant'          AS table_name,
                 'fa'                           AS alias,
                 'award_num'                    AS id_column,
                 'award_num'                    AS name_column,
                 'award_num'                    AS shortname_column,
                 'award_num'                    AS order_id_column,
                 'award_num'                    AS fk_column,
                 'GroupByAwardNumber'           AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'day'               AS name,
                 'Day'               AS display,
                 ''                  AS description,
                 'modw'              AS schema_name,
                 'days'              AS table_name,
                 'd'                 AS alias,
                 'id'                AS id_column,
                 'date(d.day_start)' AS name_column,
                 'date(d.day_start)' AS shortname_column,
                 'id'                AS order_id_column,
                 'day_id'            AS fk_column,
                 'GroupByDay'        AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'month'                               AS name,
                 'Month'                               AS display,
                 ''                                    AS description,
                 'modw'                                AS schema_name,
                 'months'                              AS table_name,
                 'm'                                   AS alias,
                 'id'                                  AS id_column,
                 'date_format(m.month_start, "%Y-%m")' AS name_column,
                 'date_format(m.month_start, "%Y-%m")' AS shortname_column,
                 'id'                                  AS order_id_column,
                 'month_id'                            AS fk_column,
                 'GroupByMonth'                        AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'none'        AS name,
                 'None'        AS display,
                 ''            AS description,
                 ''            AS schema_name,
                 ''            AS table_name,
                 ''            AS alias,
                 ''            AS id_column,
                 ''            AS name_column,
                 ''            AS shortname_column,
                 ''            AS order_id_column,
                 ''            AS fk_column,
                 'GroupByNone' AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'quarter'                                                              AS name,
                 'Quarter'                                                              AS display,
                 ''                                                                     AS description,
                 'modw'                                                                 AS schema_name,
                 'quarters'                                                             AS table_name,
                 'q'                                                                    AS alias,
                 'quarter_id'                                                           AS id_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS name_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS shortname_column,
                 'quarter_id'                                                           AS order_id_column,
                 'quarter_id'                                                           AS fk_column,
                 'GroupByQuarter'                                                       AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'year'                             AS name,
                 'Year'                             AS display,
                 ''                                 AS description,
                 'modw'                             AS schema_name,
                 'years'                            AS table_name,
                 'y'                                AS alias,
                 'year_id'                          AS id_column,
                 'date_format(y.year_start, "%Y")' AS name_column,
                 'date_format(y.year_start, "%Y")' AS shortname_column,
                 'year_id'                          AS order_id_column,
                 'year_id'                          AS fk_column,
                 'GroupByYear'                      AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'funding_agency'                                         AS name,
                 'Funding Agency'                                         AS display,
                 'A funding agency is an organization that funds grants.' AS description,
                 'modw_pops'                                              AS schema_name,
                 'funding_agency'                                         AS table_name,
                 'fa'                                                     AS alias,
                 'org_id'                                                 AS id_column,
                 'long_name'                                              AS name_column,
                 'short_name'                                             AS shortname_column,
                 'short_name'                                             AS order_id_column,
                 'org_id'                                                 AS fk_column,
                 'GroupByFundingAgency'                                   AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'nsfdirectorate'          AS name,
                 'NSF Directorate'          AS display,
                 ''                         AS description,
                 'modw'                     AS schema_name,
                 'fieldofscience_hierarchy' AS table_name,
                 'fost'                     AS alias,
                 'directorate_id'           AS id_column,
                 'directorate_description'  AS name_column,
                 'directorate_abbrev'       AS shortname_column,
                 'directorate_description'  AS order_id_column,
                 'fos_id'                   AS fk_column,
                 'GroupByNSFDirectorate'    AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'parent_science'                                                           AS name,
                 'Parent Science'                                                           AS display,
                 'The parent of the field of science indiciated on the allocation request.' AS description,
                 'modw'                                                                     AS schema_name,
                 'fieldofscience_hierarchy'                                                 AS table_name,
                 'fosm'                                                                     AS alias,
                 'parent_id'                                                                AS id_column,
                 'parent_description'                                                       AS name_column,
                 'parent_description'                                                       AS shortname_column,
                 'parent_description'                                                       AS order_id_column,
                 'fos_id'                                                                   AS fk_column,
                 'GroupByParentScience'                                                     AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'science'                                                   AS name,
                 'Field Of Science'                                          AS display,
                 'The field of science indicated on the allocation request.' AS description,
                 'modw'                                                      AS schema_name,
                 'fieldofscience_hierarchy'                                  AS table_name,
                 'fosb'                                                      AS alias,
                 'id'                                                        AS id_column,
                 'description'                                               AS name_column,
                 'description'                                               AS shortname_column,
                 'order_id'                                                  AS order_id_column,
                 'order_id'                                                  AS fk_column,
                 'GroupByScience'                                            AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
-- End Grants Group Bys

-- Performance Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'day'               AS name,
                 'Day'               AS display,
                 ''                  AS description,
                 'modw'              AS schema_name,
                 'days'              AS table_name,
                 'd'                 AS alias,
                 'id'                AS id_column,
                 'date(d.day_start)' AS name_column,
                 'date(d.day_start)' AS shortname_column,
                 'id'                AS order_id_column,
                 'day_id'            AS fk_column,
                 'GroupByDay'        AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'month'                               AS name,
                 'Month'                               AS display,
                 ''                                    AS description,
                 'modw'                                AS schema_name,
                 'months'                              AS table_name,
                 'm'                                   AS alias,
                 'id'                                  AS id_column,
                 'date_format(m.month_start, "%Y-%m")' AS name_column,
                 'date_format(m.month_start, "%Y-%m")' AS shortname_column,
                 'id'                                  AS order_id_column,
                 'month_id'                            AS fk_column,
                 'GroupByMonth'                        AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'none'        AS name,
                 'None'        AS display,
                 ''            AS description,
                 ''            AS schema_name,
                 ''            AS table_name,
                 ''            AS alias,
                 ''            AS id_column,
                 ''            AS name_column,
                 ''            AS shortname_column,
                 ''            AS order_id_column,
                 ''            AS fk_column,
                 'GroupByNone' AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'quarter'                                                              AS name,
                 'Quarter'                                                              AS display,
                 ''                                                                     AS description,
                 'modw'                                                                 AS schema_name,
                 'quarters'                                                             AS table_name,
                 'q'                                                                    AS alias,
                 'quarter_id'                                                           AS id_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS name_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS shortname_column,
                 'quarter_id'                                                           AS order_id_column,
                 'quarter_id'                                                           AS fk_column,
                 'GroupByQuarter'                                                       AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'year'                             AS name,
                 'Year'                             AS display,
                 ''                                 AS description,
                 'modw'                             AS schema_name,
                 'years'                            AS table_name,
                 'y'                                AS alias,
                 'year_id'                          AS id_column,
                 'date_format(y.year_start, "%Y")' AS name_column,
                 'date_format(y.year_start, "%Y")' AS shortname_column,
                 'year_id'                          AS order_id_column,
                 'year_id'                          AS fk_column,
                 'GroupByYear'                      AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'resource'                                      AS name,
                 'Resource'                                      AS display,
                 'A resource is a remote computer that can run ' AS description,
                 'modw'                                          AS schema_name,
                 'resourcefact'                                  AS table_name,
                 'rf'                                            AS alias,
                 'id'                                            AS id_column,
                 'code'                                          AS name_column,
                 'code'                                          AS shortname_column,
                 'code'                                          AS order_id_column,
                 'resource_id'                                   AS fk_column,
                 'GroupByResource'                               AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
-- End Performance Group Bys

-- Proposals Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'day'               AS name,
                 'Day'               AS display,
                 ''                  AS description,
                 'modw'              AS schema_name,
                 'days'              AS table_name,
                 'd'                 AS alias,
                 'id'                AS id_column,
                 'date(d.day_start)' AS name_column,
                 'date(d.day_start)' AS shortname_column,
                 'id'                AS order_id_column,
                 'day_id'            AS fk_column,
                 'GroupByDay'        AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'month'                               AS name,
                 'Month'                               AS display,
                 ''                                    AS description,
                 'modw'                                AS schema_name,
                 'months'                              AS table_name,
                 'm'                                   AS alias,
                 'id'                                  AS id_column,
                 'date_format(m.month_start, "%Y-%m")' AS name_column,
                 'date_format(m.month_start, "%Y-%m")' AS shortname_column,
                 'id'                                  AS order_id_column,
                 'month_id'                            AS fk_column,
                 'GroupByMonth'                        AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'none'        AS name,
                 'None'        AS display,
                 ''            AS description,
                 ''            AS schema_name,
                 ''            AS table_name,
                 ''            AS alias,
                 ''            AS id_column,
                 ''            AS name_column,
                 ''            AS shortname_column,
                 ''            AS order_id_column,
                 ''            AS fk_column,
                 'GroupByNone' AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'quarter'                                                              AS name,
                 'Quarter'                                                              AS display,
                 ''                                                                     AS description,
                 'modw'                                                                 AS schema_name,
                 'quarters'                                                             AS table_name,
                 'q'                                                                    AS alias,
                 'quarter_id'                                                           AS id_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS name_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS shortname_column,
                 'quarter_id'                                                           AS order_id_column,
                 'quarter_id'                                                           AS fk_column,
                 'GroupByQuarter'                                                       AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'year'                             AS name,
                 'Year'                             AS display,
                 ''                                 AS description,
                 'modw'                             AS schema_name,
                 'years'                            AS table_name,
                 'y'                                AS alias,
                 'year_id'                          AS id_column,
                 'date_format(y.year_start, "%Y")' AS name_column,
                 'date_format(y.year_start, "%Y")' AS shortname_column,
                 'year_id'                          AS order_id_column,
                 'year_id'                          AS fk_column,
                 'GroupByYear'                      AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'nsfdirectorate'          AS name,
                 'NSF Directorate'          AS display,
                 ''                         AS description,
                 'modw'                     AS schema_name,
                 'fieldofscience_hierarchy' AS table_name,
                 'fost'                     AS alias,
                 'directorate_id'           AS id_column,
                 'directorate_description'  AS name_column,
                 'directorate_abbrev'       AS shortname_column,
                 'directorate_description'  AS order_id_column,
                 'fos_id'                   AS fk_column,
                 'GroupByNSFDirectorate'    AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'parent_science'                                                           AS name,
                 'Parent Science'                                                           AS display,
                 'The parent of the field of science indiciated on the allocation request.' AS description,
                 'modw'                                                                     AS schema_name,
                 'fieldofscience_hierarchy'                                                 AS table_name,
                 'fosm'                                                                     AS alias,
                 'parent_id'                                                                AS id_column,
                 'parent_description'                                                       AS name_column,
                 'parent_description'                                                       AS shortname_column,
                 'parent_description'                                                       AS order_id_column,
                 'fos_id'                                                                   AS fk_column,
                 'GroupByParentScience'                                                     AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'science'                                                   AS name,
                 'Field Of Science'                                          AS display,
                 'The field of science indicated on the allocation request.' AS description,
                 'modw'                                                      AS schema_name,
                 'fieldofscience_hierarchy'                                  AS table_name,
                 'fosb'                                                      AS alias,
                 'id'                                                        AS id_column,
                 'description'                                               AS name_column,
                 'description'                                               AS shortname_column,
                 'order_id'                                                  AS order_id_column,
                 'order_id'                                                  AS fk_column,
                 'GroupByScience'                                            AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
-- End Proposals Group Bys

-- Requests Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'day'               AS name,
                 'Day'               AS display,
                 ''                  AS description,
                 'modw'              AS schema_name,
                 'days'              AS table_name,
                 'd'                 AS alias,
                 'id'                AS id_column,
                 'date(d.day_start)' AS name_column,
                 'date(d.day_start)' AS shortname_column,
                 'id'                AS order_id_column,
                 'day_id'            AS fk_column,
                 'GroupByDay'        AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'month'                               AS name,
                 'Month'                               AS display,
                 ''                                    AS description,
                 'modw'                                AS schema_name,
                 'months'                              AS table_name,
                 'm'                                   AS alias,
                 'id'                                  AS id_column,
                 'date_format(m.month_start, "%Y-%m")' AS name_column,
                 'date_format(m.month_start, "%Y-%m")' AS shortname_column,
                 'id'                                  AS order_id_column,
                 'month_id'                            AS fk_column,
                 'GroupByMonth'                        AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'none'        AS name,
                 'None'        AS display,
                 ''            AS description,
                 ''            AS schema_name,
                 ''            AS table_name,
                 ''            AS alias,
                 ''            AS id_column,
                 ''            AS name_column,
                 ''            AS shortname_column,
                 ''            AS order_id_column,
                 ''            AS fk_column,
                 'GroupByNone' AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'quarter'                                                              AS name,
                 'Quarter'                                                              AS display,
                 ''                                                                     AS description,
                 'modw'                                                                 AS schema_name,
                 'quarters'                                                             AS table_name,
                 'q'                                                                    AS alias,
                 'quarter_id'                                                           AS id_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS name_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS shortname_column,
                 'quarter_id'                                                           AS order_id_column,
                 'quarter_id'                                                           AS fk_column,
                 'GroupByQuarter'                                                       AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id
               AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'year'                             AS name,
                 'Year'                             AS display,
                 ''                                 AS description,
                 'modw'                             AS schema_name,
                 'years'                            AS table_name,
                 'y'                                AS alias,
                 'year_id'                          AS id_column,
                 'date_format(y.year_start, "%Y")' AS name_column,
                 'date_format(y.year_start, "%Y")' AS shortname_column,
                 'year_id'                          AS order_id_column,
                 'year_id'                          AS fk_column,
                 'GroupByYear'                      AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'nsfdirectorate'          AS name,
                 'NSF Directorate'          AS display,
                 ''                         AS description,
                 'modw'                     AS schema_name,
                 'fieldofscience_hierarchy' AS table_name,
                 'fost'                     AS alias,
                 'directorate_id'           AS id_column,
                 'directorate_description'  AS name_column,
                 'directorate_abbrev'       AS shortname_column,
                 'directorate_description'  AS order_id_column,
                 'fos_id'                   AS fk_column,
                 'GroupByNSFDirectorate'    AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'parent_science'                                                           AS name,
                 'Parent Science'                                                           AS display,
                 'The parent of the field of science indiciated on the allocation request.' AS description,
                 'modw'                                                                     AS schema_name,
                 'fieldofscience_hierarchy'                                                 AS table_name,
                 'fosm'                                                                     AS alias,
                 'parent_id'                                                                AS id_column,
                 'parent_description'                                                       AS name_column,
                 'parent_description'                                                       AS shortname_column,
                 'parent_description'                                                       AS order_id_column,
                 'fos_id'                                                                   AS fk_column,
                 'GroupByParentScience'                                                     AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'science'                                                   AS name,
                 'Field Of Science'                                          AS display,
                 'The field of science indicated on the allocation request.' AS description,
                 'modw'                                                      AS schema_name,
                 'fieldofscience_hierarchy'                                  AS table_name,
                 'fosb'                                                      AS alias,
                 'id'                                                        AS id_column,
                 'description'                                               AS name_column,
                 'description'                                               AS shortname_column,
                 'order_id'                                                  AS order_id_column,
                 'order_id'                                                  AS fk_column,
                 'GroupByScience'                                            AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
-- End Requests Group Bys

-- ResourceAllocations Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'none'        AS name,
                 'None'        AS display,
                 ''            AS description,
                 ''            AS schema_name,
                 ''            AS table_name,
                 ''            AS alias,
                 ''            AS id_column,
                 ''            AS name_column,
                 ''            AS shortname_column,
                 ''            AS order_id_column,
                 ''            AS fk_column,
                 'GroupByNone' AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'quarter'                                                              AS name,
                 'Quarter'                                                              AS display,
                 ''                                                                     AS description,
                 'modw'                                                                 AS schema_name,
                 'quarters'                                                             AS table_name,
                 'q'                                                                    AS alias,
                 'quarter_id'                                                           AS id_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS name_column,
                 'concat(year(q.quarter_start)," Q", ceil(month(q.quarter_start)/3))' AS shortname_column,
                 'quarter_id'                                                           AS order_id_column,
                 'quarter_id'                                                           AS fk_column,
                 'GroupByQuarter'                                                       AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'year'                             AS name,
                 'Year'                             AS display,
                 ''                                 AS description,
                 'modw'                             AS schema_name,
                 'years'                            AS table_name,
                 'y'                                AS alias,
                 'year_id'                          AS id_column,
                 'date_format(y.year_start, "%Y")' AS name_column,
                 'date_format(y.year_start, "%Y")' AS shortname_column,
                 'year_id'                          AS order_id_column,
                 'year_id'                          AS fk_column,
                 'GroupByYear'                      AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'service_provider'                                           AS name,
                 'Service Provider'                                           AS display,
                 'A service provider is an institution that hosts resources.' AS description,
                 'modw'                                                       AS schema_name,
                 'serviceprovider'                                            AS table_name,
                 'sp'                                                         AS alias,
                 'organization_id'                                            AS id_column,
                 'short_name'                                                 AS name_column,
                 'short_name'                                                 AS shortname_column,
                 'order_id'                                                   AS order_id_column,
                 'organization_id'                                            AS fk_column,
                 'GroupByProvider'                                            AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
    SELECT DISTINCT inc.*
    FROM (
             SELECT
                 m.module_id,
                 'resource'                                      AS name,
                 'Resource'                                      AS display,
                 'A resource is a remote computer that can run ' AS description,
                 'modw'                                          AS schema_name,
                 'resourcefact'                                  AS table_name,
                 'rf'                                            AS alias,
                 'id'                                            AS id_column,
                 'code'                                          AS name_column,
                 'code'                                          AS shortname_column,
                 'code'                                          AS order_id_column,
                 'resource_id'                                   AS fk_column,
                 'GroupByResource'                               AS class
             FROM ${DESTINATION_SCHEMA}.modules AS m
             WHERE m.name = 'xdmod') inc
        LEFT JOIN ${DESTINATION_SCHEMA}.group_bys cur
            ON cur.module_id = inc.module_id AND cur.name = inc.name AND
               cur.display = inc.display AND cur.description = inc.description
               AND cur.schema_name = inc.schema_name AND
               cur.table_name = inc.table_name AND cur.alias = inc.alias AND
               cur.id_column = inc.id_column AND
               cur.name_column = inc.name_column AND
               cur.shortname_column = inc.shortname_column AND
               cur.order_id_column = inc.order_id_column AND
               cur.fk_column = inc.fk_column AND cur.class = inc.class
    WHERE cur.group_by_id IS NULL;
-- End ResourceAllocations Group Bys
