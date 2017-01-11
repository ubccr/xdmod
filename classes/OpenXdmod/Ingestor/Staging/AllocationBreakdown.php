<?php

namespace OpenXdmod\Ingestor\Staging;

use PDODBSynchronizingIngestor;

class AllocationBreakdown extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT
                    upr.user_pi_resource_id AS allocation_breakdown_id,
                    uup.union_user_pi_id    AS person_id,
                    pr.pi_resource_id       AS allocation_id,
                    100                     AS percentage
                FROM staging_user_pi_resource upr
                LEFT JOIN staging_pi_resource pr
                     ON upr.pi_name       = pr.pi_name
                    AND upr.resource_name = pr.resource_name
                LEFT JOIN staging_union_user_pi uup
                    ON upr.user_name = uup.union_user_pi_name
            ',
            'hpcdb_allocation_breakdown',
            'allocation_breakdown_id',
            array(
                'allocation_breakdown_id',
                'person_id',
                'allocation_id',
                'percentage',
            )
        );
    }
}
