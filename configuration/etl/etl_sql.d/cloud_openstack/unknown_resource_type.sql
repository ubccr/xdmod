INSERT INTO ${DESTINATION_SCHEMA}.`staging_resource_type` (resource_type_id, resource_type_description, resource_type_abbrev)
SELECT inc.*
FROM (
    SELECT
        -1 as resource_type_id,
        'Unknown Resource Type' as resource_type_description,
        'UNK' as resource_type_abbrev
         ) inc
LEFT JOIN ${DESTINATION_SCHEMA}.`staging_resource_type` cur
          ON cur.resource_type_id = inc.resource_type_id
              AND cur.resource_type_description = inc.resource_type_description
              AND cur.resource_type_abbrev = inc.resource_type_abbrev
WHERE cur.resource_type_id IS NULL;
