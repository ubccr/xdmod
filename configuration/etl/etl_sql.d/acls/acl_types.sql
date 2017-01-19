INSERT INTO ${DESTINATION_SCHEMA}.acl_types(module_id, name, display)
SELECT inc.*
FROM (
     SELECT
      m.module_id,
      'data' as name,
      'Data' as display
      FROM ${DESTINATION_SCHEMA}.modules m
      WHERE m.name = 'xdmod') inc
LEFT JOIN ${DESTINATION_SCHEMA}.acl_types cur
     ON  cur.module_id = inc.module_id
     AND cur.name      = inc.name
     AND cur.display   = inc.display
WHERE cur.acl_type_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.acl_types(module_id, name, display)
SELECT inc.*
FROM (
     SELECT
      m.module_id,
      'feature' as name,
      'Feature' as display
     FROM ${DESTINATION_SCHEMA}.modules m
     WHERE m.name = 'xdmod') inc
LEFT JOIN ${DESTINATION_SCHEMA}.acl_types cur
     ON  cur.module_id = inc.module_id
     AND cur.name      = inc.name
     AND cur.display   = inc.display
     WHERE cur.acl_type_id IS NULL;

INSERT INTO ${DESTINATION_SCHEMA}.acl_types(module_id, name, display)
SELECT inc.*
FROM (
     SELECT
      m.module_id,
      'flag' as name,
      'Flag' as display
     FROM ${DESTINATION_SCHEMA}.modules m
     WHERE m.name = 'xdmod') inc
LEFT JOIN ${DESTINATION_SCHEMA}.acl_types cur
     ON  cur.module_id = inc.module_id
     AND cur.name      = inc.name
     AND cur.display   = inc.display
     WHERE cur.acl_type_id IS NULL;
