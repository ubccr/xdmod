INSERT INTO ${DESTINATION_SCHEMA}.`domains` (id, resource_id, name)
SELECT inc.*
FROM (
         SELECT 1           as id,
             /* We need a valid resource_id so attempt to find the one associated with the `default`
                domain and utilize that. If we can't find one then fall back to a default entry in
                domains. If that's not present then fall back to -1. */
                COALESCE(
                        COALESCE(
                                (SELECT resource_id
                                 FROM ${DESTINATION_SCHEMA}.openstack_raw_event ore
                                 WHERE LOWER(ore.domain) = 'default'
                                 LIMIT 1),
                                (SELECT resource_id
                                 FROM ${DESTINATION_SCHEMA}.domains d
                                 WHERE LOWER(d.name) = 'default'
                                 LIMIT 1)
                            ),
                        -1) as resource_id,
                'Unknown'   as name
     ) inc
         LEFT JOIN modw_cloud.`domains` cur
                   ON cur.id = inc.id
WHERE cur.id IS NULL//
