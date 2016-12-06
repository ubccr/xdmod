-- Jobs Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'allocation' as name,
'Allocation' as display,
'A funded project that is allowed to run jobs on resources.' as description,
'modw' as schema_name,
'allocation' as table_name,
'al' as alias,
'account_id' as id_column,
'long_name' as name_column,
'short_name' as shortname_column,
'order_id' as order_id_column,
'allocation_id' as fk_column,
'GroupByAllocation' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'day' as name,
'Day' as display,
'' as description,
'modw' as schema_name,
'days' as table_name,
'd' as alias,
'day_id' as id_column,
'date(d.day_start)' as name_column,
'date(d.day_start)' as shortname_column,
'day_id' as order_id_column,
'day_id' as fk_column,
'GroupByDay' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'grant_type' as name,
'Grant Type' as display,
'A categorization of the projects/allocations.' as description,
'modw' as schema_name,
'granttype' as table_name,
'gt' as alias,
'id' as id_column,
'name' as name_column,
'name' as shortname_column,
'name' as order_id_column,
'grant_type_id' as fk_column
'GroupByGrantType' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'user_institution' as name,
'User Institution' as display,
'Organizations that have users with allocations.' as description,
'modw' as schema_name,
'organization' as table_name,
'o' as alias,
'id' as id_column,
'long_name' as name_column,
'name' as shortname_column,
'order_id' as order_id_column,
'organization_id' as fk_column,
'GroupByInstitution' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'job_size' as name,
'Job Size' as display,
'A categorization of jobs into discrete groups based on the number of cores used by each job.' as description,
'modw' as schema_name,
'processor_buckets' as table_name,
'pb' as alias,
'id' as id_column,
'description' as name_column,
'description' as shortname_column,
'id' as order_id_column,
'processorbucket_id' as fk_column,
'GroupByJobSize' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'job_wall_time' as name,
'Job Wall Time' as display,
'A categorization of jobs into discrete groups based on the total linear time each job took to execute.' as description,
'modw' as schema_name,
'job_times' as table_name,
'jt' as alias,
'id' as id_column,
'description' as name_column,
'description' as shortname_column,
'id' as order_id_column,
'jobtime_id' as fk_column,
'GroupByJobTime' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'month' as name,
'Month' as display,
'' as description,
'modw' as schema_name,
'months' as table_name,
'm' as alias,
'id' as id_column,
'date_format(m.month_start, "%Y-%m")' as name_column,
'date_format(m.month_start, "%Y-%m")' as shortname_column,
'id' as order_id_column,
'month_id' as fk_column,
'GroupByMonth' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'node_count' as name,
'Node Count' as display,
'A categorization of jobs into discrete groups based on node count.' as description,
'modw' as schema_name,
'nodecount' as table_name,
'n' as alias,
'id' as id_column,
'nodes' as name_column,
'nodes' as shortname_column,
'id' as order_id_column,
'nodecount_id' as fk_column,
'GroupByNodeCount' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'none' as name,
'None' as display,
'' as schema_name,
'' as table_name,
'' as alias,
'' as id_column,
'' as name_column,
'' as shortname_column,
'' as order_id_column,
'' as fk_column
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'nsf_directorate' as name,
'NSF Directorate' as display,
'' as description,
'modw' as schema_name,
'fieldofscience_hierarchy' as table_name,
'fost' as alias,
'directorate_id' as id_column,
'directorate_description' as name_column,
'directorate_abbrev' as shortname_column,
'directorate_description' as order_id_column,
'fos_id' as fk_column,
'GroupByNSFDirectorate' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'nsf_status' as name,
'User NSF Status' as display,
'Categorization of the users who ran ' as description,
'modw' as schema_name,
'nsfstatuscode' as table_name,
'ns' as alias,
'id' as id_column,
'name' as name_column,
'name' as shortname_column,
'name' as order_id_column,
'nsfstatuscode_id' as fk_column,
'GroupByNSFStatus' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'parent_science' as name,
'Parent Science' as display,
'' as description,
'modw' as schema_name,
'fieldofscience_hierarchy' as table_name,
'fosm' as alias,
'parent_id' as id_column,
'parent_description' as name_column,
'parent_description' as shortname_column,
'parent_description' as order_id_column,
'fos_id' as fk_column,
'GroupByParentScience' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'user' as name,
'User' as display,
'A person who is on a PIs allocation, hence able run jobs on resources.' as description,
'modw' as schema_name,
'person' as table_name,
'p' as alias,
'id' as id_column,
'long_name' as name_column,
'short_name' as shortname_column,
'order_id' as order_id_column,
'person_id' as fk_column,
'GroupByPerson' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'pi' as name,
'PI' as display,
'The principal investigator of a project has a valid allocation, which can be used by him/her or the members of the project to run jobs on.' as description,
'modw' as schema_name,
'piperson' as table_name,
'pip' as alias,
'person_id' as id_column,
'long_name' as name_column,
'short_name' as shortname_column,
'order_id' as order_id_column,
'principalinvestigator_person_id' as fk_column,
'GroupByPI' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'pi_institution' as name,
'PI Institution' as display,
'Organizations that have PIs with allocations.' as description,
'modw' as schema_name,
'organization' as table_name,
'pio' as alias,
'id' as id_column,
'long_name' as name_column,
'short_name' as shortname_column,
'order_id' as order_id_column,
'piperson_organization_id' as fk_column,
'GroupByPIInstitution' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'service_provider' as name,
'Service Provider' as display,
'A service provider is an institution that hosts resources.' as description,
'modw' as schema_name,
'serviceprovider' as table_name,
'sp' as alias,
'organization_id' as id_column,
'short_name' as name_column,
'short_name' as shortname_column,
'order_id' as order_id_column,
'organization_id' as fk_column,
'GroupByProvider' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'quarter' as name,
'Quarter' as display,
'' as description,
'modw' as schema_name,
'quarters' as table_name,
'q' as alias,
'quarter_id' as id_column,
'concat(year(gt.quarter_start)," Q", ceil(month(gt.quarter_start)/3))' as name_column,
'concat(year(gt.quarter_start)," Q", ceil(month(gt.quarter_start)/3))' as shortname_column,
'quarter_id' as order_id_column,
'quarter_id' as fk_column,
'GroupByQuarter' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'queue' as name,
'Queue' as display,
'Queue pertains to the low level job queues on each resource.' as description,
'modw' as schema_name,
'queue' as table_name,
'q' as alias,
'id' as id_column,
'id' as name_column,
'id' as shortname_column,
'id' as order_id_column,
'queue_id' as fk_column,
'GroupByQueue' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'resource' as name,
'Resource' as display,
'A resource is a remote computer that can run ' as description,
'modw' as schema_name,
'resourcefact' as table_name,
'rf' as alias,
'id' as id_column,
'code' as name_column,
'code' as shortname_column,
'code' as order_id_column,
'resource_id' as fk_column,
'GroupByResource' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'resource_type' as name,
'Resource Type' as display,
'A categorization of resources into by their general capabilities.' as description,
'modw' as schema_name,
'resourcetype' as table_name,
'rt' as alias,
'id' as id_column,
'description' as name_column,
'abbrev' as shortname_column,
'description' as order_id_column,
'resourcetype_id' as fk_column,
'GroupByResourceType' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'science' as name,
'Field Of Science' as display,
'' as description,
'modw' as schema_name,
'fieldofscience_hierarchy' as table_name,
'fosb' as alias,
'id' as id_column,
'description' as name_column,
'description' as shortname_column,
'order_id' as order_id_column,
'order_id' as fk_column,
'GroupByScience' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'username' as name,
'System Username' as display,
'The specific system username of the users who ran ' as description,
'modw' as schema_name,
'systemaccount' as table_name,
'sa' as alias,
'username' as id_column,
'username' as name_column,
'username' as shortname_column,
'username' as order_id_column,
'systemaccount_id' as fk_column,
'GroupByUsername' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'year' as name,
'Year' as display,
'' as description,
'modw' as schema_name,
'years' as table_name,
'y' as alias,
'year_id' as id_column,
'date_format(gt.year_start, "%Y")' as name_column,
'date_format(gt.year_start, "%Y")' as shortname_column,
'year_id' as order_id_column,
'year_id' as fk_column,
'GroupByYear' as class
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';
-- End Jobs Group Bys

-- Accounts Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'' as name,
'' as display,
'' as schema_name,
'' as table_name,
'' as alias,
'' as id_column,
'' as name_column,
'' as shortname_column,
'' as order_id_column,
'' as fk_column
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'' as name,
'' as display,
'' as schema_name,
'' as table_name,
'' as alias,
'' as id_column,
'' as name_column,
'' as shortname_column,
'' as order_id_column,
'' as fk_column
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'' as name,
'' as display,
'' as schema_name,
'' as table_name,
'' as alias,
'' as id_column,
'' as name_column,
'' as shortname_column,
'' as order_id_column,
'' as fk_column
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'' as name,
'' as display,
'' as schema_name,
'' as table_name,
'' as alias,
'' as id_column,
'' as name_column,
'' as shortname_column,
'' as order_id_column,
'' as fk_column
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'' as name,
'' as display,
'' as schema_name,
'' as table_name,
'' as alias,
'' as id_column,
'' as name_column,
'' as shortname_column,
'' as order_id_column,
'' as fk_column
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'' as name,
'' as display,
'' as schema_name,
'' as table_name,
'' as alias,
'' as id_column,
'' as name_column,
'' as shortname_column,
'' as order_id_column,
'' as fk_column
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';
-- End Accounts Group Bys

-- Allocations Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'' as name,
'' as display,
'' as schema_name,
'' as table_name,
'' as alias,
'' as id_column,
'' as name_column,
'' as shortname_column,
'' as order_id_column,
'' as fk_column
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';
-- End Allocations Group Bys

-- Grants Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'' as name,
'' as display,
'' as schema_name,
'' as table_name,
'' as alias,
'' as id_column,
'' as name_column,
'' as shortname_column,
'' as order_id_column,
'' as fk_column
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';
-- End Grants Group Bys

-- Performance Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'' as name,
'' as display,
'' as schema_name,
'' as table_name,
'' as alias,
'' as id_column,
'' as name_column,
'' as shortname_column,
'' as order_id_column,
'' as fk_column
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';
-- End Performance Group Bys

-- Proposals Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'' as name,
'' as display,
'' as schema_name,
'' as table_name,
'' as alias,
'' as id_column,
'' as name_column,
'' as shortname_column,
'' as order_id_column,
'' as fk_column
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';
-- End Proposals Group Bys

-- Requests Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'' as name,
'' as display,
'' as schema_name,
'' as table_name,
'' as alias,
'' as id_column,
'' as name_column,
'' as shortname_column,
'' as order_id_column,
'' as fk_column
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';
-- End Requests Group Bys

-- Requests Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'' as name,
'' as display,
'' as schema_name,
'' as table_name,
'' as alias,
'' as id_column,
'' as name_column,
'' as shortname_column,
'' as order_id_column,
'' as fk_column
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';
-- End Requests Group Bys

-- ResourceAllocations Group Bys
INSERT INTO ${DESTINATION_SCHEMA}.group_bys (module_id, name, display, description, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, class)
SELECT
m.module_id,
'' as name,
'' as display,
'' as schema_name,
'' as table_name,
'' as alias,
'' as id_column,
'' as name_column,
'' as shortname_column,
'' as order_id_column,
'' as fk_column
FROM ${DESTINATION_SCHEMA}.modules as m
WHERE m.name = 'xdmod';
-- End ResourceAllocations Group Bys
