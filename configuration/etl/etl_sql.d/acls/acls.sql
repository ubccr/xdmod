-- 
INSERT INTO ${DESTINATION_SCHEMA}.acls(module_id, acl_type_id, name, display, enabled)
SELECT
  m.module_id,
  (SELECT at.acl_type_id FROM ${DESTINATION_SCHEMA}.acl_types at WHERE at.name = 'flag') as acl_type_id,
  'mgr' as name,
  'Manager' as display,
  true as enabled
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acls(module_id, acl_type_id, name, display, enabled)
SELECT
m.module_id,
(SELECT at.acl_type_id FROM ${DESTINATION_SCHEMA}.acl_types at WHERE at.name = 'flag') as acl_type_id,
'dev' as name,
'Developer' as display,
true as enabled
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acls(module_id, acl_type_id, name, display, enabled)
SELECT
m.module_id,
(SELECT at.acl_type_id FROM ${DESTINATION_SCHEMA}.acl_types at WHERE at.name = 'data') as acl_type_id,
'usr' as name,
'User' as display,
true as enabled
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acls(module_id, acl_type_id, name, display, enabled)
SELECT
m.module_id,
(SELECT at.acl_type_id FROM ${DESTINATION_SCHEMA}.acl_types at WHERE at.name = 'data') as acl_type_id,
'po' as name,
'Program Officer' as display,
true as enabled
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acls(module_id, acl_type_id, name, display, enabled)
SELECT
m.module_id,
(SELECT at.acl_type_id FROM ${DESTINATION_SCHEMA}.acl_types at WHERE at.name = 'data') as acl_type_id,
'cs' as name,
'Center Staff' as display,
true as enabled
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acls(module_id, acl_type_id, name, display, enabled)
SELECT
m.module_id,
(SELECT at.acl_type_id FROM ${DESTINATION_SCHEMA}.acl_types at WHERE at.name = 'feature') as acl_type_id,
'cd' as name,
'Center Director' as display,
true as enabled
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acls(module_id, acl_type_id, name, display, enabled)
SELECT
m.module_id,
(SELECT at.acl_type_id FROM ${DESTINATION_SCHEMA}.acl_types at WHERE at.name = 'flag') as acl_type_id,
'pi' as name,
'Principal Investigator' as display,
true as enabled
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';

INSERT INTO ${DESTINATION_SCHEMA}.acls(module_id, acl_type_id, name, display, enabled)
SELECT
m.module_id,
(SELECT at.acl_type_id FROM ${DESTINATION_SCHEMA}.acl_types at WHERE at.name = 'flag') as acl_type_id,
'cc' as name,
'Campus Champion' as display,
true as enabled
FROM ${DESTINATION_SCHEMA}.modules m
WHERE m.name = 'xdmod';
