INSERT INTO ${DESTINATION_SCHEMA}.`domains` (id, domain_id, resource_id, name)
SELECT inc.*
FROM (
         SELECT  1                                                                                                        as id,
                -1                                                                                                        as domain_id,
                /* We need a valid resource_id so attempt to find the one associated with the `default`
                   domain and utilize that. If we can't find one then fall back to -1. */
                COALESCE((SELECT resource_id FROM ${DESTINATION_SCHEMA}.`domains_staging` d WHERE LOWER(d.name) = 'default'),-1)  as resource_id,
                'Unknown'                                                                                                 as name
     ) inc
         LEFT JOIN ${DESTINATION_SCHEMA}.`domains` cur
                   ON cur.id = inc.id
                       AND cur.resource_id = inc.resource_id
                       AND cur.domain_id = inc.domain_id
                       AND cur.name = inc.name
WHERE cur.id IS NULL;
