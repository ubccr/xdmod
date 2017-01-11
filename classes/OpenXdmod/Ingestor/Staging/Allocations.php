<?php

namespace OpenXdmod\Ingestor\Staging;

use PDODBSynchronizingIngestor;

class Allocations extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            "
                SELECT
                    pr.pi_resource_id AS allocation_id,
                    r.resource_id     AS resource_id,
                    p.pi_id           AS account_id
                FROM staging_pi_resource pr
                JOIN staging_resource r
                    ON pr.resource_name = r.resource_name
                JOIN staging_pi p
                    ON pr.pi_name = p.pi_name
            ",
            'hpcdb_allocations',
            'allocation_id',
            array(
                'allocation_id',
                'resource_id',
                'account_id',
            )
        );
    }
}
