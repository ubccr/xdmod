<?php

namespace OpenXdmod\Ingestor\Staging;

use PDODBSynchronizingIngestor;

class AllocationsOnResources extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT
                    pr.pi_resource_id AS allocation_id,
                    r.resource_id     AS resource_id
                FROM staging_pi_resource pr
                JOIN staging_resource r
                    ON pr.resource_name = r.resource_name
            ',
            'hpcdb_allocations_on_resources',
            'allocation_id',
            array(
                'allocation_id',
                'resource_id',
            )
        );
    }
}
