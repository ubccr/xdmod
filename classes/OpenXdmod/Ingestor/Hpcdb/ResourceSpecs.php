<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

/**
 * Ingest resource specs from the HPcDB.
 */
class ResourceSpecs extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            '
                SELECT
                    r.resource_id        AS resource_id,
                    s.start_date_ts      AS start_date_ts,
                    s.end_date_ts        AS end_date_ts,
                    s.cpu_count          AS processors,
                    s.node_count         AS q_nodes,
                    s.cpu_count_per_node AS q_ppn,
                    s.comments           AS comments,
                    r.resource_name      AS name
                FROM hpcdb_resources r
                INNER JOIN hpcdb_resource_specs s
                    ON r.resource_id = s.resource_id
                ORDER BY r.resource_id, start_date_ts
            ',
            'resourcespecs',
            array(
                'resource_id',
                'start_date_ts',
                'end_date_ts',
                'processors',
                'q_nodes',
                'q_ppn',
                'comments',
                'name',
            )
        );
    }
}
