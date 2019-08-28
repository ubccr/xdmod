INSERT INTO ${DESTINATION_SCHEMA}.`domains` (id, resource_id, domain_id, name)
SELECT inc.*
FROM (
         SELECT
             1        as id,
             -1        as domain_id,
             -1        as resource_id,
             'Unknown' as name
     ) inc
         LEFT JOIN ${DESTINATION_SCHEMA}.`domains` cur
                   ON cur.id = inc.id
                       AND cur.resource_id = inc.resource_id
                       AND cur.domain_id = inc.domain_id
                       AND cur.name = inc.name
WHERE cur.id IS NULL;
