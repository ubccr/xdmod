<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

/**
 * Ingest resources from the HPcDB.
 */
class Resources extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            '
                SELECT
                    r.resource_id          AS id,
                    r.resource_type_id     AS resourcetype_id,
                    r.organization_id      AS organization_id,
                    r.resource_name        AS name,
                    r.resource_code        AS code,
                    r.resource_description AS description,
                    r.resource_shared_jobs AS shared_jobs,
                    r.resource_timezone    AS timezone
                FROM hpcdb_resources r
                ORDER BY r.resource_id
            ',
            'resourcefact',
            array(
                'id',
                'resourcetype_id',
                'organization_id',
                'name',
                'code',
                'description',
                'shared_jobs',
                'timezone',
            )
        );
    }
}
