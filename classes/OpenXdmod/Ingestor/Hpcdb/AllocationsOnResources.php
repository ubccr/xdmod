<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class AllocationsOnResources extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            '
                SELECT
                    allocation_id,
                    resource_id
                FROM hpcdb_allocations_on_resources
            ',
            'allocationonresource',
            array(
                'allocation_id',
                'resource_id',
            )
        );
    }
}

