INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'allocation' AS name, 
		'Allocation' AS display, 
		'modw' AS schema_name, 
		'allocation' AS table_name, 
		'al' AS alias, 
		'account_id' AS id_column, 
		'long_name' AS name_column, 
		'short_name' AS shortname_column, 
		'order_id' AS order_id_column, 
		'account_id' AS fk_column, 
		'A funded project that is allowed to run jobs on resources.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByAllocation' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'grant_type' AS name, 
		'Grant Type' AS display, 
		'modw' AS schema_name, 
		'account' AS table_name, 
		'acc' AS alias, 
		'id' AS id_column, 
		'name' AS name_column, 
		'name' AS shortname_column, 
		'name' AS order_id_column, 
		'id' AS fk_column, 
		'A categorization of the projects/allocations.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByGrantType' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'institution' AS name, 
		'User Institution' AS display, 
		'modw' AS schema_name, 
		'organization' AS table_name, 
		'o' AS alias, 
		'id' AS id_column, 
		'long_name' AS name_column, 
		'short_name' AS shortname_column, 
		'order_id' AS order_id_column, 
		'id' AS fk_column, 
		'Organizations that have users with allocations.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByInstitution' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'jobsize' AS name, 
		'Job Size' AS display, 
		'modw' AS schema_name, 
		'processor_buckets' AS table_name, 
		'pb' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'A categorization of jobs into discrete groups based on the number of cores used by each job.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByJobSize' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'jobwalltime' AS name, 
		'Job Wall Time' AS display, 
		'modw' AS schema_name, 
		'job_times' AS table_name, 
		'jt' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'A categorization of jobs into discrete groups based on the total linear time each job took to execute.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByJobTime' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'nsfdirectorate' AS name, 
		'NSF Directorate' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'directorate_id' AS id_column, 
		'directorate_description' AS name_column, 
		'directorate_abbrev' AS shortname_column, 
		'directorate_description' AS order_id_column, 
		'directorate_id' AS fk_column, 
		'The NSF directorate of the field of science indiciated on the allocation request pertaining to the running jobs.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByNSFDirectorate' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'nsfstatus' AS name, 
		'User NSF Status' AS display, 
		'modw' AS schema_name, 
		'nsfstatuscode' AS table_name, 
		'ns' AS alias, 
		'id' AS id_column, 
		'name' AS name_column, 
		'name' AS shortname_column, 
		'name' AS order_id_column, 
		'id' AS fk_column, 
		'Categorization of the users who ran jobs.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByNSFStatus' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'nodecount' AS name, 
		'Node Count' AS display, 
		'modw' AS schema_name, 
		'nodecount' AS table_name, 
		'n' AS alias, 
		'id' AS id_column, 
		'nodes' AS name_column, 
		'nodes' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'A categorization of jobs into discrete groups based on node count.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByNodeCount' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'pi' AS name, 
		'PI' AS display, 
		'modw' AS schema_name, 
		'piperson' AS table_name, 
		'pip' AS alias, 
		'person_id' AS id_column, 
		'long_name' AS name_column, 
		'short_name' AS shortname_column, 
		'order_id' AS order_id_column, 
		'person_id' AS fk_column, 
		'The principal investigator of a project has a valid allocation, which can be used by him/her or the members of the project to run jobs on.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByPI' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'pi_institution' AS name, 
		'PI Institution' AS display, 
		'modw' AS schema_name, 
		'organization' AS table_name, 
		'o' AS alias, 
		'id' AS id_column, 
		'long_name' AS name_column, 
		'short_name' AS shortname_column, 
		'order_id' AS order_id_column, 
		'id' AS fk_column, 
		'Organizations that have PIs with allocations.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByPIInstitution' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'parentscience' AS name, 
		'Parent Science' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'parent_id' AS id_column, 
		'parent_description' AS name_column, 
		'parent_description' AS shortname_column, 
		'parent_description' AS order_id_column, 
		'parent_id' AS fk_column, 
		'The parent of the field of science indiciated on the allocation request pertaining to the running jobs.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByParentScience' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'person' AS name, 
		'User' AS display, 
		'modw' AS schema_name, 
		'person' AS table_name, 
		'p' AS alias, 
		'id' AS id_column, 
		'long_name' AS name_column, 
		'short_name' AS shortname_column, 
		'order_id' AS order_id_column, 
		'id' AS fk_column, 
		'A person who is on a PIs allocation, hence able run jobs on resources.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByPerson' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'provider' AS name, 
		'Service Provider' AS display, 
		'modw' AS schema_name, 
		'serviceprovider' AS table_name, 
		'sp' AS alias, 
		'organization_id' AS id_column, 
		'short_name' AS name_column, 
		'short_name' AS shortname_column, 
		'order_id' AS order_id_column, 
		'organization_id' AS fk_column, 
		'A service provider is an institution that hosts resources.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByProvider' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'queue' AS name, 
		'Queue' AS display, 
		'modw' AS schema_name, 
		'queue' AS table_name, 
		'q' AS alias, 
		'id' AS id_column, 
		'id' AS name_column, 
		'id' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'Queue pertains to the low level job queues on each resource.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByQueue' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'resource' AS name, 
		'Resource' AS display, 
		'modw' AS schema_name, 
		'resourcefact' AS table_name, 
		'rf' AS alias, 
		'id' AS id_column, 
		'code' AS name_column, 
		'code' AS shortname_column, 
		'code' AS order_id_column, 
		'id' AS fk_column, 
		'A resource is a remote computer that can run jobs.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByResource' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'resource_type' AS name, 
		'Resource Type' AS display, 
		'modw' AS schema_name, 
		'resourcetype' AS table_name, 
		'rt' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'abbrev' AS shortname_column, 
		'description' AS order_id_column, 
		'id' AS fk_column, 
		'A categorization of resources into by their general capabilities.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByResourceType' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'fieldofscience' AS name, 
		'Field of Science' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'order_id' AS order_id_column, 
		'id' AS fk_column, 
		'The field of science indicated on the allocation request pertaining to the running jobs.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByScience' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'username' AS name, 
		'System Username' AS display, 
		'modw' AS schema_name, 
		'systemaccount' AS table_name, 
		'sa' AS alias, 
		'username' AS id_column, 
		'username' AS name_column, 
		'username' AS shortname_column, 
		'username' AS order_id_column, 
		'username' AS fk_column, 
		'The specific system username of the users who ran jobs.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByUsername' AS class 
FROM modules m, realms r 
WHERE m.name = 'xdmod' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'resource' AS name, 
		'Resource' AS display, 
		'modw' AS schema_name, 
		'resourcefact' AS table_name, 
		'rf' AS alias, 
		'id' AS id_column, 
		'code' AS name_column, 
		'code' AS shortname_column, 
		'code' AS order_id_column, 
		'id' AS fk_column, 
		'A resource is a remote computer that can run jobs.' as description, 
		'DataWarehouse/Query/Accounts/GroupBys/GroupByResource' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Accounts')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'resource_type' AS name, 
		'Resource Type' AS display, 
		'modw' AS schema_name, 
		'resourcetype' AS table_name, 
		'rt' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'abbrev' AS shortname_column, 
		'description' AS order_id_column, 
		'id' AS fk_column, 
		'A categorization of resources into by their general capabilities.' as description, 
		'DataWarehouse/Query/Accounts/GroupBys/GroupByResourceType' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Accounts')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'allocation' AS name, 
		'Allocation' AS display, 
		'modw' AS schema_name, 
		'allocation' AS table_name, 
		'al' AS alias, 
		'account_id' AS id_column, 
		'long_name' AS name_column, 
		'short_name' AS shortname_column, 
		'order_id' AS order_id_column, 
		'account_id' AS fk_column, 
		'A funded project that is allowed to run jobs on resources.' as description, 
		'DataWarehouse/Query/Allocations/GroupBys/GroupByAllocation' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'allocation_type' AS name, 
		'Allocation Type' AS display, 
		'modw' AS schema_name, 
		'transactiontype' AS table_name, 
		'tt' AS alias, 
		'id' AS id_column, 
		'name' AS name_column, 
		'name' AS shortname_column, 
		'name' AS order_id_column, 
		'id' AS fk_column, 
		'The type of funded projects allowed to run jobs on resources.' as description, 
		'DataWarehouse/Query/Allocations/GroupBys/GroupByAllocationType' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'board_type' AS name, 
		'Board Type' AS display, 
		'modw' AS schema_name, 
		'boardtype' AS table_name, 
		'bt' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'description' AS order_id_column, 
		'id' AS fk_column, 
		'A board type pertaining to the POPS meeting.' as description, 
		'DataWarehouse/Query/Allocations/GroupBys/GroupByBoardType' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'nsfdirectorate' AS name, 
		'NSF Directorate' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'directorate_id' AS id_column, 
		'directorate_description' AS name_column, 
		'directorate_abbrev' AS shortname_column, 
		'directorate_description' AS order_id_column, 
		'directorate_id' AS fk_column, 
		'The NSF directorate of the field of science indiciated on the allocation request.' as description, 
		'DataWarehouse/Query/Allocations/GroupBys/GroupByNSFDirectorate' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'pi' AS name, 
		'PI' AS display, 
		'modw' AS schema_name, 
		'piperson' AS table_name, 
		'pip' AS alias, 
		'person_id' AS id_column, 
		'long_name' AS name_column, 
		'short_name' AS shortname_column, 
		'order_id' AS order_id_column, 
		'person_id' AS fk_column, 
		'The principal investigator of a project has a valid allocation, which can be used by him/her or the members of the project to run jobs.' as description, 
		'DataWarehouse/Query/Allocations/GroupBys/GroupByPI' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'parentscience' AS name, 
		'Parent Science' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'parent_id' AS id_column, 
		'parent_description' AS name_column, 
		'parent_description' AS shortname_column, 
		'parent_description' AS order_id_column, 
		'parent_id' AS fk_column, 
		'The parent of the field of science indiciated on the allocation request.' as description, 
		'DataWarehouse/Query/Allocations/GroupBys/GroupByParentScience' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'resource' AS name, 
		'Resource' AS display, 
		'modw' AS schema_name, 
		'resourcefact' AS table_name, 
		'rf' AS alias, 
		'id' AS id_column, 
		'code' AS name_column, 
		'code' AS shortname_column, 
		'code' AS order_id_column, 
		'id' AS fk_column, 
		'A resource is a remote computer that can run jobs.' as description, 
		'DataWarehouse/Query/Allocations/GroupBys/GroupByResource' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'resource_type' AS name, 
		'Resource Type' AS display, 
		'modw' AS schema_name, 
		'resourcetype' AS table_name, 
		'rt' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'abbrev' AS shortname_column, 
		'description' AS order_id_column, 
		'id' AS fk_column, 
		'A categorization of resources into by their general capabilities.' as description, 
		'DataWarehouse/Query/Allocations/GroupBys/GroupByResourceType' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'fieldofscience' AS name, 
		'Field of Science' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'order_id' AS order_id_column, 
		'id' AS fk_column, 
		'The field of science indicated on the allocation request.' as description, 
		'DataWarehouse/Query/Allocations/GroupBys/GroupByScience' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Allocations')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'award_num' AS name, 
		'Award Number' AS display, 
		'modw_pops' AS schema_name, 
		'prop_mtg_supp_grant' AS table_name, 
		'fa' AS alias, 
		'award_num' AS id_column, 
		'award_num' AS name_column, 
		'award_num' AS shortname_column, 
		'award_num' AS order_id_column, 
		'award_num' AS fk_column, 
		'The number given to a grant.' as description, 
		'DataWarehouse/Query/Grants/GroupBys/GroupByAwardNumber' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Grants')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'funding_agency' AS name, 
		'Funding Agency' AS display, 
		'modw_pops' AS schema_name, 
		'funding_agency' AS table_name, 
		'fa' AS alias, 
		'org_id' AS id_column, 
		'long_name' AS name_column, 
		'short_name' AS shortname_column, 
		'short_name' AS order_id_column, 
		'org_id' AS fk_column, 
		'A funding agency is an organization that funds grants.' as description, 
		'DataWarehouse/Query/Grants/GroupBys/GroupByFundingAgency' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Grants')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'nsfdirectorate' AS name, 
		'NSF Directorate' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'directorate_id' AS id_column, 
		'directorate_description' AS name_column, 
		'directorate_abbrev' AS shortname_column, 
		'directorate_description' AS order_id_column, 
		'directorate_id' AS fk_column, 
		'The NSF directorate of the field of science indiciated on the allocation request pertaining.' as description, 
		'DataWarehouse/Query/Grants/GroupBys/GroupByNSFDirectorate' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Grants')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'parentscience' AS name, 
		'Parent Science' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'parent_id' AS id_column, 
		'parent_description' AS name_column, 
		'parent_description' AS shortname_column, 
		'parent_description' AS order_id_column, 
		'parent_id' AS fk_column, 
		'The parent of the field of science indiciated on the allocation request.' as description, 
		'DataWarehouse/Query/Grants/GroupBys/GroupByParentScience' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Grants')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'fieldofscience' AS name, 
		'Field of Science' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'order_id' AS order_id_column, 
		'id' AS fk_column, 
		'The field of science indicated on the allocation request.' as description, 
		'DataWarehouse/Query/Grants/GroupBys/GroupByScience' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Grants')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class)
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'gateway' AS name, 
		'Gateway' AS display, 
		'modw' AS schema_name, 
		'account' AS table_name, 
		'acc' AS alias, 
		'person_id' AS id_column, 
		'long_name' AS name_column, 
		'short_name' AS shortname_column, 
		'order_id' AS order_id_column, 
		'person_id' AS fk_column, 
		'A science gateway is a portal set up to aid submiting jobs to resources.' as description, 
		'DataWarehouse/Query/Jobs/GroupBys/GroupByGateway' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Jobs')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'ak_resource' AS name, 
		'AK Resource' AS display, 
		'mod_appkernel' AS schema_name, 
		'resource' AS table_name, 
		'r' AS alias, 
		'resource_id' AS id_column, 
		'resource' AS name_column, 
		'nickname' AS shortname_column, 
		'nickname' AS order_id_column, 
		'resource_id' AS fk_column, 
		'A ak resource is a remote computer that can host app kernel jobs.' as description, 
		'DataWarehouse/Query/Performance/GroupBys/GroupByResource' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Performance')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'nsfdirectorate' AS name, 
		'NSF Directorate' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'directorate_id' AS id_column, 
		'directorate_description' AS name_column, 
		'directorate_abbrev' AS shortname_column, 
		'directorate_description' AS order_id_column, 
		'directorate_id' AS fk_column, 
		'The NSF directorate of the field of science indiciated on the allocation request pertaining.' as description, 
		'DataWarehouse/Query/Proposals/GroupBys/GroupByNSFDirectorate' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Proposals')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'parentscience' AS name, 
		'Parent Science' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'parent_id' AS id_column, 
		'parent_description' AS name_column, 
		'parent_description' AS shortname_column, 
		'parent_description' AS order_id_column, 
		'parent_id' AS fk_column, 
		'The parent of the field of science indiciated on the allocation request.' as description, 
		'DataWarehouse/Query/Proposals/GroupBys/GroupByParentScience' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Proposals')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'fieldofscience' AS name, 
		'Field of Science' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'order_id' AS order_id_column, 
		'id' AS fk_column, 
		'The field of science indicated on the allocation request.' as description, 
		'DataWarehouse/Query/Proposals/GroupBys/GroupByScience' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Proposals')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'nsfdirectorate' AS name, 
		'NSF Directorate' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'directorate_id' AS id_column, 
		'directorate_description' AS name_column, 
		'directorate_abbrev' AS shortname_column, 
		'directorate_description' AS order_id_column, 
		'directorate_id' AS fk_column, 
		'The NSF directorate of the field of science indiciated on the" . " allocation request pertaining.' as description, 
		'DataWarehouse/Query/Requests/GroupBys/GroupByNSFDirectorate' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Requests')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'parentscience' AS name, 
		'Parent Science' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'parent_id' AS id_column, 
		'parent_description' AS name_column, 
		'parent_description' AS shortname_column, 
		'parent_description' AS order_id_column, 
		'parent_id' AS fk_column, 
		'The parent of the field of science indiciated on the" . " allocation request.' as description, 
		'DataWarehouse/Query/Requests/GroupBys/GroupByParentScience' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Requests')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'fieldofscience' AS name, 
		'Field of Science' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'order_id' AS order_id_column, 
		'id' AS fk_column, 
		'The field of science indicated on the allocation request.' as description, 
		'DataWarehouse/Query/Requests/GroupBys/GroupByScience' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('Requests')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'provider' AS name, 
		'Service Provider' AS display, 
		'modw' AS schema_name, 
		'serviceprovider' AS table_name, 
		'sp' AS alias, 
		'organization_id' AS id_column, 
		'short_name' AS name_column, 
		'short_name' AS shortname_column, 
		'order_id' AS order_id_column, 
		'organization_id' AS fk_column, 
		'A service provider is an institution that hosts resources.' as description, 
		'DataWarehouse/Query/ResourceAllocations/GroupBys/GroupByProvider' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('ResourceAllocations')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'resource' AS name, 
		'Resource' AS display, 
		'modw' AS schema_name, 
		'resourcefact' AS table_name, 
		'rf' AS alias, 
		'id' AS id_column, 
		'code' AS name_column, 
		'code' AS shortname_column, 
		'code' AS order_id_column, 
		'id' AS fk_column, 
		'A resource is a remote computer that can run jobs.' as description, 
		'DataWarehouse/Query/ResourceAllocations/GroupBys/GroupByResource' AS class 
FROM modules m, realms r 
WHERE m.name = 'xsede' 
	AND r.name = LOWER('ResourceAllocations')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'appclassmethod_id' AS name, 
		'Application Class. Method' AS display, 
		'modw_supremm' AS schema_name, 
		'appclassmethod' AS table_name, 
		'appclassmethod' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'The classification algorithm that was used to identify the application.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByAppclassmethodId' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'application' AS name, 
		'Application' AS display, 
		'modw_supremm' AS schema_name, 
		'application' AS table_name, 
		'app' AS alias, 
		'id' AS id_column, 
		'name' AS name_column, 
		'name' AS shortname_column, 
		'name' AS order_id_column, 
		'id' AS fk_column, 
		'The classication of the job as common scientific application.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByApplication' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'catastrophe_bucket_id' AS name, 
		'Catastrophe Rank' AS display, 
		'modw_supremm' AS schema_name, 
		'catastrophe_buckets' AS table_name, 
		'catastrophe_buckets' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'indicator L1D cache load drop off (smaller is worse)' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByCatastropheBucketId' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'cpi' AS name, 
		'CPI Value' AS display, 
		'modw_supremm' AS schema_name, 
		'cpibuckets' AS table_name, 
		'cpibuckets' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'The number of cpu clock ticks per instruction on average per core.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByCpibucketId' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'cpuuser' AS name, 
		'CPU User Value' AS display, 
		'modw_supremm' AS schema_name, 
		'percentages_buckets' AS table_name, 
		'percentages_buckets' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'The ratio of user cpu time to total cpu time for the cores that the job was assigned.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByCpuUserBucketid' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'cpucv' AS name, 
		'CPU User CV' AS display, 
		'modw_supremm' AS schema_name, 
		'cpu_user_cv_buckets' AS table_name, 
		'cpu_user_cv_buckets' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'Coefficient of variation for the CPU user for all cores that were assigned to the job.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByCpuUserCvId' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'datasource' AS name, 
		'Datasource' AS display, 
		'modw_supremm' AS schema_name, 
		'datasource' AS table_name, 
		'datasource' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'The software used to collect the performance data.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByDatasource' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'exit_status' AS name, 
		'Exit Status' AS display, 
		'modw_supremm' AS schema_name, 
		'exit_status' AS table_name, 
		'exit_status' AS alias, 
		'id' AS id_column, 
		'name' AS name_column, 
		'name' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'A categorization of jobs into discrete groups based on the exit status of each job reported by the resource manager.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByExitStatus' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'fieldofscience' AS name, 
		'Field of Science' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'order_id' AS order_id_column, 
		'id' AS fk_column, 
		'The field of science indicated on the allocation request pertaining to the running jobs.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByFieldofscience' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'grant_type' AS name, 
		'Grant Type' AS display, 
		'modw' AS schema_name, 
		'account' AS table_name, 
		'acc' AS alias, 
		'id' AS id_column, 
		'name' AS name_column, 
		'name' AS shortname_column, 
		'name' AS order_id_column, 
		'id' AS fk_column, 
		'A categorization of the projects/allocations.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByGrantType' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'granted_pe' AS name, 
		'Granted Processing Element' AS display, 
		'modw_supremm' AS schema_name, 
		'granted_pe' AS table_name, 
		'gpe' AS alias, 
		'id' AS id_column, 
		'name' AS name_column, 
		'name' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'How many cores within one node.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByGrantedPe' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'ibrxbyterate_bucket_id' AS name, 
		'InfiniBand Receive rate' AS display, 
		'modw_supremm' AS schema_name, 
		'logscalebytes_buckets' AS table_name, 
		'logscalebytes_buckets' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'number of bytes received per node over the data interconnect' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByIbrxbyterateBucketId' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'institution' AS name, 
		'User Institution' AS display, 
		'modw' AS schema_name, 
		'organization' AS table_name, 
		'o' AS alias, 
		'id' AS id_column, 
		'long_name' AS name_column, 
		'short_name' AS shortname_column, 
		'order_id' AS order_id_column, 
		'id' AS fk_column, 
		'Organizations that have users with allocations.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByInstitution' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'jobsize' AS name, 
		'Job Size' AS display, 
		'modw' AS schema_name, 
		'processor_buckets' AS table_name, 
		'pb' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'A categorization of jobs into discrete groups based on the number of cores used by each job.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByJobsize' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'jobwalltime' AS name, 
		'Job Wall Time' AS display, 
		'modw' AS schema_name, 
		'job_times' AS table_name, 
		'jt' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'A categorization of jobs into discrete groups based on the total linear time each job took to execute.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByJobwalltime' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'max_mem' AS name, 
		'Peak memory usage' AS display, 
		'modw_supremm' AS schema_name, 
		'percentages_buckets' AS table_name, 
		'percentages_buckets' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'Maximum ratio of memory used to total memory available for the compute node with the highest peak memory usage' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByMaxMemBucketid' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'netdrv_lustre_rx_bucket_id' AS name, 
		'lustre bytes received' AS display, 
		'modw_supremm' AS schema_name, 
		'log2scale_buckets' AS table_name, 
		'log2scale_buckets' AS alias, 
		'id' AS id_column, 
		'description' AS name_column, 
		'description' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'total number of bytes received per node from the lustre filesystem.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByNetdrvLustreRxBucketId' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'nodecount' AS name, 
		'Node Count' AS display, 
		'modw_supremm' AS schema_name, 
		'nodecount' AS table_name, 
		'n' AS alias, 
		'id' AS id_column, 
		'nodes' AS name_column, 
		'nodes' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'A categorization of jobs into discrete groups based on node count.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByNodecount' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'nsfdirectorate' AS name, 
		'NSF Directorate' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'directorate_id' AS id_column, 
		'directorate_description' AS name_column, 
		'directorate_abbrev' AS shortname_column, 
		'directorate_description' AS order_id_column, 
		'directorate_id' AS fk_column, 
		'The NSF directorate of the field of science indiciated on the allocation request pertaining to the running jobs.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByNSFDirectorate' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'parentscience' AS name, 
		'Parent Science' AS display, 
		'modw' AS schema_name, 
		'fieldofscience_hierarchy' AS table_name, 
		'fos' AS alias, 
		'parent_id' AS id_column, 
		'parent_description' AS name_column, 
		'parent_description' AS shortname_column, 
		'parent_description' AS order_id_column, 
		'parent_id' AS fk_column, 
		'The parent of the field of science indiciated on the allocation request pertaining to the running jobs.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByParentScience' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'person' AS name, 
		'User' AS display, 
		'modw' AS schema_name, 
		'person' AS table_name, 
		'p' AS alias, 
		'id' AS id_column, 
		'long_name' AS name_column, 
		'short_name' AS shortname_column, 
		'order_id' AS order_id_column, 
		'id' AS fk_column, 
		'A person who is on a PIs allocation.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByPerson' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'pi' AS name, 
		'PI' AS display, 
		'modw' AS schema_name, 
		'piperson' AS table_name, 
		'pip' AS alias, 
		'person_id' AS id_column, 
		'long_name' AS name_column, 
		'short_name' AS shortname_column, 
		'order_id' AS order_id_column, 
		'person_id' AS fk_column, 
		'The principal investigator of a project.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByPi' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'pi_institution' AS name, 
		'PI Institution' AS display, 
		'modw' AS schema_name, 
		'piperson' AS table_name, 
		'pip' AS alias, 
		'id' AS id_column, 
		'long_name' AS name_column, 
		'short_name' AS shortname_column, 
		'order_id' AS order_id_column, 
		'id' AS fk_column, 
		'Organizations that have PIs with allocations.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByPiInstitution' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'provider' AS name, 
		'Service Provider' AS display, 
		'modw' AS schema_name, 
		'serviceprovider' AS table_name, 
		'sp' AS alias, 
		'organization_id' AS id_column, 
		'short_name' AS name_column, 
		'short_name' AS shortname_column, 
		'order_id' AS order_id_column, 
		'organization_id' AS fk_column, 
		'A service provider is an institution that hosts resource(s).' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByProvider' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'queue' AS name, 
		'Queue' AS display, 
		'modw' AS schema_name, 
		'queue' AS table_name, 
		'q' AS alias, 
		'id' AS id_column, 
		'id' AS name_column, 
		'id' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'Queue pertains to the low level job queues on each resource.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByQueue' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'resource' AS name, 
		'Resource' AS display, 
		'modw' AS schema_name, 
		'resourcefact' AS table_name, 
		'rf' AS alias, 
		'id' AS id_column, 
		'code' AS name_column, 
		'code' AS shortname_column, 
		'code' AS order_id_column, 
		'id' AS fk_column, 
		'A resource is a remote computer that can run jobs.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByResource' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'shared' AS name, 
		'Share Mode' AS display, 
		'modw_supremm' AS schema_name, 
		'shared' AS table_name, 
		'shared' AS alias, 
		'id' AS id_column, 
		'name' AS name_column, 
		'name' AS shortname_column, 
		'id' AS order_id_column, 
		'id' AS fk_column, 
		'A categorization of jobs into discrete groups based on whether the job shared nodes.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByShared' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

INSERT INTO group_bys (module_id, realm_id, name, display, schema_name, table_name, alias, id_column, name_column, shortname_column, order_id_column, fk_column, description, class) 
SELECT inc.* 
FROM ( 
	SELECT 
		m.module_id as module_id, 
		r.realm_id, 
		'username' AS name, 
		'System Username' AS display, 
		'modw' AS schema_name, 
		'systemaccount' AS table_name, 
		'sa' AS alias, 
		'username' AS id_column, 
		'username' AS name_column, 
		'username' AS shortname_column, 
		'username' AS order_id_column, 
		'username' AS fk_column, 
		'The specific system username of the users who ran jobs.' as description, 
		'DataWarehouse/Query/SUPREMM/GroupBys/GroupByUsername' AS class 
FROM modules m, realms r 
WHERE m.name = 'supremm' 
	AND r.name = LOWER('SUPREMM')) inc 
LEFT JOIN group_bys cur 
	ON cur.module_id = inc.module_id 
	AND cur.display = inc.display 
	AND cur.schema_name = inc.schema_name 
	AND cur.table_name = inc.table_name 
	AND cur.alias = inc.alias 
	AND cur.id_column = inc.id_column 
	AND cur.name_column = inc.name_column 
	AND cur.shortname_column = inc.shortname_column 
	AND cur.order_id_column = inc.order_id_column 
	AND cur.fk_column = inc.fk_column 
	AND cur.description = inc.description 
	AND cur.class = inc.class 
WHERE cur.group_by_id IS NULL;

