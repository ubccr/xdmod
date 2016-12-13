<?php

namespace OpenXdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class AllocationBreakdowns extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            '
                SELECT
                    allocation_breakdown_id AS id,
                    person_id,
                    allocation_id,
                    percentage
                FROM hpcdb_allocation_breakdown
            ',
            'allocationbreakdown',
            array(
                'id',
                'person_id',
                'allocation_id',
                'percentage',
            )
        );
    }
}

